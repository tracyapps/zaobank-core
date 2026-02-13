<?php
/**
 * REST API: Moderation endpoints.
 *
 * Provides front-end moderation dashboard with user management,
 * enriched flag data, badge counts, and settings.
 */
class ZAOBank_REST_Moderation extends ZAOBank_REST_Controller {

	public function register_routes() {
		// List users with role, flag count, registration date
		register_rest_route($this->namespace, '/moderation/users', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array($this, 'get_users'),
			'permission_callback' => array($this, 'check_moderation_access'),
			'args'                => array(
				'q'        => array('type' => 'string', 'default' => ''),
				'role'     => array('type' => 'string', 'default' => ''),
				'sort'     => array('type' => 'string', 'default' => 'recent'),
				'page'     => array('type' => 'integer', 'default' => 1),
				'per_page' => array('type' => 'integer', 'default' => 20),
			),
		));

		// Change user role
		register_rest_route($this->namespace, '/moderation/users/(?P<id>[\d]+)/role', array(
			'methods'             => WP_REST_Server::EDITABLE,
			'callback'            => array($this, 'update_user_role'),
			'permission_callback' => array($this, 'check_moderation_access'),
			'args'                => array(
				'role' => array('type' => 'string', 'required' => true),
			),
		));

		// Get enriched flags with user/item data
		register_rest_route($this->namespace, '/moderation/flags', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array($this, 'get_flags'),
			'permission_callback' => array($this, 'check_moderation_access'),
			'args'                => array(
				'status'   => array('type' => 'string', 'default' => 'open'),
				'type'     => array('type' => 'string', 'default' => ''),
				'page'     => array('type' => 'integer', 'default' => 1),
				'per_page' => array('type' => 'integer', 'default' => 20),
			),
		));

		// Update flag status (from moderation dashboard)
		register_rest_route($this->namespace, '/moderation/flags/(?P<id>[\d]+)', array(
			'methods'             => WP_REST_Server::EDITABLE,
			'callback'            => array($this, 'update_flag'),
			'permission_callback' => array($this, 'check_moderation_access'),
			'args'                => array(
				'action'          => array('type' => 'string'),
				'status'          => array('type' => 'string'),
				'resolution_note' => array('type' => 'string'),
				'remove'          => array('type' => 'boolean', 'default' => false),
				'restore'         => array('type' => 'boolean', 'default' => false),
			),
		));

		// Badge counts (open flags + pending users)
		register_rest_route($this->namespace, '/moderation/counts', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array($this, 'get_counts'),
			'permission_callback' => array($this, 'check_moderation_access'),
		));

		// Get moderation settings
		register_rest_route($this->namespace, '/moderation/settings', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array($this, 'get_settings'),
			'permission_callback' => array($this, 'check_moderation_access'),
		));

		// Update moderation settings
		register_rest_route($this->namespace, '/moderation/settings', array(
			'methods'             => WP_REST_Server::EDITABLE,
			'callback'            => array($this, 'update_settings'),
			'permission_callback' => array($this, 'check_moderation_access'),
		));

		// Mark mod alerts as read
		register_rest_route($this->namespace, '/moderation/alerts/read', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array($this, 'mark_alerts_read'),
			'permission_callback' => array($this, 'check_moderation_access'),
		));
	}

	/**
	 * Permission: requires review_zaobank_flags capability.
	 */
	public function check_moderation_access($request) {
		$auth = $this->check_authentication($request);
		if (is_wp_error($auth)) {
			return $auth;
		}

		if (!ZAOBank_Security::can_review_flags()) {
			return $this->error_response(
				'rest_forbidden',
				__('Moderation access required.', 'zaobank'),
				403
			);
		}

		return true;
	}

	/**
	 * GET /moderation/users
	 */
	public function get_users($request) {
		$pagination = $this->get_pagination_params($request);
		$search     = sanitize_text_field($request->get_param('q'));
		$role       = sanitize_key($request->get_param('role'));
		$sort       = sanitize_key($request->get_param('sort'));

		$args = array(
			'number'  => $pagination['per_page'],
			'offset'  => ($pagination['page'] - 1) * $pagination['per_page'],
			'orderby' => 'user_registered',
			'order'   => 'DESC',
		);

		if (!empty($search)) {
			$args['search']         = '*' . $search . '*';
			$args['search_columns'] = array('user_login', 'user_email', 'display_name');
		}

		if (!empty($role)) {
			$args['role'] = $role;
		}

		if ($sort === 'name') {
			$args['orderby'] = 'display_name';
			$args['order']   = 'ASC';
		}

		$query = new WP_User_Query($args);
		$users = $query->get_results();
		$total = $query->get_total();

		// Get flag counts in a single query
		$flag_counts = $this->get_user_flag_counts(wp_list_pluck($users, 'ID'));

		$data = array();
		foreach ($users as $user) {
			$roles = $user->roles;
			$primary_role = !empty($roles) ? reset($roles) : '';

			$data[] = array(
				'id'              => $user->ID,
				'display_name'    => $user->display_name,
				'email'           => $user->user_email,
				'avatar_url'      => ZAOBank_Helpers::get_user_avatar_url($user->ID, 40),
				'role'            => $primary_role,
				'role_label'      => $this->get_role_label($primary_role),
				'user_registered' => $user->user_registered,
				'flag_count'      => isset($flag_counts[$user->ID]) ? (int) $flag_counts[$user->ID] : 0,
			);
		}

		return $this->success_response(array(
			'users' => $data,
			'total' => (int) $total,
			'pages' => ceil($total / $pagination['per_page']),
			'page'  => $pagination['page'],
		));
	}

	/**
	 * PUT /moderation/users/{id}/role
	 */
	public function update_user_role($request) {
		$user_id  = (int) $request['id'];
		$new_role = sanitize_key($request->get_param('role'));

		// Validate target role
		$allowed_roles = array('member', 'member_limited', 'leadership_team');
		if (!in_array($new_role, $allowed_roles, true)) {
			return $this->error_response(
				'invalid_role',
				__('Invalid role specified.', 'zaobank')
			);
		}

		$user = get_userdata($user_id);
		if (!$user) {
			return $this->error_response(
				'user_not_found',
				__('User not found.', 'zaobank'),
				404
			);
		}

		// Prevent self-modification
		if ($user_id === get_current_user_id()) {
			return $this->error_response(
				'self_modification',
				__('You cannot change your own role.', 'zaobank')
			);
		}

		// Prevent modifying administrators
		if (in_array('administrator', $user->roles, true)) {
			return $this->error_response(
				'cannot_modify_admin',
				__('Administrator roles cannot be changed here.', 'zaobank')
			);
		}

		// Only administrators can promote to leadership_team
		if ($new_role === 'leadership_team' && !current_user_can('manage_options')) {
			return $this->error_response(
				'insufficient_permissions',
				__('Only administrators can promote users to the leadership team.', 'zaobank'),
				403
			);
		}

		$old_role = !empty($user->roles) ? reset($user->roles) : '';
		$user->set_role($new_role);

		ZAOBank_Security::log_security_event('role_changed', array(
			'target_user_id' => $user_id,
			'old_role'       => $old_role,
			'new_role'       => $new_role,
			'changed_by'     => get_current_user_id(),
		));

		return $this->success_response(array(
			'message'  => __('Role updated successfully.', 'zaobank'),
			'user_id'  => $user_id,
			'old_role' => $old_role,
			'new_role' => $new_role,
		));
	}

	/**
	 * GET /moderation/flags
	 */
	public function get_flags($request) {
		global $wpdb;

		$pagination = $this->get_pagination_params($request);
		$status     = sanitize_key($request->get_param('status') ?: 'open');
		$type       = sanitize_key($request->get_param('type'));
		$table      = ZAOBank_Database::get_flags_table();
		$allowed_statuses = array('open', 'under_review', 'resolved', 'removed', 'restored');

		if ($status === 'all') {
			$where = '1=1';
		} else {
			if (!in_array($status, $allowed_statuses, true)) {
				$status = 'open';
			}
			$where = $wpdb->prepare('status = %s', $status);
		}

		if (!empty($type)) {
			$where .= $wpdb->prepare(' AND flagged_item_type = %s', $type);
		}

		// Get total count
		$total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE $where");

		// Get flags with pagination
		$flags = $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM $table WHERE $where ORDER BY created_at DESC LIMIT %d OFFSET %d",
			$pagination['per_page'],
			($pagination['page'] - 1) * $pagination['per_page']
		));

		$data = array();
		foreach ($flags as $flag) {
			$data[] = $this->enrich_flag($flag);
		}

		return $this->success_response(array(
			'flags' => $data,
			'total' => $total,
			'pages' => ceil($total / $pagination['per_page']),
			'page'  => $pagination['page'],
		));
	}

	/**
	 * PUT /moderation/flags/{id}
	 */
	public function update_flag($request) {
		$flag_id         = (int) $request['id'];
		$action          = sanitize_key($request->get_param('action'));
		$status          = sanitize_key((string) $request->get_param('status'));
		$resolution_note = sanitize_textarea_field((string) $request->get_param('resolution_note'));
		$remove          = (bool) $request->get_param('remove');
		$restore         = (bool) $request->get_param('restore');

		// Backward compatibility for old clients.
		if (empty($action)) {
			if ($restore) {
				$action = 'restore';
			} elseif ($remove) {
				$action = 'remove';
			} elseif (!empty($status)) {
				$action = $status;
			}
		}

		switch ($action) {
			case 'under_review':
				$result = ZAOBank_Flags::update_flag_status($flag_id, 'under_review', $resolution_note);
				if (is_wp_error($result)) {
					return $this->error_response($result->get_error_code(), $result->get_error_message());
				}
				$message = __('Flag marked under review.', 'zaobank');
				break;

			case 'remove':
			case 'removed':
				$remove_result = ZAOBank_Flags::remove_content($flag_id);
				if (is_wp_error($remove_result)) {
					return $this->error_response($remove_result->get_error_code(), $remove_result->get_error_message());
				}

				$note = $resolution_note ? $resolution_note : __('Removed by moderator after review.', 'zaobank');
				$status_result = ZAOBank_Flags::update_flag_status($flag_id, 'removed', $note);
				if (is_wp_error($status_result)) {
					return $this->error_response($status_result->get_error_code(), $status_result->get_error_message());
				}
				$message = __('Content removed and report logged.', 'zaobank');
				break;

			case 'restore':
			case 'restored':
				$restore_result = ZAOBank_Flags::restore_content($flag_id);
				if (is_wp_error($restore_result)) {
					return $this->error_response($restore_result->get_error_code(), $restore_result->get_error_message());
				}

				$note = $resolution_note ? $resolution_note : __('Restored after moderator verification.', 'zaobank');
				$status_result = ZAOBank_Flags::update_flag_status($flag_id, 'restored', $note);
				if (is_wp_error($status_result)) {
					return $this->error_response($status_result->get_error_code(), $status_result->get_error_message());
				}
				$message = __('Content restored and report logged.', 'zaobank');
				break;

			case 'resolve':
			case 'resolved':
				$note = $resolution_note ? $resolution_note : __('Closed after moderator review.', 'zaobank');
				$result = ZAOBank_Flags::update_flag_status($flag_id, 'resolved', $note);
				if (is_wp_error($result)) {
					return $this->error_response($result->get_error_code(), $result->get_error_message());
				}
				$message = __('Flag resolved.', 'zaobank');
				break;

			default:
				return $this->error_response(
					'invalid_flag_action',
					__('Invalid moderation action.', 'zaobank'),
					400
				);
		}

		return $this->success_response(array(
			'message' => $message,
			'action'  => $action,
		));
	}

	/**
	 * GET /moderation/counts
	 */
	public function get_counts($request) {
		global $wpdb;
		$flags_table = ZAOBank_Database::get_flags_table();

		$open_flags = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM $flags_table WHERE status = 'open'"
		);

		$under_review = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM $flags_table WHERE status = 'under_review'"
		);

		// Count member_limited users (pending verification)
		$pending_users = count(get_users(array(
			'role'   => 'member_limited',
			'fields' => 'ID',
		)));

		return $this->success_response(array(
			'open_flags'    => $open_flags,
			'under_review'  => $under_review,
			'pending_users' => $pending_users,
			'total'         => $open_flags + $under_review,
		));
	}

	/**
	 * GET /moderation/settings
	 */
	public function get_settings($request) {
		return $this->success_response(array(
			'auto_hide_flagged'          => (bool) get_option('zaobank_auto_hide_flagged', true),
			'flag_threshold'             => (int) get_option('zaobank_flag_threshold', 1),
			'auto_downgrade_threshold'   => (int) get_option('zaobank_flag_auto_downgrade_threshold', 3),
			'flag_reasons'               => ZAOBank_Flags::get_reason_options(),
		));
	}

	/**
	 * PUT /moderation/settings
	 */
	public function update_settings($request) {
		$params = $request->get_params();

		if (isset($params['auto_hide_flagged'])) {
			update_option('zaobank_auto_hide_flagged', (bool) $params['auto_hide_flagged']);
		}

		if (isset($params['flag_threshold'])) {
			update_option('zaobank_flag_threshold', max(1, (int) $params['flag_threshold']));
		}

		if (isset($params['auto_downgrade_threshold'])) {
			update_option('zaobank_flag_auto_downgrade_threshold', max(1, (int) $params['auto_downgrade_threshold']));
		}

		ZAOBank_Security::log_security_event('mod_settings_updated', array(
			'changed_by' => get_current_user_id(),
			'params'     => array_keys($params),
		));

		return $this->success_response(array(
			'message' => __('Settings saved.', 'zaobank'),
		));
	}

	/**
	 * POST /moderation/alerts/read
	 */
	public function mark_alerts_read($request) {
		$count = ZAOBank_Messages::mark_type_read(get_current_user_id(), 'mod_alert');

		return $this->success_response(array(
			'marked' => $count,
		));
	}

	/**
	 * Get flag counts per user in a single query.
	 */
	private function get_user_flag_counts($user_ids) {
		if (empty($user_ids)) {
			return array();
		}

		global $wpdb;
		$table        = ZAOBank_Database::get_flags_table();
		$placeholders = implode(',', array_fill(0, count($user_ids), '%d'));

		$results = $wpdb->get_results($wpdb->prepare(
			"SELECT flagged_user_id, COUNT(*) as cnt
			 FROM $table
			 WHERE flagged_user_id IN ($placeholders)
			   AND status IN ('open', 'under_review')
			 GROUP BY flagged_user_id",
			...$user_ids
		));

		$counts = array();
		foreach ($results as $row) {
			$counts[(int) $row->flagged_user_id] = (int) $row->cnt;
		}

		return $counts;
	}

	/**
	 * Enrich a flag with user and item details.
	 */
	private function enrich_flag($flag) {
		$flagged_user_id = !empty($flag->flagged_user_id)
			? (int) $flag->flagged_user_id
			: (($flag->flagged_item_type === 'user') ? (int) $flag->flagged_item_id : null);

		$data = array(
			'id'               => (int) $flag->id,
			'flagged_item_type' => $flag->flagged_item_type,
			'flagged_item_id'  => (int) $flag->flagged_item_id,
			'flagged_user_id'  => $flagged_user_id,
			'reporter_user_id' => (int) $flag->reporter_user_id,
			'reason_slug'      => $flag->reason_slug,
			'reason_label'     => $this->get_reason_label($flag->reason_slug),
			'context_note'     => $flag->context_note,
			'status'           => $flag->status,
			'created_at'       => $flag->created_at,
			'reviewed_at'      => $flag->reviewed_at,
			'reviewer_user_id' => $flag->reviewer_user_id ? (int) $flag->reviewer_user_id : null,
			'resolution_note'  => $flag->resolution_note,
		);

		// Reporter info
		$data['reporter_name']   = get_the_author_meta('display_name', $flag->reporter_user_id);
		$data['reporter_avatar'] = ZAOBank_Helpers::get_user_avatar_url($flag->reporter_user_id, 32);
		$data['reviewer_name']   = $flag->reviewer_user_id ? get_the_author_meta('display_name', $flag->reviewer_user_id) : '';

		// Flagged user info
		if ($flagged_user_id) {
			$data['flagged_user_name']   = get_the_author_meta('display_name', $flagged_user_id);
			$data['flagged_user_avatar'] = ZAOBank_Helpers::get_user_avatar_url($flagged_user_id, 32);
		}

		// Item preview
		$data['item_preview'] = $this->get_item_preview($flag->flagged_item_type, $flag->flagged_item_id);

		return $data;
	}

	/**
	 * Get a preview of the flagged item.
	 */
	private function get_item_preview($type, $id) {
		switch ($type) {
			case 'job':
				$job = get_post($id);
				return $job ? $job->post_title : __('(deleted job)', 'zaobank');

			case 'appreciation':
				global $wpdb;
				$table = ZAOBank_Database::get_appreciations_table();
				$message = $wpdb->get_var($wpdb->prepare(
					"SELECT message FROM $table WHERE id = %d", $id
				));
				return $message ? wp_trim_words($message, 20) : __('(deleted appreciation)', 'zaobank');

			case 'message':
				global $wpdb;
				$table = ZAOBank_Database::get_messages_table();
				$message = $wpdb->get_var($wpdb->prepare(
					"SELECT message FROM $table WHERE id = %d", $id
				));
				return $message ? wp_trim_words(wp_strip_all_tags($message), 20) : __('(deleted message)', 'zaobank');

			case 'user':
				$user = get_userdata($id);
				return $user ? $user->display_name : __('(deleted user)', 'zaobank');

			default:
				return '';
		}
	}

	/**
	 * Get human-readable reason label.
	 */
	private function get_reason_label($slug) {
		return ZAOBank_Flags::get_reason_label($slug);
	}

	/**
	 * Get human-readable role label.
	 */
	private function get_role_label($role) {
		$labels = array(
			'administrator'   => __('Admin', 'zaobank'),
			'leadership_team' => __('Leadership', 'zaobank'),
			'member'          => __('Member', 'zaobank'),
			'member_limited'  => __('Limited', 'zaobank'),
			'editor'          => __('Editor', 'zaobank'),
			'subscriber'      => __('Subscriber', 'zaobank'),
		);

		return isset($labels[$role]) ? $labels[$role] : ucwords(str_replace('_', ' ', $role));
	}
}
