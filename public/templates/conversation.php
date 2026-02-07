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
<div class="zaobank-message {{#if is_own}}zaobank-message-own{{else}}zaobank-message-other{{/if}}">
	<div class="zaobank-message-bubble">
		<p class="zaobank-message-text">{{message}}</p>
		<span class="zaobank-message-time">{{time}}</span>
	</div>
</div>
</script>

<script type="text/template" id="zaobank-message-date-template">
<div class="zaobank-message-date-divider">
	<span>{{date}}</span>
</div>
</script>
