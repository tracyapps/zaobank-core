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
		<h1 class="zaobank-page-title"><?php _e('Jobs', 'zaobank'); ?></h1>
		<?php
		$tabs = array(
			array('label' => __('all jobs', 'zaobank'), 'url' => $urls['jobs'], 'current' => true),
			array('label' => __('my jobs', 'zaobank'), 'url' => $urls['my_jobs']),
		);
		if (ZAOBank_Security::user_has_member_access()) {
			$tabs[] = array('label' => __('post a job', 'zaobank'), 'url' => $urls['job_form']);
		}
		include ZAOBANK_PLUGIN_DIR . 'public/templates/components/subpage-tabs.php';
		?>
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
			<div class="zaobank-filter-item">
				<button type="button" id="zaobank-filter-toggle" class="zaobank-btn zaobank-btn-outline zaobank-btn-sm">
					<svg class="zaobank-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<line x1="4" y1="21" x2="4" y2="14"/>
						<line x1="4" y1="10" x2="4" y2="3"/>
						<line x1="12" y1="21" x2="12" y2="12"/>
						<line x1="12" y1="8" x2="12" y2="3"/>
						<line x1="20" y1="21" x2="20" y2="16"/>
						<line x1="20" y1="12" x2="20" y2="3"/>
						<line x1="1" y1="14" x2="7" y2="14"/>
						<line x1="9" y1="8" x2="15" y2="8"/>
						<line x1="17" y1="16" x2="23" y2="16"/>
					</svg>
					<?php _e('Job Types', 'zaobank'); ?>
				</button>
			</div>
		</div>
		<div class="zaobank-filter-row zaobank-filter-row-secondary">
			<div class="zaobank-filter-item">
				<span class="zaobank-filter-summary" data-role="jobs-summary"><?php _e('Showing 0-0 of 0', 'zaobank'); ?></span>
			</div>
			<div class="zaobank-filter-item">
				<label class="zaobank-sr-only" for="zaobank-sort-filter"><?php _e('Sort jobs', 'zaobank'); ?></label>
				<select id="zaobank-sort-filter" class="zaobank-select" data-filter="sort">
					<option value="recent"><?php _e('Newest', 'zaobank'); ?></option>
					<option value="oldest"><?php _e('Oldest', 'zaobank'); ?></option>
					<option value="hours_desc"><?php _e('Hours (High to Low)', 'zaobank'); ?></option>
					<option value="hours_asc"><?php _e('Hours (Low to High)', 'zaobank'); ?></option>
					<option value="title"><?php _e('Title (A–Z)', 'zaobank'); ?></option>
				</select>
			</div>
			<div class="zaobank-filter-item">
				<label class="zaobank-sr-only" for="zaobank-per-page-filter"><?php _e('Jobs per page', 'zaobank'); ?></label>
				<select id="zaobank-per-page-filter" class="zaobank-select" data-filter="per_page">
					<option value="12">12</option>
					<option value="24">24</option>
					<option value="48">48</option>
				</select>
			</div>
		</div>
	</div>

	<!-- Filter Panel (slide-out) -->
	<div class="zaobank-filter-panel-overlay"></div>
	<aside class="zaobank-filter-panel">
		<div class="zaobank-filter-panel-header">
			<h3><?php _e('Filter by Job Type', 'zaobank'); ?></h3>
			<button type="button" class="zaobank-filter-panel-close zaobank-btn zaobank-btn-ghost zaobank-btn-sm" aria-label="<?php esc_attr_e('Close', 'zaobank'); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
					<line x1="18" y1="6" x2="6" y2="18"/>
					<line x1="6" y1="6" x2="18" y2="18"/>
				</svg>
			</button>
		</div>
		<div class="zaobank-filter-panel-body">
			<div class="zaobank-checkbox-group" id="zaobank-job-type-list">
				<p class="zaobank-loading-placeholder"><?php _e('Loading job types...', 'zaobank'); ?></p>
			</div>
		</div>
	</aside>

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
		<?php if (is_user_logged_in() && ZAOBank_Security::user_has_member_access()) : ?>
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

		{{#if job_types.length}}
		<div class="zaobank-job-types">
			{{#each job_types}}
			<span class="zaobank-tag zaobank-tag-sm">{{this.name}}</span>
			{{/each}}
		</div>
		{{/if}}

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
			{{#if virtual_ok}}
			<span class="zaobank-job-virtual">
				<svg class="zaobank-meta-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
					<path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
				</svg>
				<?php _e('Virtual ok', 'zaobank'); ?>
			</span>
			{{/if}}
		</div>

		<div class="zaobank-job-footer">
			<a href="<?php echo esc_url($urls['profile']); ?>?user_id={{requester_id}}" class="zaobank-job-poster">
				<img src="{{requester_avatar}}" alt="" class="zaobank-avatar-small">
				<span>{{requester_name}}</span>
			</a>
			{{#if can_claim}}
			<button type="button" class="zaobank-btn zaobank-btn-primary zaobank-btn-sm zaobank-claim-job" data-job-id="{{id}}">
				<?php _e('Claim', 'zaobank'); ?>
			</button>
			{{/if}}
		</div>
	</div>
</article>
</script>
