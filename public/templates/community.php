<?php
/**
 * Community Template
 *
 * Community directory and worked-with history.
 */

if (!defined('ABSPATH')) {
	exit;
}

$urls = ZAOBank_Shortcodes::get_page_urls();
$community_url = isset($urls['community']) ? $urls['community'] : (isset($urls['messages']) ? $urls['messages'] : '#');
$current_view = isset($view) ? $view : 'community';
if ($current_view === 'worked-with') {
	$current_view = 'address-book';
}
$is_address_book = ($current_view === 'address-book');
?>

<div class="zaobank-container zaobank-community-page" data-component="community" data-view="<?php echo esc_attr($current_view); ?>">

	<header class="zaobank-page-header">
		<h1 class="zaobank-page-title"><?php _e('Community', 'zaobank'); ?></h1>
		<?php
		$tabs = array(
			array('label' => __('community', 'zaobank'), 'url' => $community_url, 'current' => true),
			array('label' => __('exchanges', 'zaobank'), 'url' => $urls['exchanges']),
			array('label' => __('messages', 'zaobank'), 'url' => $urls['messages']),
		);
		include ZAOBANK_PLUGIN_DIR . 'public/templates/components/subpage-tabs.php';
		?>
	</header>

	<div class="zaobank-tabs">
		<button type="button" class="zaobank-tab <?php echo $is_address_book ? '' : 'active'; ?>" data-tab="community">
			<?php _e('Community', 'zaobank'); ?>
		</button>
		<button type="button" class="zaobank-tab <?php echo $is_address_book ? 'active' : ''; ?>" data-tab="address-book">
			<?php _e('Address Book', 'zaobank'); ?>
		</button>
	</div>

	<div class="zaobank-tab-panel <?php echo $is_address_book ? '' : 'active'; ?>" data-panel="community">
		<div class="zaobank-filter-bar">
			<input type="search"
			       class="zaobank-input"
			       data-community-filter="search"
			       placeholder="<?php esc_attr_e('Search people or skills...', 'zaobank'); ?>">
			<input type="text"
			       class="zaobank-input"
			       data-community-filter="skill"
			       placeholder="<?php esc_attr_e('Filter by skill', 'zaobank'); ?>">
			<select class="zaobank-select" data-community-filter="region">
				<option value=""><?php _e('All regions', 'zaobank'); ?></option>
			</select>
			<select class="zaobank-select" data-community-filter="sort">
				<option value="recent"><?php _e('Newest', 'zaobank'); ?></option>
				<option value="name"><?php _e('Name (A–Z)', 'zaobank'); ?></option>
			</select>
			<select class="zaobank-select" data-community-filter="per_page">
				<option value="12">12</option>
				<option value="24">24</option>
				<option value="48">48</option>
			</select>
			<button type="button" id="zaobank-community-filter-toggle" class="zaobank-btn zaobank-btn-outline zaobank-btn-sm">
				<?php _e('Filters', 'zaobank'); ?>
			</button>
		</div>

		<div class="zaobank-filter-row zaobank-filter-row-secondary">
			<div class="zaobank-filter-item">
				<span class="zaobank-filter-summary" data-role="community-summary"><?php _e('Showing 0-0 of 0', 'zaobank'); ?></span>
			</div>
		</div>

		<div class="zaobank-community-filter-overlay zaobank-filter-panel-overlay"></div>
		<aside class="zaobank-community-filter-panel zaobank-filter-panel">
			<div class="zaobank-filter-panel-header">
				<h3><?php _e('Filter by Skill Tags', 'zaobank'); ?></h3>
				<button type="button" class="zaobank-community-filter-close zaobank-btn zaobank-btn-ghost zaobank-btn-sm" aria-label="<?php esc_attr_e('Close', 'zaobank'); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
						<line x1="18" y1="6" x2="6" y2="18"/>
						<line x1="6" y1="6" x2="18" y2="18"/>
					</svg>
				</button>
			</div>
			<div class="zaobank-filter-panel-body">
				<div class="zaobank-checkbox-group">
					<?php
					$tags_field = acf_get_field('field_user_skill_tags');
					$skill_tags = $tags_field ? $tags_field['choices'] : array();
					if (!empty($skill_tags)) :
						foreach ($skill_tags as $value => $label) :
					?>
						<label class="zaobank-checkbox-label">
							<input type="checkbox" name="community_skill_tags[]" value="<?php echo esc_attr($value); ?>">
							<span><?php echo esc_html($label); ?></span>
						</label>
					<?php
						endforeach;
					else :
					?>
						<p class="zaobank-form-hint"><?php _e('No skill tags configured.', 'zaobank'); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</aside>

		<div class="zaobank-community-list" data-loading="true">
			<div class="zaobank-loading-state">
				<div class="zaobank-spinner"></div>
				<p><?php _e('Loading community...', 'zaobank'); ?></p>
			</div>
		</div>

		<div class="zaobank-community-load-more" style="display: none;">
			<button type="button" class="zaobank-btn zaobank-btn-outline zaobank-btn-block" data-action="community-load-more">
				<?php _e('Load More', 'zaobank'); ?>
			</button>
		</div>

		<div class="zaobank-empty-state" data-empty="community" style="display: none;">
			<svg class="zaobank-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
				<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
				<circle cx="12" cy="7" r="4"/>
			</svg>
			<h3><?php _e('No matches yet', 'zaobank'); ?></h3>
			<p><?php _e('Try adjusting your filters to find community members.', 'zaobank'); ?></p>
		</div>
	</div>

	<div class="zaobank-tab-panel <?php echo $is_address_book ? 'active' : ''; ?>" data-panel="address-book">
		<div class="zaobank-address-tabs">
			<button type="button" class="zaobank-address-tab active" data-address-tab="worked-with">
				<?php _e('People I\'ve Worked With', 'zaobank'); ?>
			</button>
			<button type="button" class="zaobank-address-tab" data-address-tab="saved">
				<?php _e('Saved Profiles', 'zaobank'); ?>
			</button>
		</div>

		<div class="zaobank-address-panel active" data-address-panel="worked-with">
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
		</div>

		<div class="zaobank-address-panel" data-address-panel="saved">
			<header class="zaobank-section-header">
				<h2 class="zaobank-section-title"><?php _e('Saved Profiles', 'zaobank'); ?></h2>
				<p class="zaobank-form-hint"><?php _e('Saved profiles are private and only visible to you.', 'zaobank'); ?></p>
			</header>

			<div class="zaobank-saved-profiles-list" data-loading="true">
				<div class="zaobank-loading-state">
					<div class="zaobank-spinner"></div>
					<p><?php _e('Loading saved profiles...', 'zaobank'); ?></p>
				</div>
			</div>

			<div class="zaobank-empty-state" data-empty="saved-profiles" style="display: none;">
				<svg class="zaobank-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
					<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
					<circle cx="12" cy="7" r="4"/>
				</svg>
				<h3><?php _e('No saved profiles yet', 'zaobank'); ?></h3>
				<p><?php _e('Save people from the Community list to build your address book.', 'zaobank'); ?></p>
			</div>
		</div>
	</div>

</div>

<?php include ZAOBANK_PLUGIN_DIR . 'public/templates/components/bottom-nav.php'; ?>

<script type="text/template" id="zaobank-community-card-template">
<div class="zaobank-card zaobank-community-card" data-user-id="{{id}}">
	<div class="zaobank-card-body">
		<div class="zaobank-community-header">
			<div class="zaobank-community-user">
				<img src="{{avatar_url}}" alt="" class="zaobank-avatar">
				<div>
					<span class="zaobank-community-name">{{name}}</span>
					{{#if region}}
					<span class="zaobank-community-region">{{region}}</span>
					{{/if}}
				</div>
			</div>
			{{#if can_save}}
			<button type="button" class="zaobank-btn zaobank-btn-ghost zaobank-btn-sm zaobank-save-profile" data-saved="{{is_saved}}">
				{{#if is_saved}}
				<?php _e('Saved', 'zaobank'); ?>
				{{else}}
				<?php _e('Save', 'zaobank'); ?>
				{{/if}}
			</button>
			{{/if}}
		</div>

		<a href="<?php echo esc_url($urls['profile']); ?>?user_id={{id}}" class="zaobank-community-link">
			<?php _e('View full profile', 'zaobank'); ?>
		</a>

		{{#if skill_tags}}
		<div class="zaobank-tags">
			{{#each skill_tags}}
			<span class="zaobank-tag">{{this.label}}</span>
			{{/each}}
		</div>
		{{/if}}

		{{#if skills}}
		<p class="zaobank-community-skills"><?php _e('Skills summary:', 'zaobank'); ?> {{skills}}</p>
		{{/if}}

		{{#if availability}}
		<p class="zaobank-community-availability"><?php _e('Availability:', 'zaobank'); ?> {{availability}}</p>
		{{/if}}

		<div class="zaobank-community-actions">
			{{#if can_request}}
			<button type="button" class="zaobank-btn zaobank-btn-primary zaobank-btn-sm zaobank-request-skill">
				<?php _e('Send a Request', 'zaobank'); ?>
			</button>
			{{else}}
			<span class="zaobank-form-hint"><?php _e('Requests are available to verified members.', 'zaobank'); ?></span>
			{{/if}}
		</div>

		<div class="zaobank-community-request-form" hidden>
			<div class="zaobank-form-group">
				<label class="zaobank-label" for="request-hours-{{id}}"><?php _e('Estimated hours', 'zaobank'); ?></label>
				<input type="number" id="request-hours-{{id}}" name="request_hours" min="0" step="0.25" class="zaobank-input" placeholder="<?php esc_attr_e('e.g., 2', 'zaobank'); ?>">
			</div>

			<div class="zaobank-form-group">
				<label class="zaobank-label" for="request-details-{{id}}"><?php _e('Describe your request', 'zaobank'); ?></label>
				<textarea id="request-details-{{id}}" name="request_details" class="zaobank-textarea" rows="3" placeholder="<?php esc_attr_e('Share what you need help with...', 'zaobank'); ?>"></textarea>
			</div>

			<div class="zaobank-form-actions">
				<button type="button" class="zaobank-btn zaobank-btn-primary zaobank-btn-sm zaobank-submit-request">
					<?php _e('Send Request', 'zaobank'); ?>
				</button>
				<button type="button" class="zaobank-btn zaobank-btn-ghost zaobank-btn-sm zaobank-cancel-request">
					<?php _e('Cancel', 'zaobank'); ?>
				</button>
			</div>
		</div>
	</div>
</div>
</script>

<script type="text/template" id="zaobank-saved-profile-card-template">
<div class="zaobank-card zaobank-community-card zaobank-saved-profile-card" data-user-id="{{id}}">
	<div class="zaobank-card-body">
		<div class="zaobank-community-header">
			<div class="zaobank-community-user">
				<img src="{{avatar_url}}" alt="" class="zaobank-avatar">
				<div>
					<span class="zaobank-community-name">{{name}}</span>
					{{#if region}}
					<span class="zaobank-community-region">{{region}}</span>
					{{/if}}
				</div>
			</div>
			<button type="button" class="zaobank-btn zaobank-btn-ghost zaobank-btn-sm zaobank-remove-saved">
				<?php _e('Remove', 'zaobank'); ?>
			</button>
		</div>

		<a href="<?php echo esc_url($urls['profile']); ?>?user_id={{id}}" class="zaobank-community-link">
			<?php _e('View full profile', 'zaobank'); ?>
		</a>

		{{#if skill_tags}}
		<div class="zaobank-tags">
			{{#each skill_tags}}
			<span class="zaobank-tag">{{this.label}}</span>
			{{/each}}
		</div>
		{{/if}}

		{{#if skills}}
		<p class="zaobank-community-skills"><?php _e('Skills summary:', 'zaobank'); ?> {{skills}}</p>
		{{/if}}

		{{#if availability}}
		<p class="zaobank-community-availability"><?php _e('Availability:', 'zaobank'); ?> {{availability}}</p>
		{{/if}}

		<div class="zaobank-community-actions">
			{{#if can_request}}
			<button type="button" class="zaobank-btn zaobank-btn-primary zaobank-btn-sm zaobank-request-skill">
				<?php _e('Send a Request', 'zaobank'); ?>
			</button>
			{{else}}
			<span class="zaobank-form-hint"><?php _e('Requests are available to verified members.', 'zaobank'); ?></span>
			{{/if}}
		</div>

		<div class="zaobank-community-request-form" hidden>
			<div class="zaobank-form-group">
				<label class="zaobank-label" for="request-hours-saved-{{id}}"><?php _e('Estimated hours', 'zaobank'); ?></label>
				<input type="number" id="request-hours-saved-{{id}}" name="request_hours" min="0" step="0.25" class="zaobank-input" placeholder="<?php esc_attr_e('e.g., 2', 'zaobank'); ?>">
			</div>

			<div class="zaobank-form-group">
				<label class="zaobank-label" for="request-details-saved-{{id}}"><?php _e('Describe your request', 'zaobank'); ?></label>
				<textarea id="request-details-saved-{{id}}" name="request_details" class="zaobank-textarea" rows="3" placeholder="<?php esc_attr_e('Share what you need help with...', 'zaobank'); ?>"></textarea>
			</div>

			<div class="zaobank-form-actions">
				<button type="button" class="zaobank-btn zaobank-btn-primary zaobank-btn-sm zaobank-submit-request">
					<?php _e('Send Request', 'zaobank'); ?>
				</button>
				<button type="button" class="zaobank-btn zaobank-btn-ghost zaobank-btn-sm zaobank-cancel-request">
					<?php _e('Cancel', 'zaobank'); ?>
				</button>
			</div>
		</div>
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
			{{#if can_message}}
			<a href="<?php echo esc_url($urls['messages']); ?>?user_id={{other_user_id}}" class="zaobank-btn zaobank-btn-outline zaobank-btn-sm">
				<?php _e('Send Message', 'zaobank'); ?>
			</a>
			{{/if}}
		</div>
		<a href="<?php echo esc_url($urls['profile']); ?>?user_id={{other_user_id}}" class="zaobank-community-link">
			<?php _e('View full profile', 'zaobank'); ?>
		</a>

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
