<?php
/**
 * Flag management for safety and moderation.
 */
class ZAOBank_Flags {
	const HIDDEN_MESSAGES_OPTION = 'zaobank_hidden_message_ids';

	/**
	 * Get normalized flag reason options.
	 *
	 * @return array<int, array{slug:string,label:string}>
	 */
	public static function get_reason_options() {
		return array_values(self::get_reason_map());
	}

	/**
	 * Get all valid reason slugs.
	 *
	 * @return string[]
	 */
	public static function get_reason_slugs() {
		return array_keys(self::get_reason_map());
	}

	/**
	 * Get human-readable label for a reason slug.
	 */
	public static function get_reason_label($slug) {
		$slug = sanitize_title((string) $slug);
		$reasons = self::get_reason_map();
		if (isset($reasons[ $slug ]['label'])) {
			return $reasons[ $slug ]['label'];
		}

		return ucwords(str_replace('-', ' ', $slug));
	}

	/**
	 * Create a flag.
	 */
	public static function create_flag($data) {
		global $wpdb;
		$table = ZAOBank_Database::get_flags_table();

		// Validate required fields
		if (empty($data['flagged_item_type']) || empty($data['flagged_item_id']) ||
			empty($data['reporter_user_id']) || empty($data['reason_slug'])) {
			return new WP_Error(
				'missing_flag_data',
				__('Missing required flag data', 'zaobank')
			);
		}

		// Rate limiting on flagging
		$rate_check = ZAOBank_Security::check_rate_limit(
			'create_flag_' . $data['flagged_item_type'] . '_' . $data['flagged_item_id'],
			$data['reporter_user_id'],
			3,
			86400 // 24 hours
		);

		if (is_wp_error($rate_check)) {
			return $rate_check;
		}

		// Validate reason
		$reason_slug = self::normalize_reason_slug($data['reason_slug']);
		if (!in_array($reason_slug, self::get_reason_slugs(), true)) {
			return new WP_Error(
				'invalid_reason',
				__('Invalid flag reason', 'zaobank')
			);
		}
		$data['reason_slug'] = $reason_slug;

		if ($data['flagged_item_type'] === 'user' && empty($data['flagged_user_id'])) {
			$data['flagged_user_id'] = (int) $data['flagged_item_id'];
		}

		$result = $wpdb->insert(
			$table,
			array(
				'flagged_item_type' => sanitize_key($data['flagged_item_type']),
				'flagged_item_id' => (int) $data['flagged_item_id'],
				'flagged_user_id' => isset($data['flagged_user_id']) ? (int) $data['flagged_user_id'] : null,
				'reporter_user_id' => (int) $data['reporter_user_id'],
				'reason_slug' => $reason_slug,
				'context_note' => isset($data['context_note']) ? sanitize_textarea_field($data['context_note']) : null,
				'status' => 'open',
				'created_at' => wp_date('Y-m-d H:i:s')
			),
			array('%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s')
		);

		if ($result === false) {
			return new WP_Error(
				'flag_creation_failed',
				__('Failed to create flag', 'zaobank')
			);
		}

		$flag_id = $wpdb->insert_id;

		// Take immediate action based on settings
		self::apply_immediate_actions($data);

		// Log the flag
		ZAOBank_Security::log_security_event('flag_created', array(
			'flag_id' => $flag_id,
			'item_type' => $data['flagged_item_type'],
			'item_id' => $data['flagged_item_id']
		));

		// Notify moderators about the new flag
		self::send_mod_alert(
			sprintf(
				__('New %s flag: %s', 'zaobank'),
				$data['flagged_item_type'],
				self::get_reason_label($reason_slug)
			),
			isset($data['flagged_user_id']) ? (int) $data['flagged_user_id'] : 0
		);

		// Check auto-downgrade threshold for user flags
		if (!empty($data['flagged_user_id'])) {
			self::check_auto_downgrade((int) $data['flagged_user_id']);
		}

		return $flag_id;
	}

	/**
	 * Apply immediate automatic actions when content is flagged.
	 */
	private static function apply_immediate_actions($data) {
		if (!get_option('zaobank_auto_hide_flagged', true)) {
			return;
		}

		switch ($data['flagged_item_type']) {
			case 'job':
				// Hide job from listings
				update_post_meta($data['flagged_item_id'], 'visibility', 'hidden');
				break;

			case 'appreciation':
				// Hide appreciation
				global $wpdb;
				$table = ZAOBank_Database::get_appreciations_table();
				$wpdb->update(
					$table,
					array('is_public' => 0),
					array('id' => $data['flagged_item_id']),
					array('%d'),
					array('%d')
				);
				break;

			case 'message':
				// Hide message from both users while under review
				self::hide_message((int) $data['flagged_item_id']);
				break;

			case 'user':
				// Optional: Apply temporary restrictions
				// This could be expanded based on community needs
				break;
		}
	}

	/**
	 * Get flags for review.
	 */
	public static function get_flags_for_review($status = 'open', $args = array()) {
		global $wpdb;
		$table = ZAOBank_Database::get_flags_table();

		$defaults = array(
			'limit' => 50,
			'offset' => 0
		);

		$args = wp_parse_args($args, $defaults);

		$status = sanitize_key($status);

		if ($status === 'all') {
			$query = $wpdb->prepare(
				"SELECT * FROM $table
				ORDER BY created_at DESC
				LIMIT %d OFFSET %d",
				$args['limit'],
				$args['offset']
			);
		} else {
			$query = $wpdb->prepare(
				"SELECT * FROM $table 
				WHERE status = %s
				ORDER BY created_at DESC
				LIMIT %d OFFSET %d",
				$status,
				$args['limit'],
				$args['offset']
			);
		}

		$flags = $wpdb->get_results($query);

		return array_map(array(__CLASS__, 'format_flag_data'), $flags);
	}

	/**
	 * Update flag status.
	 */
	public static function update_flag_status($flag_id, $status, $resolution_note = null) {
		global $wpdb;
		$table = ZAOBank_Database::get_flags_table();
		$status = sanitize_key($status);

		if (empty($status)) {
			return new WP_Error(
				'invalid_status',
				__('Invalid flag status', 'zaobank')
			);
		}

		$data = array(
			'status' => $status,
			'reviewed_at' => wp_date('Y-m-d H:i:s'),
			'reviewer_user_id' => get_current_user_id()
		);
		$formats = array('%s', '%s', '%d');

		if ($resolution_note) {
			$data['resolution_note'] = sanitize_textarea_field($resolution_note);
			$formats[] = '%s';
		}

		$result = $wpdb->update(
			$table,
			$data,
			array('id' => $flag_id),
			$formats,
			array('%d')
		);

		if ($result === false) {
			return new WP_Error(
				'flag_update_failed',
				__('Failed to update flag', 'zaobank')
			);
		}

		// Log the resolution
		ZAOBank_Security::log_security_event('flag_resolved', array(
			'flag_id' => $flag_id,
			'status' => $status
		));

		return true;
	}

	/**
	 * Remove flagged content after moderator confirmation.
	 */
	public static function remove_content($flag_id) {
		global $wpdb;
		$flag = self::get_flag_row($flag_id);

		if (!$flag) {
			return new WP_Error('invalid_flag', __('Invalid flag', 'zaobank'));
		}

		switch ($flag->flagged_item_type) {
			case 'job':
				update_post_meta((int) $flag->flagged_item_id, 'visibility', 'hidden');
				break;

			case 'appreciation':
				$table = ZAOBank_Database::get_appreciations_table();
				$wpdb->update(
					$table,
					array('is_public' => 0),
					array('id' => (int) $flag->flagged_item_id),
					array('%d'),
					array('%d')
				);
				break;

			case 'message':
				self::hide_message((int) $flag->flagged_item_id);
				break;

			case 'user':
				$user_id = self::get_effective_flagged_user_id($flag);
				$user = $user_id ? get_userdata($user_id) : null;
				if (!$user) {
					return new WP_Error('invalid_user', __('Unable to find the flagged user.', 'zaobank'));
				}

				if (in_array('administrator', $user->roles, true)) {
					return new WP_Error('cannot_remove_admin', __('Administrators cannot be removed here.', 'zaobank'));
				}

				if (in_array('leadership_team', $user->roles, true)) {
					return new WP_Error('cannot_remove_leadership', __('Leadership users cannot be removed here.', 'zaobank'));
				}

				$current_role = !empty($user->roles) ? reset($user->roles) : 'member';
				update_user_meta($user_id, 'zaobank_flag_prev_role_' . (int) $flag_id, sanitize_key($current_role));
				$user->set_role('member_limited');
				break;

			default:
				return new WP_Error('unsupported_flag_item', __('Unsupported flagged content type.', 'zaobank'));
		}

		ZAOBank_Security::log_security_event('flag_content_removed', array(
			'flag_id' => (int) $flag_id,
			'item_type' => $flag->flagged_item_type,
			'item_id' => (int) $flag->flagged_item_id,
		));

		return true;
	}

	/**
	 * Restore flagged content.
	 */
	public static function restore_content($flag_id) {
		global $wpdb;
		$flag = self::get_flag_row($flag_id);

		if (!$flag) {
			return new WP_Error('invalid_flag', __('Invalid flag', 'zaobank'));
		}

		switch ($flag->flagged_item_type) {
			case 'job':
				update_post_meta($flag->flagged_item_id, 'visibility', 'public');
				break;

			case 'appreciation':
				$table = ZAOBank_Database::get_appreciations_table();
				$wpdb->update(
					$table,
					array('is_public' => 1),
					array('id' => $flag->flagged_item_id),
					array('%d'),
					array('%d')
				);
				break;

			case 'message':
				self::unhide_message((int) $flag->flagged_item_id);
				break;

			case 'user':
				$user_id = self::get_effective_flagged_user_id($flag);
				$user = $user_id ? get_userdata($user_id) : null;
				if (!$user) {
					return new WP_Error('invalid_user', __('Unable to find the flagged user.', 'zaobank'));
				}

				$previous_role = sanitize_key((string) get_user_meta($user_id, 'zaobank_flag_prev_role_' . (int) $flag_id, true));
				if (empty($previous_role) || !wp_roles()->is_role($previous_role) || $previous_role === 'member_limited') {
					$previous_role = 'member';
				}

				$user->set_role($previous_role);
				delete_user_meta($user_id, 'zaobank_flag_prev_role_' . (int) $flag_id);
				break;
		}

		ZAOBank_Security::log_security_event('flag_content_restored', array(
			'flag_id' => (int) $flag_id,
			'item_type' => $flag->flagged_item_type,
			'item_id' => (int) $flag->flagged_item_id,
		));

		return true;
	}

	/**
	 * Get hidden message IDs.
	 *
	 * @return int[]
	 */
	public static function get_hidden_message_ids() {
		$hidden_ids = get_option(self::HIDDEN_MESSAGES_OPTION, array());
		if (!is_array($hidden_ids)) {
			return array();
		}

		$hidden_ids = array_map('intval', $hidden_ids);
		$hidden_ids = array_filter($hidden_ids);

		return array_values(array_unique($hidden_ids));
	}

	/**
	 * Hide message for all users.
	 */
	public static function hide_message($message_id) {
		$message_id = (int) $message_id;
		if ($message_id < 1) {
			return;
		}

		$hidden_ids = self::get_hidden_message_ids();
		if (!in_array($message_id, $hidden_ids, true)) {
			$hidden_ids[] = $message_id;
			update_option(self::HIDDEN_MESSAGES_OPTION, array_values($hidden_ids));
		}
	}

	/**
	 * Unhide previously hidden message.
	 */
	public static function unhide_message($message_id) {
		$message_id = (int) $message_id;
		if ($message_id < 1) {
			return;
		}

		$hidden_ids = self::get_hidden_message_ids();
		$hidden_ids = array_values(array_filter($hidden_ids, function($id) use ($message_id) {
			return (int) $id !== $message_id;
		}));
		update_option(self::HIDDEN_MESSAGES_OPTION, $hidden_ids);
	}

	/**
	 * Check if a user should be auto-downgraded based on flag count.
	 */
	public static function check_auto_downgrade($user_id) {
		$threshold = (int) get_option('zaobank_flag_auto_downgrade_threshold', 3);
		if ($threshold < 1) {
			return;
		}

		global $wpdb;
		$table = ZAOBank_Database::get_flags_table();

		$open_count = (int) $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM $table
			 WHERE flagged_user_id = %d AND status IN ('open', 'under_review')",
			$user_id
		));

		if ($open_count >= $threshold) {
			$user = get_userdata($user_id);
			if (!$user) {
				return;
			}

			// Only downgrade 'member' role, never admin/leadership
			if (!in_array('member', $user->roles, true)) {
				return;
			}

			$user->set_role('member_limited');

			ZAOBank_Security::log_security_event('auto_downgrade', array(
				'user_id'    => $user_id,
				'flag_count' => $open_count,
				'threshold'  => $threshold,
			));

			self::send_mod_alert(
				sprintf(
					__('User %s was auto-downgraded to limited member after %d flags.', 'zaobank'),
					$user->display_name,
					$open_count
				),
				$user_id
			);
		}
	}

	/**
	 * Send a mod_alert message to all moderators.
	 *
	 * @param string $message         Alert message text.
	 * @param int    $related_user_id Optional related user ID (stored in job_id column).
	 */
	public static function send_mod_alert($message, $related_user_id = 0) {
		$mod_users = get_users(array(
			'role__in' => array('administrator', 'leadership_team'),
			'fields'   => 'ID',
		));

		foreach ($mod_users as $mod_user_id) {
			ZAOBank_Messages::create_message(array(
				'from_user_id' => 0,
				'to_user_id'   => (int) $mod_user_id,
				'message'      => sanitize_text_field($message),
				'message_type' => 'mod_alert',
				'job_id'       => (int) $related_user_id,
			));
		}
	}

	/**
	 * Format flag data.
	 */
	private static function format_flag_data($flag) {
		$flagged_user_id = self::get_effective_flagged_user_id($flag);

		return array(
			'id' => (int) $flag->id,
			'flagged_item_type' => $flag->flagged_item_type,
			'flagged_item_id' => (int) $flag->flagged_item_id,
			'flagged_user_id' => $flagged_user_id ?: null,
			'reporter_user_id' => (int) $flag->reporter_user_id,
			'reporter_name' => get_the_author_meta('display_name', $flag->reporter_user_id),
			'reason_slug' => $flag->reason_slug,
			'reason_label' => self::get_reason_label($flag->reason_slug),
			'context_note' => $flag->context_note,
			'status' => $flag->status,
			'created_at' => $flag->created_at,
			'reviewed_at' => $flag->reviewed_at,
			'reviewer_user_id' => $flag->reviewer_user_id ? (int) $flag->reviewer_user_id : null,
			'resolution_note' => $flag->resolution_note
		);
	}

	/**
	 * Build a normalized reason map keyed by slug.
	 *
	 * @return array<string, array{slug:string,label:string}>
	 */
	private static function get_reason_map() {
		$configured_reasons = get_option('zaobank_flag_reasons', array());
		$configured_reasons = is_array($configured_reasons) ? $configured_reasons : array();

		$map = array();
		foreach ($configured_reasons as $reason) {
			$reason = sanitize_text_field((string) $reason);
			if ($reason === '') {
				continue;
			}

			$slug = sanitize_title($reason);
			if ($slug === '') {
				continue;
			}

			$is_slug = (bool) preg_match('/^[a-z0-9_-]+$/i', $reason);
			$label_source = $is_slug ? str_replace(array('_', '-'), ' ', $reason) : $reason;
			$label = trim(ucwords($label_source));

			$map[ $slug ] = array(
				'slug' => $slug,
				'label' => $label,
			);
		}

		if (!empty($map)) {
			return $map;
		}

		$defaults = array(
			'inappropriate-content',
			'harassment',
			'spam',
			'safety-concern',
			'other',
		);

		foreach ($defaults as $slug) {
			$map[ $slug ] = array(
				'slug' => $slug,
				'label' => ucwords(str_replace('-', ' ', $slug)),
			);
		}

		return $map;
	}

	/**
	 * Normalize reason input to configured reason slug.
	 */
	private static function normalize_reason_slug($reason) {
		$reason = sanitize_text_field((string) $reason);
		if ($reason === '') {
			return '';
		}

		$normalized = sanitize_title($reason);
		$reasons = self::get_reason_map();

		if (isset($reasons[ $normalized ])) {
			return $normalized;
		}

		foreach ($reasons as $slug => $details) {
			if (strtolower($details['label']) === strtolower($reason)) {
				return $slug;
			}
		}

		return $normalized;
	}

	/**
	 * Fetch a single flag row.
	 */
	private static function get_flag_row($flag_id) {
		global $wpdb;
		$flags_table = ZAOBank_Database::get_flags_table();

		return $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM $flags_table WHERE id = %d",
			(int) $flag_id
		));
	}

	/**
	 * Get flagged user ID fallback for user-type flags.
	 */
	private static function get_effective_flagged_user_id($flag) {
		if (!empty($flag->flagged_user_id)) {
			return (int) $flag->flagged_user_id;
		}

		if (isset($flag->flagged_item_type) && $flag->flagged_item_type === 'user') {
			return (int) $flag->flagged_item_id;
		}

		return 0;
	}
}
