<?php
/**
 * Messages Template
 *
 * Inbox/conversations list.
 */

if (!defined('ABSPATH')) {
	exit;
}

$urls = ZAOBank_Shortcodes::get_page_urls();
$community_url = isset($urls['community']) ? $urls['community'] : (isset($urls['messages']) ? $urls['messages'] : '#');
$current_view = isset($view) ? $view : 'messages';
$is_updates_view = ($current_view === 'updates');
?>

<div class="zaobank-container zaobank-messages-page" data-component="messages"<?php if ($is_updates_view) echo ' data-view="updates"'; ?>>

	<header class="zaobank-page-header">
		<h1 class="zaobank-page-title"><?php _e('Messages', 'zaobank'); ?></h1>
		<?php
		$tabs = array(
			array('label' => __('community', 'zaobank'), 'url' => $community_url),
			array('label' => __('exchanges', 'zaobank'), 'url' => $urls['exchanges']),
			array('label' => __('messages', 'zaobank'), 'url' => $urls['messages'], 'current' => !$is_updates_view),
			array('label' => __('job updates', 'zaobank'), 'url' => $urls['messages'] . '?view=updates', 'current' => $is_updates_view),
		);
		include ZAOBANK_PLUGIN_DIR . 'public/templates/components/subpage-tabs.php';
		?>
	</header>

	<div class="zaobank-message-search" data-component="message-search">
		<label for="zaobank-message-search-input" class="zaobank-sr-only"><?php _e('Start a new message', 'zaobank'); ?></label>
		<input type="search"
		       id="zaobank-message-search-input"
		       class="zaobank-input"
		       data-action="message-user-search"
		       placeholder="<?php esc_attr_e('Start a new message...', 'zaobank'); ?>">
		<div class="zaobank-message-search-results" aria-live="polite"></div>
	</div>

	<!-- Conversations List -->
	<div class="zaobank-conversations-list" data-loading="true">
		<div class="zaobank-loading-state">
			<div class="zaobank-spinner"></div>
			<p><?php _e('Loading conversations...', 'zaobank'); ?></p>
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

</div>

<?php include ZAOBANK_PLUGIN_DIR . 'public/templates/components/bottom-nav.php'; ?>

<script type="text/template" id="zaobank-conversation-item-template">
<div class="zaobank-conversation-item-wrapper">
	<a href="<?php echo esc_url($urls['messages']); ?>?user_id={{other_user_id}}" class="zaobank-conversation-item {{#if has_unread}}unread{{/if}}">
		<img src="{{other_user_avatar}}" alt="" class="zaobank-avatar">
		<div class="zaobank-conversation-content">
			<div class="zaobank-conversation-header">
				<span class="zaobank-conversation-name">{{other_user_name}}</span>
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

<script type="text/template" id="zaobank-job-update-template">
<div class="zaobank-card zaobank-job-update-card">
	<div class="zaobank-card-body">
		<div class="zaobank-job-update-header">
			<img src="{{other_user_avatar}}" alt="" class="zaobank-avatar-small">
			<div>
				<span class="zaobank-job-update-user">{{other_user_name}}</span>
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
</a>
</script>
