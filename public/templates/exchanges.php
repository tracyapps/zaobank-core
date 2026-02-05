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
		<h1 class="zaobank-page-title"><?php _e('Exchange History', 'zaobank'); ?></h1>
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

	<!-- People Worked With -->
	<section class="zaobank-worked-with-section">
		<header class="zaobank-section-header">
			<h2 class="zaobank-section-title"><?php _e('People You\'ve Worked With', 'zaobank'); ?></h2>
			<p class="zaobank-form-hint"><?php _e('Private notes are for your reference only and are never visible to anyone else.', 'zaobank'); ?></p>
		</header>

		<div class="zaobank-worked-with-list" data-loading="true">
			<div class="zaobank-loading-state">
				<div class="zaobank-spinner"></div>
				<p><?php _e('Loading people...', 'zaobank'); ?></p>
			</div>
		</div>

		<div class="zaobank-empty-state" data-empty="worked-with" style="display: none;">
			<svg class="zaobank-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
				<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
				<circle cx="12" cy="7" r="4"/>
			</svg>
			<h3><?php _e('No history yet', 'zaobank'); ?></h3>
			<p><?php _e('Once you complete exchanges, people you\'ve worked with will show up here.', 'zaobank'); ?></p>
		</div>
	</section>

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
			</a>
			<span class="zaobank-exchange-date">{{date}}</span>
		</div>

		<div class="zaobank-exchange-status" data-role="appreciation-status" {{appreciation_status_hidden}}>
			<span class="zaobank-badge zaobank-badge-success"><?php _e('Appreciation sent', 'zaobank'); ?></span>
		</div>

		{{#unless has_appreciation}}
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
		{{/unless}}
	</div>
</div>
</script>

<script type="text/template" id="zaobank-worked-with-item-template">
<div class="zaobank-card zaobank-worked-with-card" data-user-id="{{other_user_id}}">
	<div class="zaobank-card-body">
		<div class="zaobank-worked-with-header">
			<div class="zaobank-worked-with-user">
				<img src="{{other_user_avatar}}" alt="" class="zaobank-avatar">
				<div>
					<span class="zaobank-worked-with-name">{{other_user_name}}</span>
					<span class="zaobank-worked-with-meta">
						<?php _e('You completed', 'zaobank'); ?> {{jobs_provided}} <?php _e('jobs for them', 'zaobank'); ?>
						• <?php _e('They completed', 'zaobank'); ?> {{jobs_received}} <?php _e('jobs for you', 'zaobank'); ?>
					</span>
				</div>
			</div>
			<a href="<?php echo esc_url($urls['messages']); ?>?user_id={{other_user_id}}" class="zaobank-btn zaobank-btn-outline zaobank-btn-sm">
				<?php _e('Send Message', 'zaobank'); ?>
			</a>
		</div>

		<div class="zaobank-worked-with-summary">
			<span><?php _e('Total exchanges:', 'zaobank'); ?> {{total_exchanges}}</span>
			<span><?php _e('Total hours:', 'zaobank'); ?> {{total_hours}}</span>
			{{#if last_exchange_at}}
			<span><?php _e('Last exchange:', 'zaobank'); ?> {{last_exchange_at}}</span>
			{{/if}}
		</div>

		<div class="zaobank-worked-with-notes">
			{{#if has_latest_note}}
			<div class="zaobank-worked-with-latest" data-role="latest-note-wrapper">
				<span class="zaobank-tag" data-role="latest-note-tag">{{latest_note_tag_label}}</span>
				{{#if latest_note_text}}
				<p data-role="latest-note-text">{{latest_note_text}}</p>
				{{/if}}
			</div>
			{{/if}}

			{{#unless has_latest_note}}
			<div class="zaobank-worked-with-latest" data-role="latest-note-wrapper" hidden>
				<span class="zaobank-tag" data-role="latest-note-tag"></span>
				<p data-role="latest-note-text"></p>
			</div>
			{{/unless}}

			<div class="zaobank-form-group">
				<label class="zaobank-label"><?php _e('Private note tag', 'zaobank'); ?></label>
				<select name="note_tag" class="zaobank-select">
					<option value=""><?php _e('Select a tag', 'zaobank'); ?></option>
					{{#each note_tags}}
					<option value="{{this.slug}}">{{this.label}}</option>
					{{/each}}
				</select>
				{{#unless has_note_tags}}
				<p class="zaobank-form-hint"><?php _e('No private note tags configured.', 'zaobank'); ?></p>
				{{/unless}}
			</div>

			<div class="zaobank-form-group">
				<label class="zaobank-label" for="note-text-{{other_user_id}}"><?php _e('Note (optional, only visible to you)', 'zaobank'); ?></label>
				<textarea id="note-text-{{other_user_id}}" name="note_text" class="zaobank-textarea" rows="3" placeholder="<?php esc_attr_e('Add a private reminder...', 'zaobank'); ?>"></textarea>
			</div>

			<button type="button" class="zaobank-btn zaobank-btn-primary zaobank-btn-sm zaobank-save-note">
				<?php _e('Save Note', 'zaobank'); ?>
			</button>
		</div>
	</div>
</div>
</script>
