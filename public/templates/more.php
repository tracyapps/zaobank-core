<?php
/**
 * More Template
 *
 * Messages, job notifications, and profile edit shortcuts.
 */

if (!defined('ABSPATH')) {
	exit;
}

$urls = ZAOBank_Shortcodes::get_page_urls();
$current_view = isset($view)
	? sanitize_key($view)
	: (isset($_GET['view']) ? sanitize_key(wp_unslash($_GET['view'])) : 'messages');
$valid_views = array('messages', 'updates', 'settings');
if (!in_array($current_view, $valid_views, true)) {
	$current_view = 'messages';
}
$is_updates_view = ($current_view === 'updates');
$is_settings_view = ($current_view === 'settings');

$message_mode_options = class_exists('ZAOBank_Notifications') ? ZAOBank_Notifications::get_message_mode_labels() : array();
$digest_frequency_options = class_exists('ZAOBank_Notifications') ? ZAOBank_Notifications::get_digest_frequency_labels() : array('daily' => __('Daily', 'zaobank'), 'weekly' => __('Weekly', 'zaobank'));

$regions = get_terms(array(
	'taxonomy' => 'zaobank_region',
	'hide_empty' => false,
	'orderby' => 'name',
	'order' => 'ASC'
));
if (!is_array($regions) || is_wp_error($regions)) {
	$regions = array();
}

$job_types = get_terms(array(
	'taxonomy' => 'zaobank_job_type',
	'hide_empty' => false,
	'orderby' => 'name',
	'order' => 'ASC'
));
if (!is_array($job_types) || is_wp_error($job_types)) {
	$job_types = array();
}
?>

<div class="zaobank-container zaobank-more-page" data-component="<?php echo $is_settings_view ? 'user-settings' : 'messages'; ?>"<?php if ($is_updates_view) echo ' data-view="updates"'; ?>>

	<header class="zaobank-page-header">
		<h1 class="zaobank-page-title"><?php _e('More', 'zaobank'); ?></h1>
		<?php
		$tabs = array(
			array('label' => __('messages', 'zaobank'), 'url' => $urls['more'], 'current' => ($current_view === 'messages')),
			array('label' => __('job notifications', 'zaobank'), 'url' => $urls['more'] . '?view=updates', 'current' => $is_updates_view),
			array('label' => __('settings', 'zaobank'), 'url' => $urls['more'] . '?view=settings', 'current' => $is_settings_view),
			array('label' => __('update profile', 'zaobank'), 'url' => $urls['profile_edit']),
		);
		include ZAOBANK_PLUGIN_DIR . 'public/templates/components/subpage-tabs.php';
		?>
	</header>

	<?php if ($is_settings_view) : ?>
	<form id="zaobank-user-settings-form" class="zaobank-form zaobank-user-settings-form" data-loading="true">
		<div class="zaobank-card">
			<div class="zaobank-card-body">
				<div class="zaobank-form-group">
					<label for="zaobank-message-notification-mode" class="zaobank-label"><?php _e('New message notifications', 'zaobank'); ?></label>
					<select id="zaobank-message-notification-mode" name="message_notification_mode" class="zaobank-select">
						<?php foreach ($message_mode_options as $value => $label) : ?>
							<option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="zaobank-form-hint"><?php _e('SMS and Discord delivery require a connected provider integration.', 'zaobank'); ?></p>
				</div>

				<div class="zaobank-form-group">
					<label class="zaobank-checkbox-label">
						<input type="checkbox" name="directory_visible" value="1">
						<span><?php _e('Show my profile in the Community directory', 'zaobank'); ?></span>
					</label>
					<p class="zaobank-form-hint"><?php _e('Turn this off to hide your profile from community listings.', 'zaobank'); ?></p>
				</div>

				<div class="zaobank-form-group">
					<label class="zaobank-checkbox-label">
						<input type="checkbox" name="available_for_requests" value="1">
						<span><?php _e('Available for direct skill requests', 'zaobank'); ?></span>
					</label>
					<p class="zaobank-form-hint"><?php _e('Keep your profile visible but pause incoming request actions.', 'zaobank'); ?></p>
				</div>

				<div class="zaobank-form-group">
					<label class="zaobank-checkbox-label">
						<input type="checkbox" name="job_updates_email" value="1">
						<span><?php _e('Email me when job status updates are sent to me', 'zaobank'); ?></span>
					</label>
				</div>

				<div class="zaobank-form-group">
					<label class="zaobank-checkbox-label">
						<input type="checkbox" name="appreciations_email" value="1">
						<span><?php _e('Email me when I receive appreciation', 'zaobank'); ?></span>
					</label>
				</div>

				<hr>

				<div class="zaobank-form-group">
					<label class="zaobank-checkbox-label">
						<input type="checkbox" name="jobs_digest_enabled" value="1">
						<span><?php _e('Send me open jobs digest emails', 'zaobank'); ?></span>
					</label>
				</div>

				<div class="zaobank-form-group">
					<label for="zaobank-jobs-digest-frequency" class="zaobank-label"><?php _e('Digest frequency', 'zaobank'); ?></label>
					<select id="zaobank-jobs-digest-frequency" name="jobs_digest_frequency" class="zaobank-select">
						<?php foreach ($digest_frequency_options as $value => $label) : ?>
							<option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="zaobank-form-group">
					<label for="zaobank-jobs-digest-limit" class="zaobank-label"><?php _e('Jobs per digest', 'zaobank'); ?></label>
					<select id="zaobank-jobs-digest-limit" name="jobs_digest_limit" class="zaobank-select">
						<option value="5">5</option>
						<option value="10">10</option>
						<option value="15">15</option>
						<option value="20">20</option>
						<option value="30">30</option>
					</select>
				</div>

				<div class="zaobank-form-group">
					<label class="zaobank-label"><?php _e('Digest regions (optional)', 'zaobank'); ?></label>
					<div class="zaobank-checkbox-group">
						<?php if (!empty($regions)) : ?>
							<?php foreach ($regions as $region) : ?>
								<label class="zaobank-checkbox-label">
									<input type="checkbox" name="jobs_digest_regions[]" value="<?php echo esc_attr((int) $region->term_id); ?>">
									<span><?php echo esc_html($region->name); ?></span>
								</label>
							<?php endforeach; ?>
						<?php else : ?>
							<p class="zaobank-form-hint"><?php _e('No regions found.', 'zaobank'); ?></p>
						<?php endif; ?>
					</div>
				</div>

				<div class="zaobank-form-group">
					<label class="zaobank-label"><?php _e('Digest job types (optional)', 'zaobank'); ?></label>
					<div class="zaobank-checkbox-group">
						<?php if (!empty($job_types)) : ?>
							<?php foreach ($job_types as $job_type) : ?>
								<label class="zaobank-checkbox-label">
									<input type="checkbox" name="jobs_digest_job_types[]" value="<?php echo esc_attr((int) $job_type->term_id); ?>">
									<span><?php echo esc_html($job_type->name); ?></span>
								</label>
							<?php endforeach; ?>
						<?php else : ?>
							<p class="zaobank-form-hint"><?php _e('No job types found.', 'zaobank'); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<div class="zaobank-card-footer">
				<button type="submit" class="zaobank-btn zaobank-btn-primary zaobank-btn-block">
					<?php _e('Save Settings', 'zaobank'); ?>
				</button>
			</div>
		</div>
	</form>
	<?php else : ?>
	<?php if (!$is_updates_view) : ?>
	<div class="zaobank-message-search" data-component="message-search">
		<label for="zaobank-message-search-input" class="zaobank-sr-only"><?php _e('Start a new message', 'zaobank'); ?></label>
		<input type="search"
		       id="zaobank-message-search-input"
		       class="zaobank-input"
		       data-action="message-user-search"
		       placeholder="<?php esc_attr_e('Start a new message...', 'zaobank'); ?>">
		<div class="zaobank-message-search-results" aria-live="polite"></div>
	</div>
	<?php endif; ?>

	<!-- Conversations / Updates List -->
	<div class="zaobank-conversations-list" data-loading="true">
		<div class="zaobank-loading-state">
			<div class="zaobank-spinner"></div>
			<p><?php echo $is_updates_view ? esc_html__('Loading job notifications...', 'zaobank') : esc_html__('Loading conversations...', 'zaobank'); ?></p>
		</div>
	</div>

	<!-- Empty State -->
	<div class="zaobank-empty-state" style="display: none;">
		<svg class="zaobank-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
			<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
		</svg>
		<?php if ($is_updates_view) : ?>
		<h3><?php _e('No job updates yet', 'zaobank'); ?></h3>
		<p><?php _e('Job-related notifications will appear here when jobs are completed or released.', 'zaobank'); ?></p>
		<?php else : ?>
		<h3><?php _e('No messages yet', 'zaobank'); ?></h3>
		<p><?php _e('When you message someone or they message you, your conversations will appear here.', 'zaobank'); ?></p>
		<?php endif; ?>
		<a href="<?php echo esc_url($urls['jobs']); ?>" class="zaobank-btn zaobank-btn-primary">
			<?php _e('Browse Jobs', 'zaobank'); ?>
		</a>
	</div>
	<?php endif; ?>

</div>

<?php include ZAOBANK_PLUGIN_DIR . 'public/templates/components/bottom-nav.php'; ?>

<script type="text/template" id="zaobank-conversation-item-template">
<div class="zaobank-conversation-item-wrapper">
	<a href="<?php echo esc_url($urls['messages']); ?>?user_id={{other_user_id}}" class="zaobank-conversation-item {{#if has_unread}}unread{{/if}}">
		<img src="{{other_user_avatar}}" alt="" class="zaobank-avatar">
		<div class="zaobank-conversation-content">
			<div class="zaobank-conversation-header">
				<span class="zaobank-conversation-name">{{other_user_name}}</span>
				{{#if other_user_pronouns}}
				<span class="zaobank-name-pronouns">({{other_user_pronouns}})</span>
				{{/if}}
				<span class="zaobank-conversation-time">{{last_message_time}}</span>
			</div>
			<p class="zaobank-conversation-preview">{{last_message_preview}}</p>
		</div>
		{{#if unread_count}}
		<span class="zaobank-conversation-badge" data-unread-count="{{unread_count}}">{{unread_count}}</span>
		{{/if}}
	</a>
	<div class="zaobank-conversation-actions">
		{{#if has_unread}}
		<button type="button" class="zaobank-btn zaobank-btn-ghost zaobank-btn-sm zaobank-mark-read" data-user-id="{{other_user_id}}">
			<?php _e('Mark Read', 'zaobank'); ?>
		</button>
		{{/if}}
		<button type="button" class="zaobank-btn zaobank-btn-ghost zaobank-btn-sm zaobank-archive-conversation" data-user-id="{{other_user_id}}">
			<?php _e('Archive', 'zaobank'); ?>
		</button>
	</div>
</div>
</script>
<?php if (!$is_settings_view) : ?>

<script type="text/template" id="zaobank-job-update-template">
<div class="zaobank-card zaobank-job-update-card">
	<div class="zaobank-card-body">
		<div class="zaobank-job-update-header">
			<img src="{{other_user_avatar}}" alt="" class="zaobank-avatar-small">
			<div>
				<span class="zaobank-job-update-user">{{other_user_name}}</span>
				{{#if other_user_pronouns}}
				<span class="zaobank-name-pronouns">({{other_user_pronouns}})</span>
				{{/if}}
				<span class="zaobank-job-update-time">{{time}}</span>
			</div>
		</div>
		<p class="zaobank-job-update-message">{{message}}</p>
		{{#if job_id}}
		<a href="<?php echo esc_url($urls['jobs']); ?>?job_id={{job_id}}" class="zaobank-btn zaobank-btn-outline zaobank-btn-sm">
			<?php _e('View Job', 'zaobank'); ?>
		</a>
		{{/if}}
	</div>
</div>
</script>

<script type="text/template" id="zaobank-message-search-item-template">
<a href="<?php echo esc_url($urls['messages']); ?>?user_id={{id}}" class="zaobank-message-search-item">
	<img src="{{avatar_url}}" alt="" class="zaobank-avatar-small">
	<span class="zaobank-message-search-name">{{name}}</span>
	{{#if pronouns}}
	<span class="zaobank-name-pronouns">({{pronouns}})</span>
	{{/if}}
</a>
</script>
<?php endif; ?>
