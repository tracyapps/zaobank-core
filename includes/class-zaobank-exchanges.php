<?php
/**
 * Exchange management - canonical time bank transactions.
 */
class ZAOBank_Exchanges {

	/**
	 * Create a new exchange record.
	 */
	public static function create_exchange($data) {
		global $wpdb;
		$table = ZAOBank_Database::get_exchanges_table();

		// Validate required fields
		if (empty($data['job_id']) || empty($data['provider_user_id']) ||
			empty($data['requester_user_id']) || empty($data['hours'])) {
			return new WP_Error(
				'missing_exchange_data',
				__('Missing required exchange data', 'zaobank')
			);
		}

		// Validate hours
		$hours_validation = ZAOBank_Security::validate_hours($data['hours']);
		if (is_wp_error($hours_validation)) {
			return $hours_validation;
		}

		// Insert exchange record
		$result = $wpdb->insert(
			$table,
			array(
				'job_id' => (int) $data['job_id'],
				'provider_user_id' => (int) $data['provider_user_id'],
				'requester_user_id' => (int) $data['requester_user_id'],
				'hours' => (float) $data['hours'],
				'region_term_id' => isset($data['region_term_id']) ? (int) $data['region_term_id'] : null,
				'created_at' => wp_date('Y-m-d H:i:s')
			),
			array('%d', '%d', '%d', '%f', '%d', '%s')
		);

		if ($result === false) {
			return new WP_Error(
				'exchange_creation_failed',
				__('Failed to create exchange', 'zaobank')
			);
		}

		$exchange_id = $wpdb->insert_id;

		// Log the exchange
		ZAOBank_Security::log_security_event('exchange_created', array(
			'exchange_id' => $exchange_id,
			'job_id' => $data['job_id'],
			'hours' => $data['hours']
		));

		return $exchange_id;
	}

	/**
	 * Get user balance.
	 */
	public static function get_user_balance($user_id) {
		global $wpdb;
		$table = ZAOBank_Database::get_exchanges_table();

		// Calculate hours earned (as provider)
		$hours_earned = $wpdb->get_var($wpdb->prepare(
			"SELECT SUM(hours) FROM $table WHERE provider_user_id = %d",
			$user_id
		));

		// Calculate hours spent (as requester)
		$hours_spent = $wpdb->get_var($wpdb->prepare(
			"SELECT SUM(hours) FROM $table WHERE requester_user_id = %d",
			$user_id
		));

		return array(
			'hours_earned' => (float) ($hours_earned ?: 0),
			'hours_spent' => (float) ($hours_spent ?: 0),
			'balance' => (float) (($hours_earned ?: 0) - ($hours_spent ?: 0))
		);
	}

	/**
	 * Get user exchange history.
	 */
	public static function get_user_exchanges($user_id, $args = array()) {
		global $wpdb;
		$table = ZAOBank_Database::get_exchanges_table();

		$defaults = array(
			'limit' => 50,
			'offset' => 0,
			'order' => 'DESC',
			'type' => 'all'
		);

		$args = wp_parse_args($args, $defaults);

		$where_sql = '(provider_user_id = %d OR requester_user_id = %d)';
		$values = array($user_id, $user_id);

		if ($args['type'] === 'earned') {
			$where_sql = 'provider_user_id = %d';
			$values = array($user_id);
		} elseif ($args['type'] === 'spent') {
			$where_sql = 'requester_user_id = %d';
			$values = array($user_id);
		}

		$values[] = $args['limit'];
		$values[] = $args['offset'];

		$query = $wpdb->prepare(
			"SELECT * FROM $table 
            WHERE $where_sql
            ORDER BY created_at {$args['order']}
            LIMIT %d OFFSET %d",
			$values
		);

		$exchanges = $wpdb->get_results($query);

		return array_map(array(__CLASS__, 'format_exchange_data'), $exchanges);
	}

	/**
	 * Get exchange by ID.
	 */
	public static function get_exchange($exchange_id) {
		global $wpdb;
		$table = ZAOBank_Database::get_exchanges_table();

		$exchange = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM $table WHERE id = %d",
			$exchange_id
		));

		if (!$exchange) {
			return null;
		}

		return self::format_exchange_data($exchange);
	}

	/**
	 * Get exchanges by job ID.
	 */
	public static function get_job_exchange($job_id) {
		global $wpdb;
		$table = ZAOBank_Database::get_exchanges_table();

		$exchange = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM $table WHERE job_id = %d LIMIT 1",
			$job_id
		));

		if (!$exchange) {
			return null;
		}

		return self::format_exchange_data($exchange);
	}

	/**
	 * Get exchanges by region.
	 */
	public static function get_region_exchanges($region_id, $args = array()) {
		global $wpdb;
		$table = ZAOBank_Database::get_exchanges_table();

		$defaults = array(
			'limit' => 50,
			'offset' => 0
		);

		$args = wp_parse_args($args, $defaults);

		$query = $wpdb->prepare(
			"SELECT * FROM $table 
            WHERE region_term_id = %d
            ORDER BY created_at DESC
            LIMIT %d OFFSET %d",
			$region_id,
			$args['limit'],
			$args['offset']
		);

		$exchanges = $wpdb->get_results($query);

		return array_map(array(__CLASS__, 'format_exchange_data'), $exchanges);
	}

	/**
	 * Get a summary of people the user has exchanged with.
	 */
	public static function get_worked_with_summary($user_id) {
		global $wpdb;
		$table = ZAOBank_Database::get_exchanges_table();

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					CASE 
						WHEN provider_user_id = %d THEN requester_user_id 
						ELSE provider_user_id 
					END AS other_user_id,
					COUNT(*) AS total_exchanges,
					SUM(hours) AS total_hours,
					SUM(CASE WHEN provider_user_id = %d THEN 1 ELSE 0 END) AS jobs_provided,
					SUM(CASE WHEN requester_user_id = %d THEN 1 ELSE 0 END) AS jobs_received,
					MAX(created_at) AS last_exchange_at
				FROM $table
				WHERE provider_user_id = %d OR requester_user_id = %d
				GROUP BY other_user_id
				ORDER BY last_exchange_at DESC",
				$user_id,
				$user_id,
				$user_id,
				$user_id,
				$user_id
			)
		);

		if (empty($rows)) {
			return array();
		}

		$notes = ZAOBank_Private_Notes::get_user_notes($user_id);
		$latest_notes = array();
		foreach ($notes as $note) {
			if (!isset($latest_notes[$note['subject_user_id']])) {
				$latest_notes[$note['subject_user_id']] = $note;
			}
		}

		return array_map(function($row) use ($latest_notes) {
			$other_id = (int) $row->other_user_id;
			$latest_note = isset($latest_notes[$other_id]) ? $latest_notes[$other_id] : null;

			return array(
				'other_user_id' => $other_id,
				'other_user_name' => get_the_author_meta('display_name', $other_id),
				'other_user_avatar' => ZAOBank_Helpers::get_user_avatar_url($other_id, 48),
				'total_exchanges' => (int) $row->total_exchanges,
				'total_hours' => (float) ($row->total_hours ?: 0),
				'jobs_provided' => (int) $row->jobs_provided,
				'jobs_received' => (int) $row->jobs_received,
				'last_exchange_at' => $row->last_exchange_at,
				'latest_note' => $latest_note
			);
		}, $rows);
	}

	/**
	 * Get exchange statistics.
	 */
	public static function get_statistics($filters = array()) {
		global $wpdb;
		$table = ZAOBank_Database::get_exchanges_table();

		$where = array('1=1');
		$values = array();

		if (!empty($filters['user_id'])) {
			$where[] = '(provider_user_id = %d OR requester_user_id = %d)';
			$values[] = $filters['user_id'];
			$values[] = $filters['user_id'];
		}

		if (!empty($filters['region_id'])) {
			$where[] = 'region_term_id = %d';
			$values[] = $filters['region_id'];
		}

		if (!empty($filters['date_from'])) {
			$where[] = 'created_at >= %s';
			$values[] = $filters['date_from'];
		}

		if (!empty($filters['date_to'])) {
			$where[] = 'created_at <= %s';
			$values[] = $filters['date_to'];
		}

		$where_sql = implode(' AND ', $where);

		if (!empty($values)) {
			$query = $wpdb->prepare(
				"SELECT 
                    COUNT(*) as total_exchanges,
                    SUM(hours) as total_hours,
                    AVG(hours) as avg_hours,
                    MIN(hours) as min_hours,
                    MAX(hours) as max_hours
                FROM $table 
                WHERE $where_sql",
				$values
			);
		} else {
			$query = "SELECT 
                COUNT(*) as total_exchanges,
                SUM(hours) as total_hours,
                AVG(hours) as avg_hours,
                MIN(hours) as min_hours,
                MAX(hours) as max_hours
            FROM $table 
            WHERE $where_sql";
		}

		$stats = $wpdb->get_row($query);

		return array(
			'total_exchanges' => (int) $stats->total_exchanges,
			'total_hours' => (float) ($stats->total_hours ?: 0),
			'avg_hours' => (float) ($stats->avg_hours ?: 0),
			'min_hours' => (float) ($stats->min_hours ?: 0),
			'max_hours' => (float) ($stats->max_hours ?: 0)
		);
	}

	/**
	 * Format exchange data for API response.
	 */
	private static function format_exchange_data($exchange) {
		if (!$exchange) {
			return null;
		}

		$job = get_post($exchange->job_id);
		$region = null;
		$provider_id = (int) $exchange->provider_user_id;
		$requester_id = (int) $exchange->requester_user_id;

		if ($exchange->region_term_id) {
			$region_term = get_term($exchange->region_term_id, 'zaobank_region');
			if ($region_term && !is_wp_error($region_term)) {
				$region = array(
					'id' => $region_term->term_id,
					'name' => $region_term->name,
					'slug' => $region_term->slug
				);
			}
		}

		return array(
			'id' => (int) $exchange->id,
			'job_id' => (int) $exchange->job_id,
			'job_title' => $job ? $job->post_title : null,
			'provider_id' => $provider_id,
			'provider_user_id' => $provider_id,
			'provider_name' => get_the_author_meta('display_name', $provider_id),
			'provider_avatar' => ZAOBank_Helpers::get_user_avatar_url($provider_id, 32),
			'requester_id' => $requester_id,
			'requester_user_id' => $requester_id,
			'requester_name' => get_the_author_meta('display_name', $requester_id),
			'requester_avatar' => ZAOBank_Helpers::get_user_avatar_url($requester_id, 32),
			'hours' => (float) $exchange->hours,
			'region' => $region,
			'created_at' => $exchange->created_at
		);
	}
}
