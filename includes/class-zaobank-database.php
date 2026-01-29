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
		// Future migration logic here
		// Example: if (version_compare($from_version, '1.1.0', '<')) { ... }
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
	 * Get flags table name.
	 */
	public static function get_flags_table() {
		global $wpdb;
		return $wpdb->prefix . 'zaobank_flags';
	}
}