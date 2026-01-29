<?php
/**
 * Jobs List Template
 *
 * Browse available jobs with filters.
 */

if (!defined('ABSPATH')) {
	exit;
}

$urls = ZAOBank_Shortcodes::get_page_urls();
$initial_region = isset($region) ? $region : '';
$initial_status = isset($status) ? $status : 'available';
?>

<div class="zaobank-container zaobank-jobs-page" data-component="jobs-list">

	<header class="zaobank-page-header">
		<h1 class="zaobank-page-title"><?php _e('Available Jobs', 'zaobank'); ?></h1>
	</header>

	<!-- Filters -->
	<div class="zaobank-filters">
		<div class="zaobank-filter-row">
			<div class="zaobank-filter-item">
				<label for="zaobank-region-filter" class="zaobank-sr-only"><?php _e('Filter by region', 'zaobank'); ?></label>
				<select id="zaobank-region-filter" class="zaobank-select" data-filter="region">
					<option value=""><?php _e('All Regions', 'zaobank'); ?></option>
				</select>
			</div>
			<div class="zaobank-filter-item zaobank-search-wrapper">
				<label for="zaobank-search" class="zaobank-sr-only"><?php _e('Search jobs', 'zaobank'); ?></label>
				<input type="search"
				       id="zaobank-search"
				       class="zaobank-input zaobank-search-input"
				       placeholder="<?php esc_attr_e('Search jobs...', 'zaobank'); ?>"
				       data-filter="search">
				<svg class="zaobank-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<circle cx="11" cy="11" r="8"/>
					<line x1="21" y1="21" x2="16.65" y2="16.65"/>
				</svg>
			</div>
		</div>
	</div>

	<!-- Jobs List -->
	<div id="zaobank-jobs-list" class="zaobank-jobs-list" data-loading="true">
		<div class="zaobank-loading-state">
			<div class="zaobank-spinner"></div>
			<p><?php _e('Loading jobs...', 'zaobank'); ?></p>
		</div>
	</div>

	<!-- Load More -->
	<div class="zaobank-load-more" style="display: none;">
		<button type="button" class="zaobank-btn zaobank-btn-outline zaobank-btn-block" data-action="load-more">
			<?php _e('Load More Jobs', 'zaobank'); ?>
		</button>
	</div>

	<!-- Empty State -->
	<div class="zaobank-empty-state" style="display: none;">
		<svg class="zaobank-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
			<rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
			<path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
		</svg>
		<h3><?php _e('No jobs found', 'zaobank'); ?></h3>
		<p><?php _e('Try adjusting your filters or check back later.', 'zaobank'); ?></p>
		<?php if (is_user_logged_in()) : ?>
			<a href="<?php echo esc_url($urls['job_form']); ?>" class="zaobank-btn zaobank-btn-primary">
				<?php _e('Post a Job', 'zaobank'); ?>
			</a>
		<?php endif; ?>
	</div>

</div>

<?php if (is_user_logged_in()) : ?>
	<?php include ZAOBANK_PLUGIN_DIR . 'public/templates/components/bottom-nav.php'; ?>
<?php endif; ?>

<script type="text/template" id="zaobank-job-card-template">
<article class="zaobank-card zaobank-job-card" data-job-id="{{id}}">
	<div class="zaobank-card-body">
		<div class="zaobank-job-header">
			<h3 class="zaobank-job-title">
				<a href="<?php echo esc_url($urls['jobs']); ?>?job_id={{id}}">{{title}}</a>
			</h3>
			<span class="zaobank-badge zaobank-badge-{{status_class}}">{{status_label}}</span>
		</div>

		<p class="zaobank-job-excerpt">{{excerpt}}</p>

		<div class="zaobank-job-meta">
			<span class="zaobank-job-hours">
				<svg class="zaobank-meta-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<circle cx="12" cy="12" r="10"/>
					<polyline points="12 6 12 12 16 14"/>
				</svg>
				{{hours}} <?php _e('hours', 'zaobank'); ?>
			</span>
			{{#if location}}
			<span class="zaobank-job-location">
				<svg class="zaobank-meta-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
					<circle cx="12" cy="10" r="3"/>
				</svg>
				{{location}}
			</span>
			{{/if}}
		</div>

		<div class="zaobank-job-footer">
			<div class="zaobank-job-poster">
				<img src="{{requester_avatar}}" alt="" class="zaobank-avatar-small">
				<span>{{requester_name}}</span>
			</div>
			{{#if can_claim}}
			<button type="button" class="zaobank-btn zaobank-btn-primary zaobank-btn-sm zaobank-claim-job" data-job-id="{{id}}">
				<?php _e('Claim', 'zaobank'); ?>
			</button>
			{{/if}}
		</div>
	</div>
</article>
</script>
