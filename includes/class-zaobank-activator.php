<?php
/**
 * Fired during plugin activation.
 */
class ZAOBank_Activator {

	/**
	 * Activate the plugin.
	 */
	public static function activate() {
		// Create custom database tables
		self::create_tables();

		// Set default options
		self::set_default_options();

		// Create default capabilities
		self::create_capabilities();

		// Flush rewrite rules
		flush_rewrite_rules();

		// Set activation flag
		update_option('zaobank_activated', true);
		update_option('zaobank_version', ZAOBANK_VERSION);
	}

	/**
	 * Create custom database tables.
	 */
	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		// Exchanges table
		$table_exchanges = $wpdb->prefix . 'zaobank_exchanges';
		$sql_exchanges = "CREATE TABLE $table_exchanges (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            job_id bigint(20) UNSIGNED NOT NULL,
            provider_user_id bigint(20) UNSIGNED NOT NULL,
            requester_user_id bigint(20) UNSIGNED NOT NULL,
            hours decimal(10,2) NOT NULL,
            region_term_id bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY job_id (job_id),
            KEY provider_user_id (provider_user_id),
            KEY requester_user_id (requester_user_id),
            KEY region_term_id (region_term_id),
            KEY created_at (created_at)
        ) $charset_collate;";

		// User regions table
		$table_user_regions = $wpdb->prefix . 'zaobank_user_regions';
		$sql_user_regions = "CREATE TABLE $table_user_regions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            region_term_id bigint(20) UNSIGNED NOT NULL,
            affinity_score int(11) DEFAULT 0,
            last_seen_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_region (user_id, region_term_id),
            KEY user_id (user_id),
            KEY region_term_id (region_term_id)
        ) $charset_collate;";

		// Appreciations table
		$table_appreciations = $wpdb->prefix . 'zaobank_appreciations';
		$sql_appreciations = "CREATE TABLE $table_appreciations (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            exchange_id bigint(20) UNSIGNED NOT NULL,
            from_user_id bigint(20) UNSIGNED NOT NULL,
            to_user_id bigint(20) UNSIGNED NOT NULL,
            tag_slug varchar(100) NOT NULL,
            message text,
            is_public tinyint(1) DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY exchange_id (exchange_id),
            KEY from_user_id (from_user_id),
            KEY to_user_id (to_user_id),
            KEY is_public (is_public)
        ) $charset_collate;";

		// Messages table
		$table_messages = $wpdb->prefix . 'zaobank_messages';
		$sql_messages = "CREATE TABLE $table_messages (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            exchange_id bigint(20) UNSIGNED DEFAULT NULL,
            from_user_id bigint(20) UNSIGNED NOT NULL,
            to_user_id bigint(20) UNSIGNED NOT NULL,
            message text NOT NULL,
            is_read tinyint(1) DEFAULT 0,
            message_type varchar(20) NOT NULL DEFAULT 'direct',
            job_id bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY exchange_id (exchange_id),
            KEY from_user_id (from_user_id),
            KEY to_user_id (to_user_id),
            KEY is_read (is_read),
            KEY message_type (message_type)
        ) $charset_collate;";

		// Archived conversations table
		$table_archived = $wpdb->prefix . 'zaobank_archived_conversations';
		$sql_archived = "CREATE TABLE $table_archived (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            other_user_id bigint(20) UNSIGNED NOT NULL,
            archived_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_conversation (user_id, other_user_id)
        ) $charset_collate;";

		// Private notes table
		$table_private_notes = $wpdb->prefix . 'zaobank_private_notes';
		$sql_private_notes = "CREATE TABLE $table_private_notes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            author_user_id bigint(20) UNSIGNED NOT NULL,
            subject_user_id bigint(20) UNSIGNED NOT NULL,
            tag_slug varchar(100) NOT NULL,
            note text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY author_user_id (author_user_id),
            KEY subject_user_id (subject_user_id)
        ) $charset_collate;";

		// Flags table
		$table_flags = $wpdb->prefix . 'zaobank_flags';
		$sql_flags = "CREATE TABLE $table_flags (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            flagged_item_type varchar(50) NOT NULL,
            flagged_item_id bigint(20) UNSIGNED NOT NULL,
            flagged_user_id bigint(20) UNSIGNED DEFAULT NULL,
            reporter_user_id bigint(20) UNSIGNED NOT NULL,
            reason_slug varchar(100) NOT NULL,
            context_note text,
            status varchar(50) DEFAULT 'open',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reviewed_at datetime DEFAULT NULL,
            reviewer_user_id bigint(20) UNSIGNED DEFAULT NULL,
            resolution_note text,
            PRIMARY KEY  (id),
            KEY flagged_item (flagged_item_type, flagged_item_id),
            KEY flagged_user_id (flagged_user_id),
            KEY reporter_user_id (reporter_user_id),
            KEY status (status)
        ) $charset_collate;";

		// Execute table creation
		dbDelta($sql_exchanges);
		dbDelta($sql_user_regions);
		dbDelta($sql_appreciations);
		dbDelta($sql_messages);
		dbDelta($sql_private_notes);
		dbDelta($sql_flags);
		dbDelta($sql_archived);

		// Store database version
		update_option('zaobank_db_version', ZAOBANK_VERSION);
	}

	/**
	 * Set default plugin options.
	 */
	private static function set_default_options() {
		$default_options = array(
			'zaobank_enable_regions' => true,
			'zaobank_auto_hide_flagged' => true,
			'zaobank_flag_threshold' => 1,
			'zaobank_appreciation_tags' => array(
				'reliable',
				'kind',
				'skilled',
				'helpful',
				'communicative',
				'flexible',
				'prompt'
			),
			'zaobank_private_note_tags' => array(
				'easy-to-work-with',
				'clear-communicator',
				'respectful',
				'on-time',
				'flexible-schedule',
				'needs-clear-instructions',
				'prefers-morning',
				'prefers-evening',
				'job-completion'
			),
			'zaobank_flag_reasons' => array(
				'inappropriate-content',
				'harassment',
				'spam',
				'safety-concern',
				'other'
			)
		);

		foreach ($default_options as $key => $value) {
			if (!get_option($key)) {
				add_option($key, $value);
			}
		}
	}

	/**
	 * Create custom capabilities for user roles.
	 */
	private static function create_capabilities() {
		// Get roles
		$admin = get_role('administrator');
		$editor = get_role('editor');

		// Define capabilities
		$capabilities = array(
			// Job capabilities
			'edit_timebank_job',
			'read_timebank_job',
			'delete_timebank_job',
			'edit_timebank_jobs',
			'edit_others_timebank_jobs',
			'publish_timebank_jobs',
			'read_private_timebank_jobs',

			// Flag management
			'review_zaobank_flags',
			'manage_zaobank_flags',

			// User management
			'manage_zaobank_users',

			// Region management
			'manage_zaobank_regions'
		);

		// Add capabilities to administrator
		if ($admin) {
			foreach ($capabilities as $cap) {
				$admin->add_cap($cap);
			}
		}

		// Add limited capabilities to editor
		if ($editor) {
			$editor_caps = array(
				'review_zaobank_flags',
				'edit_timebank_jobs',
				'read_timebank_job'
			);

			foreach ($editor_caps as $cap) {
				$editor->add_cap($cap);
			}
		}
	}
}