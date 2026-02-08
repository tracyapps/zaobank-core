<?php
/**
 * Moderation Dashboard Template (Plugin Default)
 *
 * This is the plugin fallback template. The theme's app/moderation.php
 * takes priority when present.
 *
 * @package zaobank
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('zaobank_has_mod_access') || !zaobank_has_mod_access()) {
	echo '<div class="zaobank-error">' . esc_html__('Access denied.', 'zaobank') . '</div>';
	return;
}

$current_view = isset($view) ? $view : (isset($_GET['view']) ? sanitize_key($_GET['view']) : 'users');
?>

<div class="zaobank-container zaobank-moderation-page" data-component="moderation" data-view="<?php echo esc_attr($current_view); ?>">
	<h1><?php _e('Moderation Dashboard', 'zaobank'); ?></h1>
	<p><?php _e('This template should be overridden by the theme.', 'zaobank'); ?></p>
</div>
