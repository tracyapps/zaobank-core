<?php
/**
 * Appreciations Template
 *
 * Appreciations received/given.
 */

if (!defined('ABSPATH')) {
	exit;
}

$profile_user_id = isset($user_id) ? (int) $user_id : get_current_user_id();
$is_own = (is_user_logged_in() && $profile_user_id === get_current_user_id());
$user = get_userdata($profile_user_id);
$urls = ZAOBank_Shortcodes::get_page_urls();

if (!$user) {
	echo '<div class="zaobank-error">' . __('User not found.', 'zaobank') . '</div>';
	return;
}
?>

<div class="zaobank-container zaobank-appreciations-page" data-component="appreciations" data-user-id="<?php echo esc_attr($profile_user_id); ?>">

	<!-- Back Link -->
	<a href="<?php echo esc_url($urls['profile']); ?>?user_id=<?php echo esc_attr($profile_user_id); ?>" class="zaobank-back-link">
		<svg class="zaobank-back-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<line x1="19" y1="12" x2="5" y2="12"/>
			<polyline points="12 19 5 12 12 5"/>
		</svg>
		<?php printf(__('Back to %s profile', 'zaobank'), $is_own ? __('your', 'zaobank') : esc_html($user->display_name) . '\'s'); ?>
	</a>

	<header class="zaobank-page-header">
		<h1 class="zaobank-page-title">
			<?php echo $is_own ? __('Your Appreciations', 'zaobank') : sprintf(__('Appreciations for %s', 'zaobank'), esc_html($user->display_name)); ?>
		</h1>
	</header>

	<!-- Tab Navigation (only for own profile) -->
	<?php if ($is_own) : ?>
	<div class="zaobank-tabs">
		<button type="button" class="zaobank-tab active" data-tab="received">
			<?php _e('Received', 'zaobank'); ?>
			<span class="zaobank-tab-count" data-count="received">0</span>
		</button>
		<button type="button" class="zaobank-tab" data-tab="given">
			<?php _e('Given', 'zaobank'); ?>
			<span class="zaobank-tab-count" data-count="given">0</span>
		</button>
	</div>
	<?php endif; ?>

	<!-- Tag Summary -->
	<div class="zaobank-card zaobank-appreciation-tags-card">
		<div class="zaobank-card-body">
			<div class="zaobank-appreciation-tags-summary" data-loading="true">
				<div class="zaobank-loading-placeholder"><?php _e('Loading tags...', 'zaobank'); ?></div>
			</div>
		</div>
	</div>

	<!-- Appreciations List - Received -->
	<div class="zaobank-tab-panel active" data-panel="received">
		<div class="zaobank-appreciations-list" data-list="received" data-loading="true">
			<div class="zaobank-loading-state">
				<div class="zaobank-spinner"></div>
				<p><?php _e('Loading appreciations...', 'zaobank'); ?></p>
			</div>
		</div>

		<div class="zaobank-empty-state" data-empty="received" style="display: none;">
			<svg class="zaobank-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
				<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
			</svg>
			<h3><?php _e('No appreciations yet', 'zaobank'); ?></h3>
			<p><?php _e('Appreciations are given after completing exchanges.', 'zaobank'); ?></p>
		</div>
	</div>

	<!-- Appreciations List - Given (only for own profile) -->
	<?php if ($is_own) : ?>
	<div class="zaobank-tab-panel" data-panel="given">
		<div class="zaobank-appreciations-list" data-list="given" data-loading="true">
			<div class="zaobank-loading-state">
				<div class="zaobank-spinner"></div>
				<p><?php _e('Loading appreciations...', 'zaobank'); ?></p>
			</div>
		</div>

		<div class="zaobank-empty-state" data-empty="given" style="display: none;">
			<svg class="zaobank-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
				<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
			</svg>
			<h3><?php _e('You haven\'t given any appreciations', 'zaobank'); ?></h3>
			<p><?php _e('After completing exchanges, give appreciation to thank your exchange partners!', 'zaobank'); ?></p>
		</div>
	</div>
	<?php endif; ?>

</div>

<?php if (is_user_logged_in()) : ?>
	<?php include ZAOBANK_PLUGIN_DIR . 'public/templates/components/bottom-nav.php'; ?>
<?php endif; ?>

<script type="text/template" id="zaobank-appreciation-item-template">
<div class="zaobank-card zaobank-appreciation-card">
	<div class="zaobank-card-body">
		<div class="zaobank-appreciation-header">
			<a href="<?php echo esc_url($urls['profile']); ?>?user_id={{from_user_id}}" class="zaobank-appreciation-from">
				<img src="{{from_user_avatar}}" alt="" class="zaobank-avatar-small">
				<span class="zaobank-appreciation-from-name">{{from_user_name}}</span>
				{{#if from_user_pronouns}}
				<span class="zaobank-name-pronouns">({{from_user_pronouns}})</span>
				{{/if}}
			</a>
			<span class="zaobank-appreciation-date">{{date}}</span>
		</div>

		{{#if tag_slug}}
		<div class="zaobank-appreciation-tag">
			<span class="zaobank-tag zaobank-tag-{{tag_slug}}">{{tag_label}}</span>
		</div>
		{{/if}}

		{{#if message}}
		<p class="zaobank-appreciation-message">"{{message}}"</p>
		{{/if}}

		{{#if job_title}}
		<div class="zaobank-appreciation-context">
			<?php _e('For:', 'zaobank'); ?>
			<a href="<?php echo esc_url($urls['jobs']); ?>?job_id={{job_id}}">{{job_title}}</a>
		</div>
		{{/if}}
	</div>
</div>
</script>

<script type="text/template" id="zaobank-appreciation-tag-template">
<div class="zaobank-tag-badge">
	<span class="zaobank-tag zaobank-tag-{{slug}}">{{label}}</span>
	<span class="zaobank-tag-count">x{{count}}</span>
</div>
</script>
