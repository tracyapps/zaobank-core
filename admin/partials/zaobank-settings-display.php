<?php
/**
 * Settings Display
 */

if (!defined('ABSPATH')) {
	exit;
}

// Save settings if form submitted
if (isset($_POST['zaobank_save_settings']) && check_admin_referer('zaobank_settings')) {
	update_option('zaobank_enable_regions', isset($_POST['enable_regions']));
	update_option('zaobank_auto_hide_flagged', isset($_POST['auto_hide_flagged']));
	update_option('zaobank_flag_threshold', (int) $_POST['flag_threshold']);

	if (isset($_POST['appreciation_tags'])) {
		$tags = array_filter(array_map('trim', explode("\n", $_POST['appreciation_tags'])));
		update_option('zaobank_appreciation_tags', $tags);
	}

	if (isset($_POST['private_note_tags'])) {
		$tags = array_filter(array_map('trim', explode("\n", $_POST['private_note_tags'])));
		update_option('zaobank_private_note_tags', $tags);
	}

	if (isset($_POST['flag_reasons'])) {
		$reasons = array_filter(array_map('trim', explode("\n", $_POST['flag_reasons'])));
		update_option('zaobank_flag_reasons', $reasons);
	}

	if (isset($_POST['message_search_roles']) && is_array($_POST['message_search_roles'])) {
		$selected_roles = array_map('sanitize_key', $_POST['message_search_roles']);
		$valid_roles = array();
		foreach ($selected_roles as $role) {
			if (wp_roles()->is_role($role)) {
				$valid_roles[] = $role;
			}
		}
		update_option('zaobank_message_search_roles', $valid_roles);
	} else {
		update_option('zaobank_message_search_roles', array());
	}

	echo '<div class="notice notice-success"><p>' . __('Settings saved.', 'zaobank') . '</p></div>';
}

// Get current settings
$enable_regions = get_option('zaobank_enable_regions', true);
$auto_hide_flagged = get_option('zaobank_auto_hide_flagged', true);
$flag_threshold = get_option('zaobank_flag_threshold', 1);
$appreciation_tags = get_option('zaobank_appreciation_tags', array());
$private_note_tags = get_option('zaobank_private_note_tags', array());
$flag_reasons = get_option('zaobank_flag_reasons', array());
$message_search_roles = get_option('zaobank_message_search_roles', array('member'));
if (!is_array($message_search_roles)) {
	$message_search_roles = array();
}
$available_roles = get_editable_roles();

?>

<div class="wrap">
	<h1><?php echo esc_html(get_admin_page_title()); ?></h1>

	<form method="post" action="">
		<?php wp_nonce_field('zaobank_settings'); ?>

		<table class="form-table">
			<tr>
				<th scope="row"><?php _e('Enable Regions', 'zaobank'); ?></th>
				<td>
					<label>
						<input type="checkbox" name="enable_regions" value="1" <?php checked($enable_regions, true); ?>>
						<?php _e('Enable regional filtering and organization', 'zaobank'); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php _e('Auto-hide Flagged Content', 'zaobank'); ?></th>
				<td>
					<label>
						<input type="checkbox" name="auto_hide_flagged" value="1" <?php checked($auto_hide_flagged, true); ?>>
						<?php _e('Automatically hide content when flagged (recommended)', 'zaobank'); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php _e('Flag Threshold', 'zaobank'); ?></th>
				<td>
					<input type="number" name="flag_threshold" value="<?php echo esc_attr($flag_threshold); ?>" min="1" max="10">
					<p class="description"><?php _e('Number of flags before content is auto-hidden', 'zaobank'); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php _e('Message Search Roles', 'zaobank'); ?></th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><?php _e('Message Search Roles', 'zaobank'); ?></legend>
						<?php foreach ($available_roles as $role_key => $role_info) : ?>
							<label style="display:block; margin-bottom:6px;">
								<input type="checkbox"
								       name="message_search_roles[]"
								       value="<?php echo esc_attr($role_key); ?>"
									<?php checked(in_array($role_key, $message_search_roles, true)); ?>>
								<?php echo esc_html($role_info['name']); ?>
							</label>
						<?php endforeach; ?>
					</fieldset>
					<p class="description"><?php _e('Only users with these roles appear in “Start a new message” search. Leave unchecked to disable search results.', 'zaobank'); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php _e('Appreciation Tags', 'zaobank'); ?></th>
				<td>
					<textarea name="appreciation_tags" rows="10" class="large-text code"><?php echo esc_textarea(implode("\n", $appreciation_tags)); ?></textarea>
					<p class="description"><?php _e('One tag per line. These are positive tags users can use for appreciations.', 'zaobank'); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php _e('Private Note Tags', 'zaobank'); ?></th>
				<td>
					<textarea name="private_note_tags" rows="10" class="large-text code"><?php echo esc_textarea(implode("\n", $private_note_tags)); ?></textarea>
					<p class="description"><?php _e('One tag per line. These are memory aid tags for private notes (never visible to others).', 'zaobank'); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php _e('Flag Reasons', 'zaobank'); ?></th>
				<td>
					<textarea name="flag_reasons" rows="8" class="large-text code"><?php echo esc_textarea(implode("\n", $flag_reasons)); ?></textarea>
					<p class="description"><?php _e('One reason per line. These are available reasons for flagging content.', 'zaobank'); ?></p>
				</td>
			</tr>
		</table>

		<p class="submit">
			<input type="submit" name="zaobank_save_settings" class="button button-primary" value="<?php _e('Save Settings', 'zaobank'); ?>">
		</p>
	</form>
</div>
