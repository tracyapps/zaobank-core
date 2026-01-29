<?php
/**
 * Dashboard Template
 *
 * User's main dashboard with balance, quick stats, and recent activity.
 */

if (!defined('ABSPATH')) {
	exit;
}

$user_id = get_current_user_id();
$user = get_userdata($user_id);
$urls = ZAOBank_Shortcodes::get_page_urls();
?>

<div class="zaobank-container zaobank-dashboard" data-component="dashboard">

	<header class="zaobank-page-header">
		<h1 class="zaobank-greeting">
			<?php printf(__('Hello, %s', 'zaobank'), esc_html($user->display_name)); ?>
		</h1>
	</header>

	<!-- Balance Card -->
	<div class="zaobank-card zaobank-balance-card">
		<div class="zaobank-card-header">
			<h2 class="zaobank-card-title"><?php _e('Your Balance', 'zaobank'); ?></h2>
		</div>
		<div class="zaobank-card-body">
			<div class="zaobank-balance-display" data-loading="true">
				<div class="zaobank-loading-placeholder"><?php _e('Loading...', 'zaobank'); ?></div>
			</div>
		</div>
	</div>

	<!-- Quick Stats -->
	<div class="zaobank-card zaobank-stats-card">
		<div class="zaobank-card-header">
			<h2 class="zaobank-card-title"><?php _e('Your Activity', 'zaobank'); ?></h2>
		</div>
		<div class="zaobank-card-body">
			<div class="zaobank-stats-grid" data-component="stats" data-loading="true">
				<a href="<?php echo esc_url($urls['my_jobs']); ?>" class="zaobank-stat-item">
					<span class="zaobank-stat-value" data-stat="jobs_requested">-</span>
					<span class="zaobank-stat-label"><?php _e('Jobs Posted', 'zaobank'); ?></span>
				</a>
				<a href="<?php echo esc_url($urls['exchanges']); ?>" class="zaobank-stat-item">
					<span class="zaobank-stat-value" data-stat="jobs_completed">-</span>
					<span class="zaobank-stat-label"><?php _e('Jobs Done', 'zaobank'); ?></span>
				</a>
				<a href="<?php echo esc_url($urls['appreciations']); ?>" class="zaobank-stat-item">
					<span class="zaobank-stat-value" data-stat="appreciations_received">-</span>
					<span class="zaobank-stat-label"><?php _e('Appreciations', 'zaobank'); ?></span>
				</a>
			</div>
		</div>
	</div>

	<!-- Quick Actions -->
	<div class="zaobank-quick-actions">
		<a href="<?php echo esc_url($urls['job_form']); ?>" class="zaobank-btn zaobank-btn-primary zaobank-btn-block">
			<svg class="zaobank-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
				<line x1="12" y1="5" x2="12" y2="19"/>
				<line x1="5" y1="12" x2="19" y2="12"/>
			</svg>
			<?php _e('Request Help', 'zaobank'); ?>
		</a>
		<a href="<?php echo esc_url($urls['jobs']); ?>" class="zaobank-btn zaobank-btn-secondary zaobank-btn-block">
			<svg class="zaobank-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
				<circle cx="11" cy="11" r="8"/>
				<line x1="21" y1="21" x2="16.65" y2="16.65"/>
			</svg>
			<?php _e('Find Jobs', 'zaobank'); ?>
		</a>
	</div>

	<!-- Recent Activity -->
	<div class="zaobank-card zaobank-activity-card">
		<div class="zaobank-card-header">
			<h2 class="zaobank-card-title"><?php _e('Recent Activity', 'zaobank'); ?></h2>
		</div>
		<div class="zaobank-card-body">
			<div class="zaobank-activity-list" data-component="activity" data-loading="true">
				<div class="zaobank-loading-placeholder"><?php _e('Loading recent activity...', 'zaobank'); ?></div>
			</div>
		</div>
	</div>

</div>

<?php include ZAOBANK_PLUGIN_DIR . 'public/templates/components/bottom-nav.php'; ?>
