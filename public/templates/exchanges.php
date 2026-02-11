<?php
/**
 * Exchanges Template
 *
 * Exchange history list.
 */

if (!defined('ABSPATH')) {
	exit;
}

$urls = ZAOBank_Shortcodes::get_page_urls();
?>

<div class="zaobank-container zaobank-exchanges-page" data-component="exchanges">

	<header class="zaobank-page-header">
		<h1 class="zaobank-page-title"><?php _e('Jobs', 'zaobank'); ?></h1>
		<?php
		$tabs = array(
			array('label' => __('all jobs', 'zaobank'), 'url' => $urls['jobs']),
			array('label' => __('my jobs', 'zaobank'), 'url' => $urls['my_jobs']),
		);
		if (ZAOBank_Security::user_has_member_access()) {
			$tabs[] = array('label' => __('new job', 'zaobank'), 'url' => $urls['job_form']);
		}
		$tabs[] = array('label' => __('job history', 'zaobank'), 'url' => $urls['exchanges'], 'current' => true);
		include ZAOBANK_PLUGIN_DIR . 'public/templates/components/subpage-tabs.php';
		?>
	</header>

	<!-- Balance Summary -->
	<div class="zaobank-card zaobank-balance-card">
		<div class="zaobank-card-body">
			<div class="zaobank-balance-display" data-loading="true">
				<div class="zaobank-loading-placeholder"><?php _e('Loading balance...', 'zaobank'); ?></div>
			</div>
		</div>
	</div>

	<!-- Filter Tabs -->
	<div class="zaobank-tabs zaobank-tabs-compact">
		<button type="button" class="zaobank-tab active" data-filter="all">
			<?php _e('All', 'zaobank'); ?>
		</button>
		<button type="button" class="zaobank-tab" data-filter="earned">
			<?php _e('Earned', 'zaobank'); ?>
		</button>
		<button type="button" class="zaobank-tab" data-filter="spent">
			<?php _e('Spent', 'zaobank'); ?>
		</button>
	</div>

	<!-- Exchanges List -->
	<div class="zaobank-exchanges-list" data-loading="true">
		<div class="zaobank-loading-state">
			<div class="zaobank-spinner"></div>
			<p><?php _e('Loading exchanges...', 'zaobank'); ?></p>
		</div>
	</div>

	<!-- Load More -->
	<div class="zaobank-load-more" style="display: none;">
		<button type="button" class="zaobank-btn zaobank-btn-outline zaobank-btn-block" data-action="load-more">
			<?php _e('Load More', 'zaobank'); ?>
		</button>
	</div>

	<!-- Empty State -->
	<div class="zaobank-empty-state" style="display: none;">
		<svg class="zaobank-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
			<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
			<polyline points="17 6 23 6 23 12"/>
		</svg>
		<h3><?php _e('No exchanges yet', 'zaobank'); ?></h3>
		<p><?php _e('Exchanges are recorded when jobs are completed. Start by claiming or posting a job!', 'zaobank'); ?></p>
		<a href="<?php echo esc_url($urls['jobs']); ?>" class="zaobank-btn zaobank-btn-primary">
			<?php _e('Browse Jobs', 'zaobank'); ?>
		</a>
	</div>

</div>

<?php include ZAOBANK_PLUGIN_DIR . 'public/templates/components/bottom-nav.php'; ?>

<script type="text/template" id="zaobank-exchange-item-template">
<div class="zaobank-card zaobank-exchange-card {{type}}" data-exchange-id="{{id}}">
	<div class="zaobank-card-body">
		<div class="zaobank-exchange-header">
			<div class="zaobank-exchange-type {{type}}">
				{{#if is_earned}}
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<line x1="12" y1="19" x2="12" y2="5"/>
					<polyline points="5 12 12 5 19 12"/>
				</svg>
				<span><?php _e('Earned', 'zaobank'); ?></span>
				{{else}}
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<line x1="12" y1="5" x2="12" y2="19"/>
					<polyline points="19 12 12 19 5 12"/>
				</svg>
				<span><?php _e('Spent', 'zaobank'); ?></span>
				{{/if}}
			</div>
			<span class="zaobank-exchange-hours {{type}}">{{#if is_earned}}+{{else}}-{{/if}}{{hours}} <?php _e('hrs', 'zaobank'); ?></span>
		</div>

		<h3 class="zaobank-exchange-title">
			<a href="<?php echo esc_url($urls['jobs']); ?>?job_id={{job_id}}">{{job_title}}</a>
		</h3>

		<div class="zaobank-exchange-meta">
			<a href="<?php echo esc_url($urls['profile']); ?>?user_id={{other_user_id}}" class="zaobank-exchange-user">
				<img src="{{other_user_avatar}}" alt="" class="zaobank-avatar-tiny">
				<span>{{#if is_earned}}<?php _e('From', 'zaobank'); ?>{{else}}<?php _e('To', 'zaobank'); ?>{{/if}} {{other_user_name}}</span>
				{{#if other_user_pronouns}}
				<span class="zaobank-name-pronouns">({{other_user_pronouns}})</span>
				{{/if}}
			</a>
			<span class="zaobank-exchange-date">{{date}}</span>
		</div>

		{{#if is_earned}}
		{{#if appreciation_received}}
		<div class="zaobank-exchange-status">
			<a href="<?php echo esc_url($urls['appreciations']); ?>?tab=received" class="zaobank-btn zaobank-btn-outline zaobank-btn-sm">
				<svg class="zaobank-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
				</svg>
				<?php _e('View Appreciation', 'zaobank'); ?>
			</a>
		</div>
		{{/if}}
		{{else}}
		{{#if appreciation_given}}
		<div class="zaobank-exchange-status">
			<a href="<?php echo esc_url($urls['appreciations']); ?>?tab=given" class="zaobank-btn zaobank-btn-outline zaobank-btn-sm">
				<svg class="zaobank-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
				</svg>
				<?php _e('View Appreciation', 'zaobank'); ?>
			</a>
		</div>
		{{else}}
		<div class="zaobank-exchange-actions">
			<button type="button" class="zaobank-btn zaobank-btn-outline zaobank-btn-sm zaobank-give-appreciation" data-exchange-id="{{id}}" data-user-id="{{other_user_id}}">
				<svg class="zaobank-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
				</svg>
				<?php _e('Give Appreciation', 'zaobank'); ?>
			</button>
		</div>

		<div class="zaobank-appreciation-form" data-exchange-id="{{id}}" data-user-id="{{other_user_id}}" hidden>
			<div class="zaobank-form-group">
				<label class="zaobank-label"><?php _e('Choose appreciation tags', 'zaobank'); ?></label>
				<div class="zaobank-checkbox-group">
					{{#each appreciation_tags}}
					<label class="zaobank-checkbox-label">
						<input type="checkbox" name="appreciation_tags[]" value="{{this.slug}}">
						<span>{{this.label}}</span>
					</label>
					{{/each}}
					{{#unless has_appreciation_tags}}
					<p class="zaobank-form-hint"><?php _e('No appreciation tags configured.', 'zaobank'); ?></p>
					{{/unless}}
				</div>
			</div>

			<div class="zaobank-form-group">
				<label class="zaobank-label" for="appreciation-message-{{id}}"><?php _e('Add a message (optional)', 'zaobank'); ?></label>
				<textarea id="appreciation-message-{{id}}" name="appreciation_message" class="zaobank-textarea" rows="3" placeholder="<?php esc_attr_e('Share a short thank you...', 'zaobank'); ?>"></textarea>
			</div>

			<div class="zaobank-form-actions">
				<button type="button" class="zaobank-btn zaobank-btn-primary zaobank-btn-sm zaobank-submit-appreciation">
					<?php _e('Send Appreciation', 'zaobank'); ?>
				</button>
				<button type="button" class="zaobank-btn zaobank-btn-ghost zaobank-btn-sm zaobank-cancel-appreciation">
					<?php _e('Cancel', 'zaobank'); ?>
				</button>
			</div>
		</div>
		{{/if}}
		{{/if}}
	</div>
</div>
</script>
