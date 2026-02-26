<?php
/**
 * Notification preferences and delivery helpers.
 */
class ZAOBank_Notifications {

	const DIGEST_HOOK = 'zaobank_process_notification_digests';

	/**
	 * Ensure the digest cron event exists.
	 */
	public function ensure_cron_scheduled() {
		if (!wp_next_scheduled(self::DIGEST_HOOK)) {
			wp_schedule_event(time() + 300, 'hourly', self::DIGEST_HOOK);
		}
	}

	/**
	 * Handle newly-created message events.
	 */
	public function handle_message_created($message_id, $message_data = array()) {
		$message = ZAOBank_Messages::get_message((int) $message_id);
		if (!$message) {
			return;
		}

		$to_user_id = isset($message['to_user_id']) ? (int) $message['to_user_id'] : 0;
		$from_user_id = isset($message['from_user_id']) ? (int) $message['from_user_id'] : 0;
		$message_type = isset($message['message_type']) ? sanitize_key($message['message_type']) : 'direct';

		if (!$to_user_id || ($to_user_id === $from_user_id)) {
			return;
		}

		$settings = self::get_user_settings($to_user_id);

		// Job updates have their own toggle to avoid inbox noise.
		if ($message_type === 'job_update') {
			if (!empty($settings['job_updates_email'])) {
				$this->send_job_update_email($to_user_id, $message);
			}
			return;
		}

		if (!in_array($message_type, array('direct', 'job_request', 'job_offer'), true)) {
			return;
		}

		$channels = isset($settings['message_notification_channels']) && is_array($settings['message_notification_channels'])
			? $settings['message_notification_channels']
			: array('in_app');

		$has_external = array_intersect($channels, array('email', 'sms', 'discord'));
		if (empty($has_external)) {
			return;
		}

		if (in_array('email', $channels, true)) {
			$this->send_new_message_email($to_user_id, $message);
		}

		if (in_array('sms', $channels, true)) {
			$this->send_sms_notification($to_user_id, $this->build_sms_message_text($message), array(
				'type' => 'message',
				'message_id' => $message_id
			));
		}

		if (in_array('discord', $channels, true)) {
			$this->send_discord_notification($to_user_id, $this->build_discord_message_text($message), array(
				'type' => 'message',
				'message_id' => $message_id
			));
		}
	}

	/**
	 * Handle newly-created appreciations.
	 */
	public function handle_appreciation_created($appreciation_id, $appreciation_data = array()) {
		$to_user_id = isset($appreciation_data['to_user_id']) ? (int) $appreciation_data['to_user_id'] : 0;
		if (!$to_user_id) {
			return;
		}

		$settings = self::get_user_settings($to_user_id);
		if (empty($settings['appreciations_email'])) {
			return;
		}

		$from_user_id = isset($appreciation_data['from_user_id']) ? (int) $appreciation_data['from_user_id'] : 0;
		$from_name = $from_user_id ? get_the_author_meta('display_name', $from_user_id) : __('A community member', 'zaobank');
		$tag = isset($appreciation_data['tag_slug']) ? sanitize_text_field((string) $appreciation_data['tag_slug']) : '';
		$message = isset($appreciation_data['message']) ? wp_strip_all_tags((string) $appreciation_data['message']) : '';

		$urls = ZAOBank_Shortcodes::get_page_urls();
		$appreciations_url = isset($urls['appreciations']) ? $urls['appreciations'] : home_url('/');

		$subject = __('New appreciation', 'zaobank');
		$lines = array(
			sprintf(__('%s sent you an appreciation.', 'zaobank'), $from_name),
		);
		if ($tag !== '') {
			$lines[] = sprintf(__('Tag: %s', 'zaobank'), $tag);
		}
		if ($message !== '') {
			$lines[] = '';
			$lines[] = $message;
		}
		$lines[] = '';
		$lines[] = sprintf(__('View appreciations: %s', 'zaobank'), $appreciations_url);

		$this->send_email($to_user_id, $subject, implode("\n", $lines));
	}

	/**
	 * Cron callback: process all digest sends.
	 */
	public function process_scheduled_digests() {
		$users = get_users(array(
			'fields' => array('ID'),
			'number' => -1
		));

		foreach ($users as $user) {
			$user_id = (int) $user->ID;
			if ($user_id <= 0) {
				continue;
			}
			$this->maybe_send_message_digest($user_id);
			$this->maybe_send_jobs_digest($user_id);
		}
	}

	/**
	 * Return default user settings.
	 */
	public static function get_default_settings() {
		return array(
			'message_notification_mode' => 'in_app',
			'message_notification_channels' => array('in_app'),
			'directory_visible' => true,
			'available_for_requests' => true,
			'job_updates_email' => true,
			'appreciations_email' => true,
			'jobs_digest_enabled' => false,
			'jobs_digest_frequency' => 'weekly',
			'jobs_digest_limit' => 10,
			'jobs_digest_regions' => array(),
			'jobs_digest_job_types' => array(),
		);
	}

	/**
	 * Return normalized settings for a user.
	 */
	public static function get_user_settings($user_id) {
		$defaults = self::get_default_settings();

		$legacy_mode = sanitize_key((string) get_user_meta($user_id, 'zaobank_message_notification_mode', true));
		$raw_channels = get_user_meta($user_id, 'zaobank_message_notification_channels', true);
		$channels = self::normalize_message_channels($raw_channels, $legacy_mode);
		$mode = self::channels_to_legacy_mode($channels);

		$directory_visible_raw = get_user_meta($user_id, 'zaobank_directory_visible', true);
		$directory_visible = ($directory_visible_raw === '' || $directory_visible_raw === null)
			? true
			: self::to_bool($directory_visible_raw);

		$available_raw = get_user_meta($user_id, 'user_available_for_requests', true);
		$available_for_requests = ($available_raw === '' || $available_raw === null)
			? true
			: self::to_bool($available_raw);

		$job_updates_raw = get_user_meta($user_id, 'zaobank_job_updates_email', true);
		$job_updates_email = ($job_updates_raw === '' || $job_updates_raw === null)
			? true
			: self::to_bool($job_updates_raw);

		$appreciations_raw = get_user_meta($user_id, 'zaobank_appreciations_email', true);
		$appreciations_email = ($appreciations_raw === '' || $appreciations_raw === null)
			? true
			: self::to_bool($appreciations_raw);

		$jobs_digest_enabled_raw = get_user_meta($user_id, 'zaobank_jobs_digest_enabled', true);
		$jobs_digest_enabled = ($jobs_digest_enabled_raw === '' || $jobs_digest_enabled_raw === null)
			? false
			: self::to_bool($jobs_digest_enabled_raw);

		$jobs_digest_frequency = sanitize_key((string) get_user_meta($user_id, 'zaobank_jobs_digest_frequency', true));
		if (!in_array($jobs_digest_frequency, self::get_digest_frequency_values(), true)) {
			$jobs_digest_frequency = $defaults['jobs_digest_frequency'];
		}

		$jobs_digest_limit = (int) get_user_meta($user_id, 'zaobank_jobs_digest_limit', true);
		if ($jobs_digest_limit < 1) {
			$jobs_digest_limit = $defaults['jobs_digest_limit'];
		}
		if ($jobs_digest_limit > 50) {
			$jobs_digest_limit = 50;
		}

		$jobs_digest_regions = get_user_meta($user_id, 'zaobank_jobs_digest_regions', true);
		if (!is_array($jobs_digest_regions)) {
			$jobs_digest_regions = array();
		}
		$jobs_digest_regions = array_values(array_unique(array_filter(array_map('intval', $jobs_digest_regions))));

		$jobs_digest_job_types = get_user_meta($user_id, 'zaobank_jobs_digest_job_types', true);
		if (!is_array($jobs_digest_job_types)) {
			$jobs_digest_job_types = array();
		}
		$jobs_digest_job_types = array_values(array_unique(array_filter(array_map('intval', $jobs_digest_job_types))));

		return array(
			'message_notification_mode' => $mode,
			'message_notification_channels' => $channels,
			'directory_visible' => $directory_visible,
			'available_for_requests' => $available_for_requests,
			'job_updates_email' => $job_updates_email,
			'appreciations_email' => $appreciations_email,
			'jobs_digest_enabled' => $jobs_digest_enabled,
			'jobs_digest_frequency' => $jobs_digest_frequency,
			'jobs_digest_limit' => $jobs_digest_limit,
			'jobs_digest_regions' => $jobs_digest_regions,
			'jobs_digest_job_types' => $jobs_digest_job_types,
		);
	}

	/**
	 * Persist notification settings for a user.
	 */
	public static function update_user_settings($user_id, $params) {
		if (isset($params['message_notification_channels'])) {
			$channels = self::normalize_message_channels($params['message_notification_channels']);
			$mode = self::channels_to_legacy_mode($channels);
			update_user_meta($user_id, 'zaobank_message_notification_channels', $channels);
			update_user_meta($user_id, 'zaobank_message_notification_mode', $mode);
		}

		if (isset($params['message_notification_mode'])) {
			$mode = sanitize_key((string) $params['message_notification_mode']);
			if (!in_array($mode, self::get_message_mode_values(), true)) {
				return new WP_Error(
					'invalid_message_notification_mode',
					__('Invalid message notification mode.', 'zaobank')
				);
			}
			$channels = self::legacy_mode_to_channels($mode);
			$channels = self::normalize_message_channels($channels, $mode);
			update_user_meta($user_id, 'zaobank_message_notification_mode', $mode);
			update_user_meta($user_id, 'zaobank_message_notification_channels', $channels);
		}

		if (isset($params['directory_visible'])) {
			update_user_meta($user_id, 'zaobank_directory_visible', self::to_bool($params['directory_visible']) ? 1 : 0);
		}

		if (isset($params['available_for_requests'])) {
			update_user_meta($user_id, 'user_available_for_requests', self::to_bool($params['available_for_requests']) ? 1 : 0);
		}

		if (isset($params['job_updates_email'])) {
			update_user_meta($user_id, 'zaobank_job_updates_email', self::to_bool($params['job_updates_email']) ? 1 : 0);
		}

		if (isset($params['appreciations_email'])) {
			update_user_meta($user_id, 'zaobank_appreciations_email', self::to_bool($params['appreciations_email']) ? 1 : 0);
		}

		if (isset($params['jobs_digest_enabled'])) {
			update_user_meta($user_id, 'zaobank_jobs_digest_enabled', self::to_bool($params['jobs_digest_enabled']) ? 1 : 0);
		}

		if (isset($params['jobs_digest_frequency'])) {
			$frequency = sanitize_key((string) $params['jobs_digest_frequency']);
			if (!in_array($frequency, self::get_digest_frequency_values(), true)) {
				return new WP_Error(
					'invalid_jobs_digest_frequency',
					__('Invalid jobs digest frequency.', 'zaobank')
				);
			}
			update_user_meta($user_id, 'zaobank_jobs_digest_frequency', $frequency);
		}

		if (isset($params['jobs_digest_limit'])) {
			$limit = (int) $params['jobs_digest_limit'];
			if ($limit < 1) {
				$limit = 1;
			}
			if ($limit > 50) {
				$limit = 50;
			}
			update_user_meta($user_id, 'zaobank_jobs_digest_limit', $limit);
		}

		if (isset($params['jobs_digest_regions'])) {
			$regions = is_array($params['jobs_digest_regions']) ? $params['jobs_digest_regions'] : array();
			$regions = array_values(array_unique(array_filter(array_map('intval', $regions))));
			update_user_meta($user_id, 'zaobank_jobs_digest_regions', $regions);
		}

		if (isset($params['jobs_digest_job_types'])) {
			$types = is_array($params['jobs_digest_job_types']) ? $params['jobs_digest_job_types'] : array();
			$types = array_values(array_unique(array_filter(array_map('intval', $types))));
			update_user_meta($user_id, 'zaobank_jobs_digest_job_types', $types);
		}

		return true;
	}

	/**
	 * List valid values for message notification mode.
	 */
	public static function get_message_mode_values() {
		return array('in_app', 'email_instant', 'sms_instant', 'discord_instant', 'daily_digest', 'weekly_digest', 'off');
	}

	/**
	 * List valid values for message notification channels.
	 */
	public static function get_message_channel_values() {
		return array('in_app', 'sms', 'email', 'discord');
	}

	/**
	 * Human labels for message notification channels.
	 */
	public static function get_message_channel_labels() {
		return array(
			'in_app' => __('In app only', 'zaobank'),
			'sms' => __('Text (SMS)', 'zaobank'),
			'email' => __('Email', 'zaobank'),
			'discord' => __('Discord (coming soon)', 'zaobank'),
		);
	}

	/**
	 * Human labels for message notification mode options.
	 */
	public static function get_message_mode_labels() {
		return array(
			'in_app' => __('In-app only', 'zaobank'),
			'email_instant' => __('Email instantly', 'zaobank'),
			'sms_instant' => __('Text (SMS) instantly', 'zaobank'),
			'discord_instant' => __('Discord instantly', 'zaobank'),
			'daily_digest' => __('Daily message summary (email)', 'zaobank'),
			'weekly_digest' => __('Weekly message summary (email)', 'zaobank'),
			'off' => __('No external notifications', 'zaobank'),
		);
	}

	/**
	 * Normalize channel payload and enforce in-app exclusivity rules.
	 *
	 * @param mixed  $channels    Array/string payload from request/meta.
	 * @param string $legacy_mode Optional legacy mode fallback.
	 * @return array
	 */
	private static function normalize_message_channels($channels, $legacy_mode = '') {
		if (!is_array($channels)) {
			if ($channels === null || $channels === '') {
				$channels = array();
			} else {
				$channels = array($channels);
			}
		}

		$allowed = self::get_message_channel_values();
		$normalized = array();
		foreach ($channels as $channel) {
			$value = sanitize_key((string) $channel);
			if (in_array($value, $allowed, true)) {
				$normalized[] = $value;
			}
		}

		$normalized = array_values(array_unique($normalized));

		if (empty($normalized) && $legacy_mode !== '') {
			$normalized = self::legacy_mode_to_channels($legacy_mode);
		}

		$external = array_values(array_intersect($normalized, array('sms', 'email', 'discord')));
		if (!empty($external)) {
			return $external;
		}

		return array('in_app');
	}

	/**
	 * Convert legacy mode into channel list.
	 */
	private static function legacy_mode_to_channels($mode) {
		$mode = sanitize_key((string) $mode);
		switch ($mode) {
			case 'email_instant':
			case 'daily_digest':
			case 'weekly_digest':
				return array('email');
			case 'sms_instant':
				return array('sms');
			case 'discord_instant':
				return array('discord');
			case 'off':
				return array('in_app');
			case 'in_app':
			default:
				return array('in_app');
		}
	}

	/**
	 * Convert channels to best-fit legacy mode.
	 */
	private static function channels_to_legacy_mode($channels) {
		$channels = is_array($channels) ? array_values($channels) : array();
		$channels = array_values(array_intersect($channels, array('sms', 'email', 'discord')));
		if (empty($channels)) {
			return 'in_app';
		}
		if (count($channels) === 1) {
			if ($channels[0] === 'email') {
				return 'email_instant';
			}
			if ($channels[0] === 'sms') {
				return 'sms_instant';
			}
			if ($channels[0] === 'discord') {
				return 'discord_instant';
			}
		}
		// Legacy mode is single-select; use email as best compatible fallback for mixed channels.
		return in_array('email', $channels, true) ? 'email_instant' : 'in_app';
	}

	/**
	 * Valid jobs digest frequencies.
	 */
	public static function get_digest_frequency_values() {
		return array('daily', 'weekly');
	}

	/**
	 * Human labels for jobs digest frequency.
	 */
	public static function get_digest_frequency_labels() {
		return array(
			'daily' => __('Daily', 'zaobank'),
			'weekly' => __('Weekly', 'zaobank'),
		);
	}

	/**
	 * Convert any value to bool consistently.
	 */
	private static function to_bool($value) {
		if (is_bool($value)) {
			return $value;
		}
		if (is_numeric($value)) {
			return ((int) $value) === 1;
		}
		$text = strtolower(trim((string) $value));
		return in_array($text, array('1', 'true', 'yes', 'on'), true);
	}

	/**
	 * Try to send SMS via external integration.
	 */
	private function send_sms_notification($user_id, $message, $context = array()) {
		$phone = trim((string) get_user_meta($user_id, 'user_phone', true));
		if ($phone === '') {
			return new WP_Error('sms_missing_phone', __('No phone number is set for SMS notifications.', 'zaobank'));
		}

		$payload = array(
			'user_id' => (int) $user_id,
			'phone' => $phone,
			'message' => (string) $message,
			'context' => (array) $context
		);

		$handled = apply_filters('zaobank_send_sms_notification', false, $payload, $user_id);
		if ($handled) {
			return true;
		}

		return new WP_Error('sms_not_configured', __('SMS provider is not configured.', 'zaobank'));
	}

	/**
	 * Try to send Discord notification via external integration.
	 */
	private function send_discord_notification($user_id, $message, $context = array()) {
		$discord_id = trim((string) get_user_meta($user_id, 'user_discord_id', true));
		if ($discord_id === '') {
			return new WP_Error('discord_missing_id', __('No Discord user ID is set for notifications.', 'zaobank'));
		}

		$payload = array(
			'user_id' => (int) $user_id,
			'discord_id' => $discord_id,
			'message' => (string) $message,
			'context' => (array) $context
		);

		$handled = apply_filters('zaobank_send_discord_notification', false, $payload, $user_id);
		if ($handled) {
			return true;
		}

		return new WP_Error('discord_not_configured', __('Discord notifications are not configured.', 'zaobank'));
	}

	/**
	 * Send new-message email.
	 */
	private function send_new_message_email($to_user_id, $message) {
		$sender_name = !empty($message['from_user_name']) ? $message['from_user_name'] : __('Someone', 'zaobank');
		$subject = sprintf(
			__('New message from %s', 'zaobank'),
			$sender_name
		);

		$conversation_url = $this->get_conversation_url((int) $message['from_user_id']);
		$body_lines = array(
			sprintf(__('You have a new message from %s.', 'zaobank'), $sender_name),
			'',
			wp_strip_all_tags((string) $message['message']),
			'',
			sprintf(__('Open conversation: %s', 'zaobank'), $conversation_url)
		);

		$this->send_email($to_user_id, $subject, implode("\n", $body_lines));
	}

	/**
	 * Send job-update email.
	 */
	private function send_job_update_email($to_user_id, $message) {
		$subject = __('Job notification', 'zaobank');
		$job_url = '';
		if (!empty($message['job_id'])) {
			$urls = ZAOBank_Shortcodes::get_page_urls();
			if (!empty($urls['jobs'])) {
				$job_url = add_query_arg('job_id', (int) $message['job_id'], $urls['jobs']);
			}
		}

		$lines = array(
			wp_strip_all_tags((string) $message['message']),
		);
		if ($job_url !== '') {
			$lines[] = '';
			$lines[] = sprintf(__('View job: %s', 'zaobank'), $job_url);
		}

		$this->send_email($to_user_id, $subject, implode("\n", $lines));
	}

	/**
	 * Send an email to a user.
	 */
	private function send_email($user_id, $subject, $body) {
		$user = get_userdata((int) $user_id);
		if (!$user || !is_email($user->user_email)) {
			return new WP_Error('invalid_email', __('User email is not available.', 'zaobank'));
		}

		$site_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
		$full_subject = sprintf('[%s] %s', $site_name, $subject);
		$headers = array('Content-Type: text/plain; charset=UTF-8');

		$sent = wp_mail($user->user_email, $full_subject, $body, $headers);
		if (!$sent) {
			return new WP_Error('email_send_failed', __('Failed to send email notification.', 'zaobank'));
		}

		return true;
	}

	/**
	 * Build short SMS notification text.
	 */
	private function build_sms_message_text($message) {
		$sender_name = !empty($message['from_user_name']) ? $message['from_user_name'] : __('Someone', 'zaobank');
		$text = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags((string) $message['message'])));
		if (strlen($text) > 90) {
			$text = substr($text, 0, 87) . '...';
		}
		return sprintf(__('New ZAO Bank message from %1$s: %2$s', 'zaobank'), $sender_name, $text);
	}

	/**
	 * Build Discord notification text.
	 */
	private function build_discord_message_text($message) {
		$sender_name = !empty($message['from_user_name']) ? $message['from_user_name'] : __('Someone', 'zaobank');
		$text = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags((string) $message['message'])));
		if (strlen($text) > 160) {
			$text = substr($text, 0, 157) . '...';
		}
		return sprintf(__('%1$s sent you a message: %2$s', 'zaobank'), $sender_name, $text);
	}

	/**
	 * Build a direct conversation URL.
	 */
	private function get_conversation_url($other_user_id) {
		$urls = ZAOBank_Shortcodes::get_page_urls();
		$messages_url = !empty($urls['messages']) ? $urls['messages'] : home_url('/');
		return add_query_arg('user_id', (int) $other_user_id, $messages_url);
	}

	/**
	 * Send message digest if due.
	 */
	private function maybe_send_message_digest($user_id) {
		$settings = self::get_user_settings($user_id);
		$mode = $settings['message_notification_mode'];

		if (!in_array($mode, array('daily_digest', 'weekly_digest'), true)) {
			return;
		}

		$interval = ($mode === 'weekly_digest') ? WEEK_IN_SECONDS : DAY_IN_SECONDS;
		$last_sent = get_user_meta($user_id, 'zaobank_message_digest_last_sent', true);
		$now = time();

		if ($last_sent) {
			$last_ts = strtotime($last_sent);
			if ($last_ts && ($now - $last_ts) < $interval) {
				return;
			}
			$since = gmdate('Y-m-d H:i:s', $last_ts);
		} else {
			$since = gmdate('Y-m-d H:i:s', $now - $interval);
		}

		$items = $this->get_message_digest_items($user_id, $since, 20);
		$sent_ok = true;
		if (!empty($items)) {
			$send_result = $this->send_message_digest_email($user_id, $items, $since);
			$sent_ok = !is_wp_error($send_result);
		}

		if ($sent_ok) {
			update_user_meta($user_id, 'zaobank_message_digest_last_sent', wp_date('Y-m-d H:i:s'));
		}
	}

	/**
	 * Fetch messages for digest.
	 */
	private function get_message_digest_items($user_id, $since, $limit = 20) {
		global $wpdb;
		$table = ZAOBank_Database::get_messages_table();

		$rows = $wpdb->get_results($wpdb->prepare(
			"SELECT id, from_user_id, message, message_type, created_at
			 FROM $table
			 WHERE to_user_id = %d
			   AND created_at > %s
			   AND message_type IN ('direct', 'job_request', 'job_offer')
			 ORDER BY created_at DESC
			 LIMIT %d",
			(int) $user_id,
			$since,
			(int) $limit
		));

		$items = array();
		foreach ($rows as $row) {
			$from_name = get_the_author_meta('display_name', (int) $row->from_user_id);
			$text = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags((string) $row->message)));
			if (strlen($text) > 140) {
				$text = substr($text, 0, 137) . '...';
			}
			$items[] = array(
				'from_user_id' => (int) $row->from_user_id,
				'from_name' => $from_name ? $from_name : __('Community member', 'zaobank'),
				'message' => $text,
				'created_at' => $row->created_at
			);
		}

		return $items;
	}

	/**
	 * Send message digest email.
	 */
	private function send_message_digest_email($user_id, $items, $since) {
		$subject = __('Message digest', 'zaobank');
		$urls = ZAOBank_Shortcodes::get_page_urls();
		$messages_url = !empty($urls['messages']) ? $urls['messages'] : home_url('/');

		$lines = array(
			__('Here is your recent message summary:', 'zaobank'),
			sprintf(__('Since: %s', 'zaobank'), wp_date('Y-m-d H:i', strtotime($since))),
			''
		);

		foreach ($items as $item) {
			$conversation_url = $this->get_conversation_url($item['from_user_id']);
			$lines[] = sprintf('- %1$s: %2$s', $item['from_name'], $item['message']);
			$lines[] = sprintf('  %s', $conversation_url);
		}

		$lines[] = '';
		$lines[] = sprintf(__('View all messages: %s', 'zaobank'), $messages_url);

		return $this->send_email($user_id, $subject, implode("\n", $lines));
	}

	/**
	 * Send open-jobs digest if due.
	 */
	private function maybe_send_jobs_digest($user_id) {
		$settings = self::get_user_settings($user_id);
		if (empty($settings['jobs_digest_enabled'])) {
			return;
		}

		$frequency = $settings['jobs_digest_frequency'];
		$interval = ($frequency === 'daily') ? DAY_IN_SECONDS : WEEK_IN_SECONDS;

		$last_sent = get_user_meta($user_id, 'zaobank_jobs_digest_last_sent', true);
		$now = time();
		if ($last_sent) {
			$last_ts = strtotime($last_sent);
			if ($last_ts && ($now - $last_ts) < $interval) {
				return;
			}
		}

		$jobs = $this->get_open_jobs_for_digest($user_id, $settings);
		$sent_ok = true;
		if (!empty($jobs)) {
			$send_result = $this->send_jobs_digest_email($user_id, $jobs, $settings);
			$sent_ok = !is_wp_error($send_result);
		}

		if ($sent_ok) {
			update_user_meta($user_id, 'zaobank_jobs_digest_last_sent', wp_date('Y-m-d H:i:s'));
		}
	}

	/**
	 * Get open jobs for user digest.
	 */
	private function get_open_jobs_for_digest($user_id, $settings) {
		$meta_query = array(
			'relation' => 'AND',
			array(
				'key' => 'provider_user_id',
				'compare' => 'NOT EXISTS'
			),
			array(
				'relation' => 'OR',
				array(
					'key' => 'visibility',
					'compare' => 'NOT EXISTS'
				),
				array(
					'key' => 'visibility',
					'value' => 'public',
					'compare' => '='
				)
			)
		);

		$tax_query = array();
		if (!empty($settings['jobs_digest_regions'])) {
			$tax_query[] = array(
				'taxonomy' => 'zaobank_region',
				'field' => 'term_id',
				'terms' => array_map('intval', $settings['jobs_digest_regions'])
			);
		}

		if (!empty($settings['jobs_digest_job_types'])) {
			$tax_query[] = array(
				'taxonomy' => 'zaobank_job_type',
				'field' => 'term_id',
				'terms' => array_map('intval', $settings['jobs_digest_job_types'])
			);
		}

		if (count($tax_query) > 1) {
			$tax_query['relation'] = 'AND';
		}

		$query_args = array(
			'post_type' => 'timebank_job',
			'post_status' => 'publish',
			'posts_per_page' => (int) $settings['jobs_digest_limit'],
			'post__not_in' => array(),
			'author__not_in' => array((int) $user_id),
			'meta_query' => $meta_query,
			'orderby' => 'date',
			'order' => 'DESC'
		);

		if (!empty($tax_query)) {
			$query_args['tax_query'] = $tax_query;
		}

		$query = new WP_Query($query_args);
		if (!$query->have_posts()) {
			return array();
		}

		return $query->posts;
	}

	/**
	 * Send open-jobs digest email.
	 */
	private function send_jobs_digest_email($user_id, $jobs, $settings) {
		$subject = __('Open jobs digest', 'zaobank');
		$urls = ZAOBank_Shortcodes::get_page_urls();
		$jobs_url = !empty($urls['jobs']) ? $urls['jobs'] : home_url('/');

		$lines = array(
			sprintf(__('Here are %d open jobs you may be interested in:', 'zaobank'), count($jobs)),
			''
		);

		foreach ($jobs as $job) {
			$hours = get_post_meta($job->ID, 'hours', true);
			$job_link = add_query_arg('job_id', (int) $job->ID, $jobs_url);
			$summary = wp_trim_words(wp_strip_all_tags((string) $job->post_content), 18, '...');
			$hours_text = $hours ? sprintf(__('(%s hrs)', 'zaobank'), rtrim(rtrim((string) $hours, '0'), '.')) : '';

			$lines[] = sprintf('- %1$s %2$s', $job->post_title, $hours_text);
			if ($summary !== '') {
				$lines[] = sprintf('  %s', $summary);
			}
			$lines[] = sprintf('  %s', $job_link);
		}

		$lines[] = '';
		$lines[] = sprintf(__('Browse all jobs: %s', 'zaobank'), $jobs_url);

		return $this->send_email($user_id, $subject, implode("\n", $lines));
	}
}
