<?php
/**
 * Security and permissions management.
 */
class ZAOBank_Security {

	/**
	 * Verify nonce for REST API requests.
	 */
	public static function verify_nonce($request) {
		$nonce = $request->get_header('X-WP-Nonce');

		if (!$nonce) {
			return new WP_Error(
				'missing_nonce',
				__('Missing security token', 'zaobank'),
				array('status' => 403)
			);
		}

		if (!wp_verify_nonce($nonce, 'wp_rest')) {
			return new WP_Error(
				'invalid_nonce',
				__('Invalid security token', 'zaobank'),
				array('status' => 403)
			);
		}

		return true;
	}

	/**
	 * Check if user is authenticated.
	 */
	public static function is_user_authenticated() {
		return is_user_logged_in();
	}

	/**
	 * Get roles allowed to access member-only app actions.
	 */
	public static function get_member_access_roles() {
		$roles = get_option('zaobank_message_search_roles', array('member'));

		$roles = apply_filters('zaobank_member_access_roles', $roles);
		$roles = apply_filters('zaobank_message_search_roles', $roles);

		$valid_roles = array();
		foreach ((array) $roles as $role) {
			if (wp_roles()->is_role($role)) {
				$valid_roles[] = $role;
			}
		}

		return array_values(array_unique($valid_roles));
	}

	/**
	 * Check if a user has one of the member access roles.
	 */
	public static function user_has_member_access($user_id = null) {
		if (!$user_id) {
			$user_id = get_current_user_id();
		}

		if (!$user_id) {
			return false;
		}

		$user = get_userdata($user_id);
		if (!$user || empty($user->roles)) {
			return false;
		}

		$allowed_roles = self::get_member_access_roles();
		if (empty($allowed_roles)) {
			return false;
		}

		return (bool) array_intersect($user->roles, $allowed_roles);
	}

	/**
	 * Check if user can create jobs.
	 */
	public static function can_create_job($user_id = null) {
		if (!$user_id) {
			$user_id = get_current_user_id();
		}

		return user_can($user_id, 'edit_timebank_jobs');
	}

	/**
	 * Check if user can edit a specific job.
	 */
	public static function can_edit_job($job_id, $user_id = null) {
		if (!$user_id) {
			$user_id = get_current_user_id();
		}

		$job = get_post($job_id);

		if (!$job || $job->post_type !== 'timebank_job') {
			return false;
		}

		// Job author can edit
		if ($job->post_author == $user_id) {
			return true;
		}

		// Provider can edit certain fields
		$provider_id = get_post_meta($job_id, 'provider_user_id', true);
		if ($provider_id == $user_id) {
			return true;
		}

		// Admins can edit
		return user_can($user_id, 'edit_others_timebank_jobs');
	}

	/**
	 * Check if user can claim a job.
	 */
	public static function can_claim_job($job_id, $user_id = null) {
		if (!$user_id) {
			$user_id = get_current_user_id();
		}

		$job = get_post($job_id);

		if (!$job || $job->post_type !== 'timebank_job') {
			return false;
		}

		// Can't claim your own job
		if ($job->post_author == $user_id) {
			return false;
		}

		// Job must be open
		if ($job->post_status !== 'publish' || get_post_meta($job_id, 'provider_user_id', true)) {
			return false;
		}

		return true;
	}

	/**
	 * Check if user can complete a job.
	 */
	public static function can_complete_job($job_id, $user_id = null) {
		if (!$user_id) {
			$user_id = get_current_user_id();
		}

		$job = get_post($job_id);

		if (!$job || $job->post_type !== 'timebank_job') {
			return false;
		}

		// Only requester can mark as complete
		return $job->post_author == $user_id;
	}

	/**
	 * Check if user can review flags.
	 */
	public static function can_review_flags($user_id = null) {
		if (!$user_id) {
			$user_id = get_current_user_id();
		}

		return user_can($user_id, 'review_zaobank_flags');
	}

	/**
	 * Sanitize input data.
	 */
	public static function sanitize_job_data($data) {
		$sanitized = array();

		if (isset($data['title'])) {
			$sanitized['title'] = sanitize_text_field($data['title']);
		}

		if (isset($data['description'])) {
			$sanitized['description'] = wp_kses_post($data['description']);
		}

		if (isset($data['hours'])) {
			$sanitized['hours'] = floatval($data['hours']);
		}

		if (isset($data['location'])) {
			$sanitized['location'] = sanitize_text_field($data['location']);
		}

		if (isset($data['skills_required'])) {
			$sanitized['skills_required'] = sanitize_text_field($data['skills_required']);
		}

		if (isset($data['virtual_ok'])) {
			$sanitized['virtual_ok'] = (bool) $data['virtual_ok'];
		}

		if (isset($data['regions']) && is_array($data['regions'])) {
			$sanitized['regions'] = array_map('intval', $data['regions']);
		}

		return $sanitized;
	}

	/**
	 * Rate limiting check.
	 */
	public static function check_rate_limit($action, $user_id = null, $limit = 10, $period = 3600) {
		if (!$user_id) {
			$user_id = get_current_user_id();
		}

		$transient_key = "zaobank_rate_limit_{$action}_{$user_id}";
		$count = get_transient($transient_key);

		if ($count === false) {
			set_transient($transient_key, 1, $period);
			return true;
		}

		if ($count >= $limit) {
			return new WP_Error(
				'rate_limit_exceeded',
				__('You are doing this too frequently. Please try again later.', 'zaobank'),
				array('status' => 429)
			);
		}

		set_transient($transient_key, $count + 1, $period);
		return true;
	}

	/**
	 * Validate hours value.
	 */
	public static function validate_hours($hours) {
		if (!is_numeric($hours)) {
			return new WP_Error(
				'invalid_hours',
				__('Hours must be a number', 'zaobank')
			);
		}

		$hours = floatval($hours);

		if ($hours < 0.25) {
			return new WP_Error(
				'invalid_hours',
				__('Hours must be at least 0.25', 'zaobank')
			);
		}

		if ($hours > 100) {
			return new WP_Error(
				'invalid_hours',
				__('Hours cannot exceed 100', 'zaobank')
			);
		}

		return true;
	}

	/**
	 * Check if content should be visible based on flags.
	 */
	public static function is_content_visible($item_type, $item_id) {
		global $wpdb;
		$flags_table = ZAOBank_Database::get_flags_table();

		// Check if there are active flags
		$flag_count = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM $flags_table 
            WHERE flagged_item_type = %s 
            AND flagged_item_id = %d 
            AND status = 'open'",
			$item_type,
			$item_id
		));

		// Auto-hide if flagged and auto-hide is enabled
		if ($flag_count > 0 && get_option('zaobank_auto_hide_flagged', true)) {
			return false;
		}

		return true;
	}

	/**
	 * Log security event (for audit trail).
	 */
	public static function log_security_event($event_type, $data = array()) {
		if (!get_option('zaobank_enable_security_logging', false)) {
			return;
		}

		$log_entry = array(
			'timestamp' => wp_date('Y-m-d H:i:s'),
			'user_id' => get_current_user_id(),
			'event_type' => $event_type,
			'data' => $data,
			'ip_address' => self::get_client_ip(),
			'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
		);

		// Store in option or custom table
		$logs = get_option('zaobank_security_logs', array());
		$logs[] = $log_entry;

		// Keep only last 1000 entries
		if (count($logs) > 1000) {
			$logs = array_slice($logs, -1000);
		}

		update_option('zaobank_security_logs', $logs);
	}

	/**
	 * Get client IP address.
	 */
	private static function get_client_ip() {
		$ip = '';

		if (isset($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif (isset($_SERVER['REMOTE_ADDR'])) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		return sanitize_text_field($ip);
	}
}
