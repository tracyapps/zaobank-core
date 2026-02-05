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

	<a href="<?php echo esc_url($urls['messages']); ?>"
	   class="zaobank-nav-item <?php echo $active === 'messages' ? 'active' : ''; ?>"
	   aria-label="<?php esc_attr_e('Messages', 'zaobank'); ?>">
		<svg class="zaobank-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
		</svg>
		<span class="zaobank-nav-label"><?php _e('Messages', 'zaobank'); ?></span>
		<?php if ($unread_count > 0) : ?>
			<span class="zaobank-nav-badge" data-unread-count="<?php echo (int) $unread_count; ?>" aria-label="<?php echo esc_attr(sprintf(_n('%d unread message', '%d unread messages', $unread_count, 'zaobank'), $unread_count)); ?>">
				<?php echo $unread_count > 99 ? '99+' : $unread_count; ?>
			</span>
		<?php endif; ?>
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
