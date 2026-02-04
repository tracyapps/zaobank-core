<?php
/**
 * My Jobs Template
 *
 * User's created and claimed jobs.
 */

if (!defined('ABSPATH')) {
	exit;
}

$urls = ZAOBank_Shortcodes::get_page_urls();
?>

<div class="zaobank-container zaobank-my-jobs-page" data-component="my-jobs">

	<header class="zaobank-page-header">
		<h1 class="zaobank-page-title"><?php _e('Jobs', 'zaobank'); ?></h1>
		<nav class="zaobank-subpage-tabs">
			<ul role="tablist">
				<li role="tab" class="subpage-tab">
					<a href="<?php echo esc_url($urls['jobs']); ?>">all jobs</a>
				</li>
				<li role="tab" class="subpage-tab current-tab">
					<span>my jobs</span>
				</li>
				<li role="tab" class="subpage-tab">
					<a href="<?php echo esc_url($urls['job_form']); ?>">post a job</a>
				</li>
			</ul>
		</nav>
	</header>

	<!-- Tab Navigation -->
	<div class="zaobank-tabs">
		<button type="button" class="zaobank-tab active" data-tab="posted">
			<?php _e('Posted', 'zaobank'); ?>
			<span class="zaobank-tab-count" data-count="posted">0</span>
		</button>
		<button type="button" class="zaobank-tab" data-tab="claimed">
			<?php _e('Claimed', 'zaobank'); ?>
			<span class="zaobank-tab-count" data-count="claimed">0</span>
		</button>
	</div>

	<!-- Posted Jobs Panel -->
	<div class="zaobank-tab-panel active" data-panel="posted">
		<div class="zaobank-jobs-list" data-list="posted" data-loading="true">
			<div class="zaobank-loading-state">
				<div class="zaobank-spinner"></div>
				<p><?php _e('Loading your posted jobs...', 'zaobank'); ?></p>
			</div>
		</div>

		<div class="zaobank-empty-state" data-empty="posted" style="display: none;">
			<svg class="zaobank-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
				<rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
				<path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
			</svg>
			<h3><?php _e('No jobs posted yet', 'zaobank'); ?></h3>
			<p><?php _e('When you need help with something, post a job for others to claim.', 'zaobank'); ?></p>
			<a href="<?php echo esc_url($urls['job_form']); ?>" class="zaobank-btn zaobank-btn-primary">
				<?php _e('Post Your First Job', 'zaobank'); ?>
			</a>
		</div>
	</div>

	<!-- Claimed Jobs Panel -->
	<div class="zaobank-tab-panel" data-panel="claimed">
		<div class="zaobank-jobs-list" data-list="claimed" data-loading="true">
			<div class="zaobank-loading-state">
				<div class="zaobank-spinner"></div>
				<p><?php _e('Loading your claimed jobs...', 'zaobank'); ?></p>
			</div>
		</div>

		<div class="zaobank-empty-state" data-empty="claimed" style="display: none;">
			<svg class="zaobank-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
				<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
				<polyline points="22 4 12 14.01 9 11.01"/>
			</svg>
			<h3><?php _e('No jobs claimed yet', 'zaobank'); ?></h3>
			<p><?php _e('Browse available jobs and claim one to start helping your community.', 'zaobank'); ?></p>
			<a href="<?php echo esc_url($urls['jobs']); ?>" class="zaobank-btn zaobank-btn-primary">
				<?php _e('Browse Jobs', 'zaobank'); ?>
			</a>
		</div>
	</div>

	<!-- Floating Action Button -->
	<a href="<?php echo esc_url($urls['job_form']); ?>" class="zaobank-fab" aria-label="<?php esc_attr_e('Post a new job', 'zaobank'); ?>">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<line x1="12" y1="5" x2="12" y2="19"/>
			<line x1="5" y1="12" x2="19" y2="12"/>
		</svg>
	</a>

</div>

<?php include ZAOBANK_PLUGIN_DIR . 'public/templates/components/bottom-nav.php'; ?>

<script type="text/template" id="zaobank-my-job-card-template">
<article class="zaobank-card zaobank-job-card" data-job-id="{{id}}">
	<div class="zaobank-card-body">
		<div class="zaobank-job-header">
			<h3 class="zaobank-job-title">
				<a href="<?php echo esc_url($urls['jobs']); ?>?job_id={{id}}">{{title}}</a>
			</h3>
			<span class="zaobank-badge zaobank-badge-{{status_class}}">{{status_label}}</span>
		</div>

		<div class="zaobank-job-meta">
			<span class="zaobank-job-hours">{{hours}} <?php _e('hours', 'zaobank'); ?></span>
			<span class="zaobank-job-date">{{created_date}}</span>
		</div>

		{{#if provider_name}}
		<div class="zaobank-job-claimed-by">
			<img src="{{provider_avatar}}" alt="" class="zaobank-avatar-tiny">
			<span><?php _e('Claimed by', 'zaobank'); ?> {{provider_name}}</span>
		</div>
		{{/if}}

		<div class="zaobank-job-card-actions">
			{{#if can_complete}}
			<button type="button" class="zaobank-btn zaobank-btn-success zaobank-btn-sm zaobank-complete-job" data-job-id="{{id}}">
				<?php _e('Mark Complete', 'zaobank'); ?>
			</button>
			{{/if}}

			{{#if can_edit}}
			<a href="<?php echo esc_url($urls['job_form']); ?>?job_id={{id}}" class="zaobank-btn zaobank-btn-outline zaobank-btn-sm">
				<?php _e('Edit', 'zaobank'); ?>
			</a>
			{{/if}}

			<a href="<?php echo esc_url($urls['jobs']); ?>?job_id={{id}}" class="zaobank-btn zaobank-btn-ghost zaobank-btn-sm">
				<?php _e('View', 'zaobank'); ?>
			</a>
		</div>
	</div>
</article>
</script>
