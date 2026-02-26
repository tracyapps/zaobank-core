<?php
/**
 * Fired during plugin deactivation.
 */
class ZAOBank_Deactivator {

	/**
	 * Deactivate the plugin.
	 */
	public static function deactivate() {
		// Clear scheduled digest processing event.
		wp_clear_scheduled_hook('zaobank_process_notification_digests');

		// Flush rewrite rules
		flush_rewrite_rules();

		// Note: We do NOT drop tables on deactivation
		// This preserves user data if they temporarily deactivate the plugin
		// Tables should only be dropped on uninstall
	}
}
