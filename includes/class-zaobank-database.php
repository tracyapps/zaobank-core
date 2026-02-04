<?php
/**
 * Database management and version control.
 */
class ZAOBank_Database {

	/**
	 * Check database version and run migrations if needed.
	 */
	public function check_database_version() {
		$current_version = get_option('zaobank_db_version', '0.0.0');

		if (version_compare($current_version, ZAOBANK_VERSION, '<')) {
			$this->run_migrations($current_version);
			update_option('zaobank_db_version', ZAOBANK_VERSION);
		}
	}

	/**
	 * Run database migrations.
	 */
	private function run_migrations($from_version) {
		if (version_compare($from_version, '1.1.0', '<')) {
			$this->migrate_1_1_0();
		}
	}

	/**
	 * Migration for v1.1.0: Add message_type/job_id to messages, create archived_conversations table.
	 */
	private function migrate_1_1_0() {
		global $wpdb;
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		$charset_collate = $wpdb->get_charset_collate();
		$messages_table = self::get_messages_table();

		// Add message_type and job_id columns to messages table
		$columns = $wpdb->get_col("DESCRIBE $messages_table", 0);

		if (!in_array('message_type', $columns)) {
			$wpdb->query("ALTER TABLE $messages_table ADD COLUMN message_type varchar(20) NOT NULL DEFAULT 'direct' AFTER is_read");
		}

		if (!in_array('job_id', $columns)) {
			$wpdb->query("ALTER TABLE $messages_table ADD COLUMN job_id bigint(20) UNSIGNED DEFAULT NULL AFTER message_type");
		}

		// Create archived conversations table
		$archived_table = self::get_archived_conversations_table();
		$sql = "CREATE TABLE $archived_table (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id bigint(20) UNSIGNED NOT NULL,
			other_user_id bigint(20) UNSIGNED NOT NULL,
			archived_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY user_conversation (user_id, other_user_id)
		) $charset_collate;";

		dbDelta($sql);

		// Add job-completion to private note tags if not present
		$tags = get_option('zaobank_private_note_tags', array());
		if (!in_array('job-completion', $tags)) {
			$tags[] = 'job-completion';
			update_option('zaobank_private_note_tags', $tags);
		}
	}

	/**
	 * Get exchanges table name.
	 */
	public static function get_exchanges_table() {
		global $wpdb;
		return $wpdb->prefix . 'zaobank_exchanges';
	}

	/**
	 * Get user regions table name.
	 */
	public static function get_user_regions_table() {
		global $wpdb;
		return $wpdb->prefix . 'zaobank_user_regions';
	}

	/**
	 * Get appreciations table name.
	 */
	public static function get_appreciations_table() {
		global $wpdb;
		return $wpdb->prefix . 'zaobank_appreciations';
	}

	/**
	 * Get messages table name.
	 */
	public static function get_messages_table() {
		global $wpdb;
		return $wpdb->prefix . 'zaobank_messages';
	}

	/**
	 * Get private notes table name.
	 */
	public static function get_private_notes_table() {
		global $wpdb;
		return $wpdb->prefix . 'zaobank_private_notes';
	}

	/**
	 * Get archived conversations table name.
	 */
	public static function get_archived_conversations_table() {
		global $wpdb;
		return $wpdb->prefix . 'zaobank_archived_conversations';
	}

	/**
	 * Get flags table name.
	 */
	public static function get_flags_table() {
		global $wpdb;
		return $wpdb->prefix . 'zaobank_flags';
	}
}