<?php
/**
 * Fired when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

global $wpdb;

// Define table names
$tables = array(
	$wpdb->prefix . 'zaobank_exchanges',
	$wpdb->prefix . 'zaobank_user_regions',
	$wpdb->prefix . 'zaobank_appreciations',
	$wpdb->prefix . 'zaobank_messages',
	$wpdb->prefix . 'zaobank_private_notes',
	$wpdb->prefix . 'zaobank_flags'
);

// Ask user confirmation before dropping tables (handled in WordPress admin)
// If you want to preserve data, comment out the table dropping section

// Drop custom tables
foreach ($tables as $table) {
	$table_name = esc_sql($table);
	$wpdb->query("DROP TABLE IF EXISTS `{$table_name}`");
}

// Delete all jobs
$jobs = get_posts(array(
	'post_type' => 'timebank_job',
	'posts_per_page' => -1,
	'post_status' => 'any'
));

foreach ($jobs as $job) {
	wp_delete_post($job->ID, true);
}

// Delete all regions (taxonomy terms)
$regions = get_terms(array(
	'taxonomy' => 'zaobank_region',
	'hide_empty' => false
));

foreach ($regions as $region) {
	wp_delete_term($region->term_id, 'zaobank_region');
}

// Delete plugin options
$options = array(
	'zaobank_version',
	'zaobank_db_version',
	'zaobank_activated',
	'zaobank_enable_regions',
	'zaobank_auto_hide_flagged',
	'zaobank_flag_threshold',
	'zaobank_appreciation_tags',
	'zaobank_private_note_tags',
	'zaobank_flag_reasons',
	'zaobank_enable_security_logging',
	'zaobank_security_logs'
);

foreach ($options as $option) {
	delete_option($option);
}

// Delete user meta
$user_meta_keys = array(
	'user_skills',
	'user_availability',
	'user_bio',
	'user_primary_region',
	'user_profile_tags',
	'user_contact_preferences',
	'user_phone',
	'zaobank_onboarding_completed',
	'zaobank_onboarding_completed_at'
);

foreach ($user_meta_keys as $meta_key) {
	delete_metadata('user', 0, $meta_key, '', true);
}

// Remove custom capabilities from roles
$roles = array('administrator', 'editor');
$capabilities = array(
	'edit_timebank_job',
	'read_timebank_job',
	'delete_timebank_job',
	'edit_timebank_jobs',
	'edit_others_timebank_jobs',
	'publish_timebank_jobs',
	'read_private_timebank_jobs',
	'review_zaobank_flags',
	'manage_zaobank_flags',
	'manage_zaobank_users',
	'manage_zaobank_regions'
);

foreach ($roles as $role_name) {
	$role = get_role($role_name);
	if ($role) {
		foreach ($capabilities as $cap) {
			$role->remove_cap($cap);
		}
	}
}

// Clear any cached data
wp_cache_flush();