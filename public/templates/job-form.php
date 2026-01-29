<?php
/**
 * Job Form Template
 *
 * Create or edit a job.
 */

if (!defined('ABSPATH')) {
	exit;
}

$job_id = isset($id) ? (int) $id : 0;
$is_edit = $job_id > 0;
$urls = ZAOBank_Shortcodes::get_page_urls();
?>

<div class="zaobank-container zaobank-job-form-page" data-component="job-form" data-job-id="<?php echo esc_attr($job_id); ?>">

	<!-- Back Link -->
	<a href="<?php echo esc_url($is_edit ? $urls['my_jobs'] : $urls['jobs']); ?>" class="zaobank-back-link">
		<svg class="zaobank-back-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<line x1="19" y1="12" x2="5" y2="12"/>
			<polyline points="12 19 5 12 12 5"/>
		</svg>
		<?php _e('Back', 'zaobank'); ?>
	</a>

	<header class="zaobank-page-header">
		<h1 class="zaobank-page-title">
			<?php echo $is_edit ? __('Edit Job', 'zaobank') : __('Request Help', 'zaobank'); ?>
		</h1>
		<?php if (!$is_edit) : ?>
			<p class="zaobank-page-subtitle"><?php _e('Describe what you need help with and how long it might take.', 'zaobank'); ?></p>
		<?php endif; ?>
	</header>

	<form id="zaobank-job-form" class="zaobank-form" data-loading="<?php echo $is_edit ? 'true' : 'false'; ?>">
		<?php wp_nonce_field('zaobank_job_form', 'zaobank_nonce'); ?>
		<input type="hidden" name="job_id" value="<?php echo esc_attr($job_id); ?>">

		<div class="zaobank-card">
			<div class="zaobank-card-body">

				<!-- Title -->
				<div class="zaobank-form-group">
					<label for="job-title" class="zaobank-label zaobank-required">
						<?php _e('What do you need help with?', 'zaobank'); ?>
					</label>
					<input type="text"
					       id="job-title"
					       name="title"
					       class="zaobank-input"
					       placeholder="<?php esc_attr_e('e.g., Help moving furniture', 'zaobank'); ?>"
					       required
					       maxlength="100">
					<span class="zaobank-form-hint"><?php _e('A short, clear title for your request', 'zaobank'); ?></span>
				</div>

				<!-- Description -->
				<div class="zaobank-form-group">
					<label for="job-description" class="zaobank-label zaobank-required">
						<?php _e('Description', 'zaobank'); ?>
					</label>
					<textarea id="job-description"
					          name="description"
					          class="zaobank-textarea"
					          rows="5"
					          placeholder="<?php esc_attr_e('Describe what needs to be done...', 'zaobank'); ?>"
					          required></textarea>
					<span class="zaobank-form-hint"><?php _e('Include any important details that will help someone decide if they can help', 'zaobank'); ?></span>
				</div>

				<!-- Hours -->
				<div class="zaobank-form-group">
					<label for="job-hours" class="zaobank-label zaobank-required">
						<?php _e('Estimated Time (hours)', 'zaobank'); ?>
					</label>
					<input type="number"
					       id="job-hours"
					       name="hours"
					       class="zaobank-input zaobank-input-sm"
					       min="0.25"
					       max="40"
					       step="0.25"
					       value="1"
					       required>
					<span class="zaobank-form-hint"><?php _e('How long do you think this will take?', 'zaobank'); ?></span>
				</div>

				<!-- Location -->
				<div class="zaobank-form-group">
					<label for="job-location" class="zaobank-label">
						<?php _e('Location', 'zaobank'); ?>
					</label>
					<input type="text"
					       id="job-location"
					       name="location"
					       class="zaobank-input"
					       placeholder="<?php esc_attr_e('e.g., Downtown, My home, Online', 'zaobank'); ?>">
					<span class="zaobank-form-hint"><?php _e('Where will this job take place?', 'zaobank'); ?></span>
				</div>

				<!-- Preferred Date -->
				<div class="zaobank-form-group">
					<label for="job-date" class="zaobank-label">
						<?php _e('Preferred Date', 'zaobank'); ?>
					</label>
					<input type="date"
					       id="job-date"
					       name="preferred_date"
					       class="zaobank-input zaobank-input-sm"
					       min="<?php echo esc_attr(date('Y-m-d')); ?>">
					<span class="zaobank-form-hint"><?php _e('Leave blank if flexible', 'zaobank'); ?></span>
				</div>

				<!-- Flexible Timing -->
				<div class="zaobank-form-group">
					<label class="zaobank-checkbox-label">
						<input type="checkbox" name="flexible_timing" value="1" checked>
						<span><?php _e('I have flexible timing', 'zaobank'); ?></span>
					</label>
				</div>

				<!-- Skills Required -->
				<div class="zaobank-form-group">
					<label for="job-skills" class="zaobank-label">
						<?php _e('Skills Needed', 'zaobank'); ?>
					</label>
					<input type="text"
					       id="job-skills"
					       name="skills_required"
					       class="zaobank-input"
					       placeholder="<?php esc_attr_e('e.g., Driving, Lifting, Computer skills', 'zaobank'); ?>">
					<span class="zaobank-form-hint"><?php _e('Separate with commas', 'zaobank'); ?></span>
				</div>

				<!-- Region -->
				<div class="zaobank-form-group">
					<label for="job-region" class="zaobank-label">
						<?php _e('Region', 'zaobank'); ?>
					</label>
					<select id="job-region" name="region" class="zaobank-select">
						<option value=""><?php _e('Select a region', 'zaobank'); ?></option>
					</select>
				</div>

			</div>

			<div class="zaobank-card-footer">
				<button type="submit" class="zaobank-btn zaobank-btn-primary zaobank-btn-lg zaobank-btn-block">
					<?php echo $is_edit ? __('Update Job', 'zaobank') : __('Post Job', 'zaobank'); ?>
				</button>
			</div>
		</div>
	</form>

</div>

<?php include ZAOBANK_PLUGIN_DIR . 'public/templates/components/bottom-nav.php'; ?>
