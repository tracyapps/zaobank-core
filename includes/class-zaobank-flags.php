<?php
/**
 * Flag management for safety and moderation.
 */
class ZAOBank_Flags {

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
		$valid_reasons = get_option('zaobank_flag_reasons', array());
		if (!in_array($data['reason_slug'], $valid_reasons)) {
			return new WP_Error(
				'invalid_reason',
				__('Invalid flag reason', 'zaobank')
			);
		}

		$result = $wpdb->insert(
			$table,
			array(
				'flagged_item_type' => sanitize_key($data['flagged_item_type']),
				'flagged_item_id' => (int) $data['flagged_item_id'],
				'flagged_user_id' => isset($data['flagged_user_id']) ? (int) $data['flagged_user_id'] : null,
				'reporter_user_id' => (int) $data['reporter_user_id'],
				'reason_slug' => sanitize_key($data['reason_slug']),
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
				$data['reason_slug']
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
				// Mark message as hidden
				$table = ZAOBank_Database::get_messages_table();
				$wpdb->update(
					$table,
					array('is_read' => 1), // Using read flag as hidden marker
					array('id' => $data['flagged_item_id']),
					array('%d'),
					array('%d')
				);
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

		$query = $wpdb->prepare(
			"SELECT * FROM $table 
            WHERE status = %s
            ORDER BY created_at DESC
            LIMIT %d OFFSET %d",
			$status,
			$args['limit'],
			$args['offset']
		);

		$flags = $wpdb->get_results($query);

		return array_map(array(__CLASS__, 'format_flag_data'), $flags);
	}

	/**
	 * Update flag status.
	 */
	public static function update_flag_status($flag_id, $status, $resolution_note = null) {
		global $wpdb;
		$table = ZAOBank_Database::get_flags_table();

		$data = array(
			'status' => $status,
			'reviewed_at' => wp_date('Y-m-d H:i:s'),
			'reviewer_user_id' => get_current_user_id()
		);

		if ($resolution_note) {
			$data['resolution_note'] = sanitize_textarea_field($resolution_note);
		}

		$result = $wpdb->update(
			$table,
			$data,
			array('id' => $flag_id),
			array('%s', '%s', '%d', '%s'),
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
	 * Restore flagged content.
	 */
	public static function restore_content($flag_id) {
		global $wpdb;
		$flags_table = ZAOBank_Database::get_flags_table();

		$flag = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM $flags_table WHERE id = %d",
			$flag_id
		));

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
		}

		return true;
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
		return array(
			'id' => (int) $flag->id,
			'flagged_item_type' => $flag->flagged_item_type,
			'flagged_item_id' => (int) $flag->flagged_item_id,
			'flagged_user_id' => $flag->flagged_user_id ? (int) $flag->flagged_user_id : null,
			'reporter_user_id' => (int) $flag->reporter_user_id,
			'reporter_name' => get_the_author_meta('display_name', $flag->reporter_user_id),
			'reason_slug' => $flag->reason_slug,
			'context_note' => $flag->context_note,
			'status' => $flag->status,
			'created_at' => $flag->created_at,
			'reviewed_at' => $flag->reviewed_at,
			'reviewer_user_id' => $flag->reviewer_user_id ? (int) $flag->reviewer_user_id : null,
			'resolution_note' => $flag->resolution_note
		);
	}
}