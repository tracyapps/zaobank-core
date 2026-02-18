<?php
/**
 * Conversation Template
 *
 * Single conversation thread with a user.
 */

if (!defined('ABSPATH')) {
	exit;
}

$other_user_id = isset($user_id) ? (int) $user_id : 0;
$other_user = get_userdata($other_user_id);
$urls = ZAOBank_Shortcodes::get_page_urls();
$other_user_pronouns = $other_user_id ? get_user_meta($other_user_id, 'user_pronouns', true) : '';

if (!$other_user) {
	echo '<div class="zaobank-error">' . __('User not found.', 'zaobank') . '</div>';
	return;
}
?>

<div class="zaobank-container zaobank-conversation-page" data-component="conversation" data-user-id="<?php echo esc_attr($other_user_id); ?>">

	<!-- Conversation Header -->
	<header class="zaobank-conversation-header">
		<a href="<?php echo esc_url($urls['messages']); ?>" class="zaobank-back-btn" aria-label="<?php esc_attr_e('Back to messages', 'zaobank'); ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
				<line x1="19" y1="12" x2="5" y2="12"/>
				<polyline points="12 19 5 12 12 5"/>
			</svg>
		</a>
		<a href="<?php echo esc_url($urls['profile']); ?>?user_id=<?php echo esc_attr($other_user_id); ?>" class="zaobank-conversation-user">
			<img src="<?php echo esc_url(get_avatar_url($other_user_id, array('size' => 40))); ?>" alt="" class="zaobank-avatar-small">
			<span class="zaobank-conversation-user-name"><?php echo esc_html($other_user->display_name); ?></span>
			<?php if (!empty($other_user_pronouns)) : ?>
				<span class="zaobank-name-pronouns">(<?php echo esc_html($other_user_pronouns); ?>)</span>
			<?php endif; ?>
		</a>
	</header>

	<!-- Messages Area -->
	<div class="zaobank-messages-container">
		<div class="zaobank-messages-list" data-loading="true">
			<div class="zaobank-loading-state">
				<div class="zaobank-spinner"></div>
				<p><?php _e('Loading messages...', 'zaobank'); ?></p>
			</div>
		</div>
	</div>

	<div class="zaobank-conversation-intent-tools">
		<button type="button"
		        class="zaobank-btn zaobank-btn-outline zaobank-btn-sm zaobank-open-job-intent"
		        data-intent="request">
			<?php _e('Create Request', 'zaobank'); ?>
		</button>
		<button type="button"
		        class="zaobank-btn zaobank-btn-outline zaobank-btn-sm zaobank-open-job-intent"
		        data-intent="offer">
			<?php _e('Offer Help', 'zaobank'); ?>
		</button>
	</div>

	<form class="zaobank-job-intent-form" data-component="job-intent-form" hidden>
		<input type="hidden" name="intent" value="request">
		<input type="hidden" name="message_id" value="">
		<div class="zaobank-card">
			<div class="zaobank-card-body">
				<h3 data-role="intent-heading"><?php _e('Create Job Request', 'zaobank'); ?></h3>
				<div class="zaobank-form-group">
					<label class="zaobank-label" for="zaobank-intent-title"><?php _e('Title', 'zaobank'); ?></label>
					<input type="text" id="zaobank-intent-title" name="title" class="zaobank-input" maxlength="120" required>
				</div>
				<div class="zaobank-form-group">
					<label class="zaobank-label" for="zaobank-intent-hours"><?php _e('Estimated hours', 'zaobank'); ?></label>
					<input type="number" id="zaobank-intent-hours" name="hours" min="0.25" step="0.25" class="zaobank-input" required>
				</div>
				<div class="zaobank-form-group">
					<label class="zaobank-label" for="zaobank-intent-details"><?php _e('Details', 'zaobank'); ?></label>
					<textarea id="zaobank-intent-details" name="details" rows="3" class="zaobank-textarea" required></textarea>
				</div>
				<div class="zaobank-job-intent-actions">
					<button type="submit" class="zaobank-btn zaobank-btn-primary">
						<span data-role="intent-submit-label"><?php _e('Send Request', 'zaobank'); ?></span>
					</button>
					<button type="button" class="zaobank-btn zaobank-btn-ghost zaobank-cancel-job-intent">
						<?php _e('Cancel', 'zaobank'); ?>
					</button>
				</div>
			</div>
		</div>
	</form>

	<!-- Message Composer -->
	<form class="zaobank-message-composer" data-component="message-form">
		<div class="zaobank-composer-input-wrapper">
			<label for="message-input" class="zaobank-sr-only"><?php _e('Type a message', 'zaobank'); ?></label>
			<textarea id="message-input"
			          name="message"
			          class="zaobank-composer-input"
			          placeholder="<?php esc_attr_e('Type a message...', 'zaobank'); ?>"
			          rows="1"
			          required></textarea>
		</div>
		<button type="submit" class="zaobank-composer-send" aria-label="<?php esc_attr_e('Send message', 'zaobank'); ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
				<line x1="22" y1="2" x2="11" y2="13"/>
				<polygon points="22 2 15 22 11 13 2 9 22 2"/>
			</svg>
		</button>
	</form>

</div>

<script type="text/template" id="zaobank-message-template">
<div class="zaobank-message {{#if is_own}}zaobank-message-own{{/if}}{{#unless is_own}}zaobank-message-other{{/unless}}" data-message-id="{{id}}">
	<div class="zaobank-message-bubble">
		{{#if is_intent}}
		<div class="zaobank-message-intent zaobank-message-intent-{{intent_type}}">
			<span class="zaobank-badge zaobank-badge-primary">{{intent_label}}</span>
			<p class="zaobank-message-intent-title">{{intent_title}}</p>
			<p class="zaobank-message-intent-hours"><?php _e('Estimated hours:', 'zaobank'); ?> {{intent_hours}}</p>
			{{#if intent_details}}
			<p class="zaobank-message-intent-details">{{intent_details}}</p>
			{{/if}}
			{{#if job_id}}
			<a href="<?php echo esc_url($urls['jobs']); ?>?job_id={{job_id}}" class="zaobank-btn zaobank-btn-ghost zaobank-btn-sm">
				<?php _e('View Job', 'zaobank'); ?>
			</a>
			{{/if}}
			{{#if can_accept_intent}}
			<button type="button"
			        class="zaobank-btn zaobank-btn-primary zaobank-btn-sm zaobank-accept-job-intent"
			        data-message-id="{{id}}"
			        data-hours="{{intent_hours_input}}">
				<?php _e('Accept and Create Job', 'zaobank'); ?>
			</button>
			{{/if}}
		</div>
		{{else}}
		<p class="zaobank-message-text">{{message}}</p>
		{{#if can_convert_intent}}
		<div class="zaobank-message-inline-actions">
			<button type="button"
			        class="zaobank-message-inline-action zaobank-convert-message-intent"
			        data-intent="request"
			        data-message-id="{{id}}"
			        data-message-text="{{message_attr}}">
				<?php _e('Make Request', 'zaobank'); ?>
			</button>
			<button type="button"
			        class="zaobank-message-inline-action zaobank-convert-message-intent"
			        data-intent="offer"
			        data-message-id="{{id}}"
			        data-message-text="{{message_attr}}">
				<?php _e('Offer Help', 'zaobank'); ?>
			</button>
		</div>
		{{/if}}
		{{/if}}
		<span class="zaobank-message-time">{{time}}</span>
	</div>
</div>
</script>

<script type="text/template" id="zaobank-message-date-template">
<div class="zaobank-message-date-divider">
	<span>{{date}}</span>
</div>
</script>
