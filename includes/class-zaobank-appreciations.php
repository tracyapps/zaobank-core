<?php
/**
 * Appreciation management.
 */
class ZAOBank_Appreciations {

	/**
	 * Create an appreciation.
	 */
	public static function create_appreciation($data) {
		global $wpdb;
		$table = ZAOBank_Database::get_appreciations_table();

		// Validate required fields
		if (empty($data['exchange_id']) || empty($data['from_user_id']) ||
			empty($data['to_user_id']) || empty($data['tag_slug'])) {
			return new WP_Error(
				'missing_appreciation_data',
				__('Missing required appreciation data', 'zaobank')
			);
		}

		// Verify the exchange exists
		$exchange = ZAOBank_Exchanges::get_exchange($data['exchange_id']);
		if (!$exchange) {
			return new WP_Error(
				'invalid_exchange',
				__('Invalid exchange', 'zaobank')
			);
		}

		// Validate tag
		$valid_tags = get_option('zaobank_appreciation_tags', array());
		if (!in_array($data['tag_slug'], $valid_tags)) {
			return new WP_Error(
				'invalid_tag',
				__('Invalid appreciation tag', 'zaobank')
			);
		}

		$result = $wpdb->insert(
			$table,
			array(
				'exchange_id' => (int) $data['exchange_id'],
				'from_user_id' => (int) $data['from_user_id'],
				'to_user_id' => (int) $data['to_user_id'],
				'tag_slug' => sanitize_key($data['tag_slug']),
				'message' => isset($data['message']) ? wp_kses_post($data['message']) : null,
				'is_public' => isset($data['is_public']) ? (int) $data['is_public'] : 0,
				'created_at' => wp_date('Y-m-d H:i:s')
			),
			array('%d', '%d', '%d', '%s', '%s', '%d', '%s')
		);

		if ($result === false) {
			return new WP_Error(
				'appreciation_creation_failed',
				__('Failed to create appreciation', 'zaobank')
			);
		}

		return $wpdb->insert_id;
	}

	/**
	 * Get appreciations for a user.
	 */
	public static function get_user_appreciations($user_id, $public_only = true) {
		global $wpdb;
		$table = ZAOBank_Database::get_appreciations_table();

		$where = $wpdb->prepare('to_user_id = %d', $user_id);

		if ($public_only) {
			$where .= ' AND is_public = 1';
		}

		$appreciations = $wpdb->get_results(
			"SELECT * FROM $table WHERE $where ORDER BY created_at DESC"
		);

		return array_map(array(__CLASS__, 'format_appreciation_data'), $appreciations);
	}

	/**
	 * Get appreciations given by a user.
	 */
	public static function get_given_appreciations($user_id) {
		global $wpdb;
		$table = ZAOBank_Database::get_appreciations_table();

		$appreciations = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE from_user_id = %d ORDER BY created_at DESC",
				$user_id
			)
		);

		return array_map(array(__CLASS__, 'format_appreciation_data'), $appreciations);
	}

	/**
	 * Format appreciation data.
	 */
	private static function format_appreciation_data($appreciation) {
		$exchange = ZAOBank_Exchanges::get_exchange($appreciation->exchange_id);

		return array(
			'id' => (int) $appreciation->id,
			'exchange_id' => (int) $appreciation->exchange_id,
			'from_user_id' => (int) $appreciation->from_user_id,
			'from_user_name' => get_the_author_meta('display_name', $appreciation->from_user_id),
			'from_user_avatar' => ZAOBank_Helpers::get_user_avatar_url($appreciation->from_user_id, 40),
			'to_user_id' => (int) $appreciation->to_user_id,
			'to_user_name' => get_the_author_meta('display_name', $appreciation->to_user_id),
			'tag_slug' => $appreciation->tag_slug,
			'message' => $appreciation->message,
			'is_public' => (bool) $appreciation->is_public,
			'created_at' => $appreciation->created_at,
			'job_id' => $exchange ? $exchange['job_id'] : null,
			'job_title' => $exchange ? $exchange['job_title'] : null
		);
	}
}
