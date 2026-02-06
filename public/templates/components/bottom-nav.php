<?php
/**
 * Bottom Navigation Component
 *
 * Fixed bottom navigation for mobile/tablet devices.
 * Hidden on desktop (1024px+).
 */

if (!defined('ABSPATH')) {
	exit;
}

$urls = ZAOBank_Shortcodes::get_page_urls();
$community_url = isset($urls['community']) ? $urls['community'] : (isset($urls['messages']) ? $urls['messages'] : '#');
$unread_count = ZAOBank_Shortcodes::get_unread_message_count();
$current_url = trailingslashit(get_permalink());

// Determine active state
$active = '';
foreach ($urls as $key => $url) {
	if (trailingslashit($url) === $current_url) {
		$active = $key;
		break;
	}
}
?>

<nav class="zaobank-bottom-nav" role="navigation" aria-label="<?php esc_attr_e('Main navigation', 'zaobank'); ?>">
	<a href="<?php echo esc_url($urls['dashboard']); ?>"
	   class="zaobank-nav-item <?php echo $active === 'dashboard' ? 'active' : ''; ?>"
	   aria-label="<?php esc_attr_e('Dashboard', 'zaobank'); ?>">
		<svg class="zaobank-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
			<polyline points="9 22 9 12 15 12 15 22"/>
		</svg>
		<span class="zaobank-nav-label"><?php _e('Home', 'zaobank'); ?></span>
	</a>

	<a href="<?php echo esc_url($urls['jobs']); ?>"
	   class="zaobank-nav-item <?php echo $active === 'jobs' ? 'active' : ''; ?>"
	   aria-label="<?php esc_attr_e('Browse Jobs', 'zaobank'); ?>">
		<svg class="zaobank-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
			<path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
		</svg>
		<span class="zaobank-nav-label"><?php _e('Jobs', 'zaobank'); ?></span>
	</a>

	<a href="<?php echo esc_url($urls['job_form']); ?>"
	   class="zaobank-nav-item zaobank-nav-item-primary <?php echo $active === 'job_form' ? 'active' : ''; ?>"
	   aria-label="<?php esc_attr_e('Create New Job', 'zaobank'); ?>">
		<svg class="zaobank-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<line x1="12" y1="5" x2="12" y2="19"/>
			<line x1="5" y1="12" x2="19" y2="12"/>
		</svg>
		<span class="zaobank-nav-label"><?php _e('New', 'zaobank'); ?></span>
	</a>

	<a href="<?php echo esc_url($community_url); ?>"
	   class="zaobank-nav-item <?php echo $active === 'community' ? 'active' : ''; ?>"
	   aria-label="<?php esc_attr_e('Community', 'zaobank'); ?>">
		<svg class="zaobank-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
			<circle cx="8.5" cy="7" r="4"/>
			<path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
			<path d="M16 3.13a4 4 0 0 1 0 7.75"/>
		</svg>
		<span class="zaobank-nav-label"><?php _e('Community', 'zaobank'); ?></span>
	</a>

	<a href="<?php echo esc_url($urls['profile']); ?>"
	   class="zaobank-nav-item <?php echo $active === 'profile' ? 'active' : ''; ?>"
	   aria-label="<?php esc_attr_e('Profile', 'zaobank'); ?>">
		<svg class="zaobank-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<circle cx="12" cy="7" r="4"/>
			<path d="M5.5 21a7.5 7.5 0 0 1 13 0"/>
		</svg>
		<span class="zaobank-nav-label"><?php _e('Profile', 'zaobank'); ?></span>
	</a>
</nav>
