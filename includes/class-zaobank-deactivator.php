<?php
/**
 * Fired during plugin deactivation.
 */
class ZAOBank_Deactivator {

	/**
	 * Deactivate the plugin.
	 */
	public static function deactivate() {
		// Flush rewrite rules
		flush_rewrite_rules();

		// Note: We do NOT drop tables on deactivation
		// This preserves user data if they temporarily deactivate the plugin
		// Tables should only be dropped on uninstall
	}
}