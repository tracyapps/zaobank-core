<?php
/**
 * Profile Template
 *
 * User profile view (own = editable, others = view only).
 */

if (!defined('ABSPATH')) {
	exit;
}

$profile_user_id = isset($user_id) ? (int) $user_id : 0;
$is_own = isset($is_own_profile) ? $is_own_profile : false;
$urls = ZAOBank_Shortcodes::get_page_urls();
?>

<div class="zaobank-container zaobank-profile-page" data-component="profile" data-user-id="<?php echo esc_attr($profile_user_id); ?>" data-own="<?php echo $is_own ? 'true' : 'false'; ?>">

	<!-- Profile Content - populated via JS -->
	<div class="zaobank-profile-content" data-loading="true">
		<div class="zaobank-loading-state">
			<div class="zaobank-spinner"></div>
			<p><?php _e('Loading profile...', 'zaobank'); ?></p>
		</div>
	</div>

</div>

<?php if (is_user_logged_in()) : ?>
	<?php include ZAOBANK_PLUGIN_DIR . 'public/templates/components/bottom-nav.php'; ?>
<?php endif; ?>

<script type="text/template" id="zaobank-profile-template">
<div class="zaobank-profile-header-section">
	<div class="zaobank-profile-avatar-wrapper">
		<img src="{{avatar_url}}" alt="{{display_name}}" class="zaobank-profile-avatar">
	</div>
	<h1 class="zaobank-profile-name">{{display_name}}</h1>
	{{#if display_name}}
	<p class="zaobank-profile-display-name"><?php _e('Display name', 'zaobank'); ?>: {{display_name}}</p>
	{{/if}}
	<p class="zaobank-profile-since"><?php _e('Member since', 'zaobank'); ?> {{member_since}}</p>

	{{#if primary_region}}
	<div class="zaobank-profile-region">
		<svg class="zaobank-meta-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
			<circle cx="12" cy="10" r="3"/>
		</svg>
		{{primary_region.name}}
	</div>
	{{/if}}
</div>

{{#if bio}}
<div class="zaobank-card zaobank-profile-section">
	<div class="zaobank-card-header">
		<h2 class="zaobank-card-title"><?php _e('About', 'zaobank'); ?></h2>
	</div>
	<div class="zaobank-card-body">
		<p class="zaobank-profile-bio">{{bio}}</p>
	</div>
</div>
{{/if}}

{{#if skills}}
<div class="zaobank-card zaobank-profile-section">
	<div class="zaobank-card-header">
		<h2 class="zaobank-card-title"><?php _e('Skills I Can Offer', 'zaobank'); ?></h2>
	</div>
	<div class="zaobank-card-body">
		<p class="zaobank-profile-skills">{{skills}}</p>
	</div>
</div>
{{/if}}

{{#if availability}}
<div class="zaobank-card zaobank-profile-section">
	<div class="zaobank-card-header">
		<h2 class="zaobank-card-title"><?php _e('Availability', 'zaobank'); ?></h2>
	</div>
	<div class="zaobank-card-body">
		<p class="zaobank-profile-availability">{{availability}}</p>
	</div>
</div>
{{/if}}

{{#if profile_tags.length}}
<div class="zaobank-card zaobank-profile-section">
	<div class="zaobank-card-header">
		<h2 class="zaobank-card-title"><?php _e('Profile Tags', 'zaobank'); ?></h2>
	</div>
	<div class="zaobank-card-body">
		<div class="zaobank-tags">
			{{#each profile_tags}}
			<span class="zaobank-tag">{{this}}</span>
			{{/each}}
		</div>
	</div>
</div>
{{/if}}

{{#if show_connect}}
<div class="zaobank-card zaobank-profile-section">
	<div class="zaobank-card-header">
		<h2 class="zaobank-card-title"><?php _e('Connect', 'zaobank'); ?></h2>
	</div>
	<div class="zaobank-card-body">
		<div class="zaobank-tags">
			{{#if discord_url}}<a href="{{discord_url}}" target="_blank" rel="noopener" class="zaobank-tag zaobank-tag-link">Discord</a>{{/if}}
			{{#if has_signal}}<span class="zaobank-tag">Signal</span>{{/if}}
		</div>
	</div>
</div>
{{/if}}

<!-- Appreciations Section -->
<div class="zaobank-card zaobank-profile-section">
	<div class="zaobank-card-header">
		<h2 class="zaobank-card-title"><?php _e('Appreciations', 'zaobank'); ?></h2>
	</div>
	<div class="zaobank-card-body">
		<div class="zaobank-appreciations-preview" data-component="appreciations-preview">
			<div class="zaobank-loading-placeholder"><?php _e('Loading appreciations...', 'zaobank'); ?></div>
		</div>
		<a href="<?php echo esc_url($urls['appreciations']); ?>?user_id={{id}}" class="zaobank-link-more">
			<?php _e('View all appreciations', 'zaobank'); ?>
			<svg class="zaobank-link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
				<line x1="5" y1="12" x2="19" y2="12"/>
				<polyline points="12 5 19 12 12 19"/>
			</svg>
		</a>
	</div>
</div>

<!-- Action Buttons -->
<div class="zaobank-profile-actions">
	{{#if is_own}}
	<a href="<?php echo esc_url($urls['profile_edit']); ?>" class="zaobank-btn zaobank-btn-primary zaobank-btn-block">
		<svg class="zaobank-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
			<path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
		</svg>
		<?php _e('Edit Profile', 'zaobank'); ?>
	</a>
	{{else}}
	<a href="<?php echo esc_url($urls['messages']); ?>?user_id={{id}}" class="zaobank-btn zaobank-btn-primary zaobank-btn-block">
		<svg class="zaobank-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
		</svg>
		<?php _e('Send Message', 'zaobank'); ?>
	</a>
	<button type="button" class="zaobank-btn zaobank-btn-ghost zaobank-btn-sm zaobank-flag-content" data-item-type="user" data-item-id="{{id}}">
		<svg class="zaobank-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
			<line x1="4" y1="22" x2="4" y2="15"/>
		</svg>
		<?php _e('Report User', 'zaobank'); ?>
	</button>
	{{/if}}
</div>
</script>
