<?php
/**
 * Profile Edit Template
 *
 * Dedicated profile edit form.
 */

if (!defined('ABSPATH')) {
	exit;
}

$user_id = get_current_user_id();
$urls = ZAOBank_Shortcodes::get_page_urls();
?>

<div class="zaobank-container zaobank-profile-edit-page" data-component="profile-edit">

	<!-- Back Link -->
	<a href="<?php echo esc_url($urls['profile']); ?>" class="zaobank-back-link">
		<svg class="zaobank-back-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<line x1="19" y1="12" x2="5" y2="12"/>
			<polyline points="12 19 5 12 12 5"/>
		</svg>
		<?php _e('Back to Profile', 'zaobank'); ?>
	</a>

	<header class="zaobank-page-header">
		<h1 class="zaobank-page-title"><?php _e('Edit Profile', 'zaobank'); ?></h1>
	</header>

	<form id="zaobank-profile-form" class="zaobank-form" data-loading="true">
		<?php wp_nonce_field('zaobank_profile_form', 'zaobank_nonce'); ?>

		<div class="zaobank-card">
			<div class="zaobank-card-body">

				<!-- Avatar Preview -->
				<div class="zaobank-form-group zaobank-avatar-section">
					<div class="zaobank-avatar-preview">
						<img src="<?php echo esc_url(get_avatar_url($user_id, array('size' => 96))); ?>" alt="" class="zaobank-avatar-large" id="profile-avatar">
					</div>
					<p class="zaobank-form-hint">
						<?php _e('Avatar is managed through Gravatar', 'zaobank'); ?>
						<a href="https://gravatar.com" target="_blank" rel="noopener"><?php _e('Change on Gravatar', 'zaobank'); ?></a>
					</p>
				</div>

				<!-- Bio -->
				<div class="zaobank-form-group">
					<label for="profile-bio" class="zaobank-label">
						<?php _e('About Me', 'zaobank'); ?>
					</label>
					<textarea id="profile-bio"
					          name="user_bio"
					          class="zaobank-textarea"
					          rows="4"
					          placeholder="<?php esc_attr_e('Tell the community a bit about yourself...', 'zaobank'); ?>"></textarea>
				</div>

				<!-- Skills -->
				<div class="zaobank-form-group">
					<label for="profile-skills" class="zaobank-label">
						<?php _e('Skills I Can Offer', 'zaobank'); ?>
					</label>
					<textarea id="profile-skills"
					          name="user_skills"
					          class="zaobank-textarea"
					          rows="3"
					          placeholder="<?php esc_attr_e('What skills or services can you offer to others?', 'zaobank'); ?>"></textarea>
					<span class="zaobank-form-hint"><?php _e('e.g., gardening, tutoring, cooking, computer help', 'zaobank'); ?></span>
				</div>

				<!-- Availability -->
				<div class="zaobank-form-group">
					<label for="profile-availability" class="zaobank-label">
						<?php _e('Availability', 'zaobank'); ?>
					</label>
					<input type="text"
					       id="profile-availability"
					       name="user_availability"
					       class="zaobank-input"
					       placeholder="<?php esc_attr_e('e.g., Weekday evenings, Saturday mornings', 'zaobank'); ?>">
				</div>

				<!-- Primary Region -->
				<div class="zaobank-form-group">
					<label for="profile-region" class="zaobank-label">
						<?php _e('Primary Region', 'zaobank'); ?>
					</label>
					<select id="profile-region" name="user_primary_region" class="zaobank-select">
						<option value=""><?php _e('Select your region', 'zaobank'); ?></option>
					</select>
				</div>

				<!-- Profile Tags -->
				<div class="zaobank-form-group">
					<label class="zaobank-label"><?php _e('Profile Tags', 'zaobank'); ?></label>
					<div class="zaobank-checkbox-group" data-field="user_profile_tags">
						<?php
						$tags_field = acf_get_field('field_user_profile_tags');
						$profile_tags = $tags_field ? $tags_field['choices'] : array();
						foreach ($profile_tags as $value => $label) :
						?>
							<label class="zaobank-checkbox-label">
								<input type="checkbox" name="user_profile_tags[]" value="<?php echo esc_attr($value); ?>">
								<span><?php echo esc_html($label); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Contact Preferences -->
				<div class="zaobank-form-group">
					<label class="zaobank-label"><?php _e('Contact Preferences', 'zaobank'); ?></label>
					<div class="zaobank-checkbox-group" data-field="user_contact_preferences">
						<?php
						$prefs_field = acf_get_field('field_user_contact_preferences');
						$contact_prefs = $prefs_field ? $prefs_field['choices'] : array();
						foreach ($contact_prefs as $value => $label) :
						?>
							<label class="zaobank-checkbox-label">
								<input type="checkbox" name="user_contact_preferences[]" value="<?php echo esc_attr($value); ?>">
								<span><?php echo esc_html($label); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Phone Number -->
				<div class="zaobank-form-group">
					<label for="profile-phone" class="zaobank-label">
						<?php _e('Phone Number', 'zaobank'); ?>
					</label>
					<input type="tel"
					       id="profile-phone"
					       name="user_phone"
					       class="zaobank-input"
					       placeholder="<?php esc_attr_e('Your phone number (optional)', 'zaobank'); ?>">
					<span class="zaobank-form-hint"><?php _e('Only visible to members you exchange with', 'zaobank'); ?></span>
				</div>

				<!-- Discord User ID -->
				<div class="zaobank-form-group">
					<label for="profile-discord" class="zaobank-label">
						<?php _e('Discord User ID', 'zaobank'); ?>
					</label>
					<input type="text"
					       id="profile-discord"
					       name="user_discord_id"
					       class="zaobank-input"
					       placeholder="<?php esc_attr_e('e.g., 123456789012345678', 'zaobank'); ?>">
					<span class="zaobank-form-hint"><?php _e('Enable Developer Mode in Discord, then right-click your profile to copy your User ID', 'zaobank'); ?></span>
				</div>

			</div>

			<div class="zaobank-card-footer">
				<button type="submit" class="zaobank-btn zaobank-btn-primary zaobank-btn-lg zaobank-btn-block">
					<?php _e('Save Changes', 'zaobank'); ?>
				</button>
			</div>
		</div>
	</form>

</div>

<?php include ZAOBANK_PLUGIN_DIR . 'public/templates/components/bottom-nav.php'; ?>
