<?php
/**
 * Plugin Name: ZAO Bank Core
 * Plugin URI: https://zaobank.org
 * Description: A WordPress-based time banking system built for church communities with porous regional boundaries and strong care ethics.
 * Version: 1.0.0
 * Author: ZAO Bank
 * Author URI: https://zaobank.org
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: zaobank
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

// Define plugin constants
define('ZAOBANK_VERSION', '1.0.0');
define('ZAOBANK_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ZAOBANK_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ZAOBANK_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once ZAOBANK_PLUGIN_DIR . 'includes/class-zaobank-core.php';

/**
 * Begins execution of the plugin.
 */
function run_zaobank() {
	$plugin = new ZAOBank_Core();
	$plugin->run();
}

/**
 * Activation hook
 */
function activate_zaobank() {
	require_once ZAOBANK_PLUGIN_DIR . 'includes/class-zaobank-activator.php';
	ZAOBank_Activator::activate();
}

/**
 * Deactivation hook
 */
function deactivate_zaobank() {
	require_once ZAOBANK_PLUGIN_DIR . 'includes/class-zaobank-deactivator.php';
	ZAOBank_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_zaobank');
register_deactivation_hook(__FILE__, 'deactivate_zaobank');

// Run the plugin
run_zaobank();

/**
 * =============================================================================
 * THEME TEMPLATE TAGS
 * =============================================================================
 * These functions are available for themes to use in their templates.
 */

/**
 * Get the ZAOBank page URLs for navigation.
 *
 * Usage in theme:
 *   $urls = zaobank_get_urls();
 *   echo $urls['dashboard']; // https://site.com/app/dashboard/
 *
 * @return array Associative array of page URLs.
 */
function zaobank_get_urls() {
	return ZAOBank_Shortcodes::get_page_urls();
}

/**
 * Get unread message count for current user.
 *
 * Usage in theme:
 *   $count = zaobank_unread_count();
 *   if ($count > 0) echo '<span class="badge">' . $count . '</span>';
 *
 * @return int Number of unread messages.
 */
function zaobank_unread_count() {
	return ZAOBank_Shortcodes::get_unread_message_count();
}

/**
 * Check if we're on a ZAOBank app page.
 *
 * Usage in theme:
 *   if (zaobank_is_app_page()) {
 *       // Load app-specific header
 *   }
 *
 * @return bool True if current page is a ZAOBank page.
 */
function zaobank_is_app_page() {
	if (!is_page()) {
		return false;
	}

	global $post;
	$content = $post->post_content ?? '';

	// Check if page contains any zaobank shortcode
	return (
		has_shortcode($content, 'zaobank_dashboard') ||
		has_shortcode($content, 'zaobank_jobs') ||
		has_shortcode($content, 'zaobank_job') ||
		has_shortcode($content, 'zaobank_job_form') ||
		has_shortcode($content, 'zaobank_my_jobs') ||
		has_shortcode($content, 'zaobank_profile') ||
		has_shortcode($content, 'zaobank_profile_edit') ||
		has_shortcode($content, 'zaobank_messages') ||
		has_shortcode($content, 'zaobank_conversation') ||
		has_shortcode($content, 'zaobank_exchanges') ||
		has_shortcode($content, 'zaobank_appreciations')
	);
}

/**
 * Check if we're within the /app/ section.
 *
 * Usage in theme:
 *   if (zaobank_is_app_section()) {
 *       get_header('app'); // Use header-app.php
 *   }
 *
 * @param string $parent_slug The parent page slug to check (default: 'app').
 * @return bool True if current page is under the app section.
 */
function zaobank_is_app_section($parent_slug = 'app') {
	if (!is_page()) {
		return false;
	}

	global $post;

	// Check if current page slug starts with app/ or is a child of app page
	$parent_page = get_page_by_path($parent_slug);
	if (!$parent_page) {
		return false;
	}

	// Check if current page is the parent or a descendant
	if ($post->ID === $parent_page->ID) {
		return true;
	}

	// Check ancestors
	$ancestors = get_post_ancestors($post->ID);
	return in_array($parent_page->ID, $ancestors);
}

/**
 * Get which ZAOBank template is being used on current page.
 *
 * Usage in theme:
 *   $template = zaobank_current_template();
 *   // Returns: 'dashboard', 'jobs-list', 'profile', etc. or false
 *
 * @return string|false Template name or false if not a ZAOBank page.
 */
function zaobank_current_template() {
	if (!is_page()) {
		return false;
	}

	global $post;
	$content = $post->post_content ?? '';

	$shortcode_map = array(
		'zaobank_dashboard'     => 'dashboard',
		'zaobank_jobs'          => 'jobs-list',
		'zaobank_job'           => 'job-single',
		'zaobank_job_form'      => 'job-form',
		'zaobank_my_jobs'       => 'my-jobs',
		'zaobank_profile'       => 'profile',
		'zaobank_profile_edit'  => 'profile-edit',
		'zaobank_messages'      => 'messages',
		'zaobank_conversation'  => 'conversation',
		'zaobank_exchanges'     => 'exchanges',
		'zaobank_appreciations' => 'appreciations',
	);

	foreach ($shortcode_map as $shortcode => $template) {
		if (has_shortcode($content, $shortcode)) {
			return $template;
		}
	}

	return false;
}

/**
 * Get the current user's time balance.
 *
 * @return float Time balance in hours.
 */
function zaobank_user_balance() {
	if (!is_user_logged_in()) {
		return 0;
	}
	return ZAOBank_Exchanges::get_user_balance(get_current_user_id());
}

/**
 * Render a ZAOBank template directly.
 *
 * Use this in page templates to output plugin template content.
 * Auth checks are bypassed - use when AAM handles authentication.
 *
 * Usage:
 *   <?php zaobank_render_template('dashboard'); ?>
 *   <?php zaobank_render_template('job-single', ['id' => 123]); ?>
 *
 * @param string $template_name Template name (dashboard, jobs-list, etc.).
 * @param array  $args          Optional args to pass to template.
 */
function zaobank_render_template($template_name, $args = array()) {
	ZAOBank_Shortcodes::instance()->render_template_direct($template_name, $args);
}

/**
 * Get ZAOBank template content as string.
 *
 * @param string $template_name Template name.
 * @param array  $args          Optional args.
 * @return string Template HTML.
 */
function zaobank_get_template($template_name, $args = array()) {
	return ZAOBank_Shortcodes::instance()->get_template($template_name, $args);
}

/**
 * Include the bottom navigation component.
 *
 * Usage in page templates:
 *   <?php zaobank_bottom_nav(); ?>
 */
function zaobank_bottom_nav() {
	zaobank_render_template('components/bottom-nav');
}

/**
 * Get list of available templates for theme overrides.
 *
 * Usage:
 *   $templates = zaobank_available_templates();
 *   foreach ($templates as $name => $description) { ... }
 *
 * @return array Template names and descriptions.
 */
function zaobank_available_templates() {
	return ZAOBank_Shortcodes::get_available_templates();
}