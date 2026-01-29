<?php
/**
 * Single Job Template
 *
 * Detailed view of a single job.
 */

if (!defined('ABSPATH')) {
	exit;
}

$job_id = isset($id) ? (int) $id : 0;
$urls = ZAOBank_Shortcodes::get_page_urls();
$current_user_id = get_current_user_id();
?>

<div class="zaobank-container zaobank-job-single" data-component="job-single" data-job-id="<?php echo esc_attr($job_id); ?>">

	<!-- Back Link -->
	<a href="<?php echo esc_url($urls['jobs']); ?>" class="zaobank-back-link">
		<svg class="zaobank-back-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<line x1="19" y1="12" x2="5" y2="12"/>
			<polyline points="12 19 5 12 12 5"/>
		</svg>
		<?php _e('Back to Jobs', 'zaobank'); ?>
	</a>

	<!-- Job Content - populated via JS -->
	<div class="zaobank-job-content" data-loading="true">
		<div class="zaobank-loading-state">
			<div class="zaobank-spinner"></div>
			<p><?php _e('Loading job details...', 'zaobank'); ?></p>
		</div>
	</div>

</div>

<?php if (is_user_logged_in()) : ?>
	<?php include ZAOBANK_PLUGIN_DIR . 'public/templates/components/bottom-nav.php'; ?>
<?php endif; ?>

<script type="text/template" id="zaobank-job-single-template">
<article class="zaobank-card zaobank-job-detail-card">
	<div class="zaobank-card-body">
		<header class="zaobank-job-detail-header">
			<h1 class="zaobank-job-title">{{title}}</h1>
			<span class="zaobank-badge zaobank-badge-{{status_class}} zaobank-badge-lg">{{status_label}}</span>
		</header>

		<div class="zaobank-job-meta-detailed">
			<div class="zaobank-meta-item">
				<svg class="zaobank-meta-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<circle cx="12" cy="12" r="10"/>
					<polyline points="12 6 12 12 16 14"/>
				</svg>
				<div>
					<span class="zaobank-meta-label"><?php _e('Time Required', 'zaobank'); ?></span>
					<span class="zaobank-meta-value">{{hours}} <?php _e('hours', 'zaobank'); ?></span>
				</div>
			</div>

			{{#if location}}
			<div class="zaobank-meta-item">
				<svg class="zaobank-meta-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
					<circle cx="12" cy="10" r="3"/>
				</svg>
				<div>
					<span class="zaobank-meta-label"><?php _e('Location', 'zaobank'); ?></span>
					<span class="zaobank-meta-value">{{location}}</span>
				</div>
			</div>
			{{/if}}

			{{#if preferred_date}}
			<div class="zaobank-meta-item">
				<svg class="zaobank-meta-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
					<line x1="16" y1="2" x2="16" y2="6"/>
					<line x1="8" y1="2" x2="8" y2="6"/>
					<line x1="3" y1="10" x2="21" y2="10"/>
				</svg>
				<div>
					<span class="zaobank-meta-label"><?php _e('Preferred Date', 'zaobank'); ?></span>
					<span class="zaobank-meta-value">{{preferred_date}}</span>
				</div>
			</div>
			{{/if}}
		</div>

		<div class="zaobank-job-description">
			<h2 class="zaobank-section-title"><?php _e('Description', 'zaobank'); ?></h2>
			<div class="zaobank-prose">{{description}}</div>
		</div>

		{{#if skills_required}}
		<div class="zaobank-job-skills">
			<h2 class="zaobank-section-title"><?php _e('Skills Needed', 'zaobank'); ?></h2>
			<div class="zaobank-tags">
				{{#each skills_required}}
				<span class="zaobank-tag">{{this}}</span>
				{{/each}}
			</div>
		</div>
		{{/if}}

		<div class="zaobank-job-poster-card">
			<h2 class="zaobank-section-title"><?php _e('Posted By', 'zaobank'); ?></h2>
			<a href="<?php echo esc_url($urls['profile']); ?>?user_id={{requester_id}}" class="zaobank-user-card-link">
				<img src="{{requester_avatar}}" alt="" class="zaobank-avatar">
				<div class="zaobank-user-info">
					<span class="zaobank-user-name">{{requester_name}}</span>
					<span class="zaobank-user-since"><?php _e('Member since', 'zaobank'); ?> {{requester_since}}</span>
				</div>
			</a>
		</div>

		{{#if provider_id}}
		<div class="zaobank-job-provider-card">
			<h2 class="zaobank-section-title"><?php _e('Claimed By', 'zaobank'); ?></h2>
			<a href="<?php echo esc_url($urls['profile']); ?>?user_id={{provider_id}}" class="zaobank-user-card-link">
				<img src="{{provider_avatar}}" alt="" class="zaobank-avatar">
				<div class="zaobank-user-info">
					<span class="zaobank-user-name">{{provider_name}}</span>
				</div>
			</a>
		</div>
		{{/if}}
	</div>

	<div class="zaobank-card-footer zaobank-job-actions">
		{{#if can_claim}}
		<button type="button" class="zaobank-btn zaobank-btn-primary zaobank-btn-lg zaobank-btn-block zaobank-claim-job" data-job-id="{{id}}">
			<?php _e('Claim This Job', 'zaobank'); ?>
		</button>
		{{/if}}

		{{#if can_complete}}
		<button type="button" class="zaobank-btn zaobank-btn-success zaobank-btn-lg zaobank-btn-block zaobank-complete-job" data-job-id="{{id}}">
			<?php _e('Mark as Complete', 'zaobank'); ?>
		</button>
		{{/if}}

		{{#if can_edit}}
		<a href="<?php echo esc_url($urls['job_form']); ?>?job_id={{id}}" class="zaobank-btn zaobank-btn-secondary zaobank-btn-block">
			<?php _e('Edit Job', 'zaobank'); ?>
		</a>
		{{/if}}

		{{#if can_message}}
		<a href="<?php echo esc_url($urls['messages']); ?>?user_id={{requester_id}}" class="zaobank-btn zaobank-btn-outline zaobank-btn-block">
			<svg class="zaobank-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
				<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
			</svg>
			<?php _e('Message Poster', 'zaobank'); ?>
		</a>
		{{/if}}

		<button type="button" class="zaobank-btn zaobank-btn-ghost zaobank-btn-sm zaobank-flag-content" data-item-type="job" data-item-id="{{id}}">
			<svg class="zaobank-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
				<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
				<line x1="4" y1="22" x2="4" y2="15"/>
			</svg>
			<?php _e('Report', 'zaobank'); ?>
		</button>
	</div>
</article>
</script>
