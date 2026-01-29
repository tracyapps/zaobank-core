<?php
/**
 * ACF (Advanced Custom Fields) integration.
 */
class ZAOBank_ACF {

	/**
	 * Register ACF field groups.
	 */
	public function register_field_groups() {
		if (!function_exists('acf_add_local_field_group')) {
			return;
		}

		$this->register_job_fields();
		$this->register_user_profile_fields();
	}

	/**
	 * Register job-related ACF fields.
	 */
	private function register_job_fields() {
		acf_add_local_field_group(array(
			'key' => 'group_zaobank_job',
			'title' => 'Job Details',
			'fields' => array(
				array(
					'key' => 'field_job_hours',
					'label' => 'Hours Required',
					'name' => 'hours',
					'type' => 'number',
					'instructions' => 'Estimated hours for this job',
					'required' => 1,
					'min' => 0.25,
					'max' => 100,
					'step' => 0.25,
					'default_value' => 1,
				),
				array(
					'key' => 'field_job_provider',
					'label' => 'Provider',
					'name' => 'provider_user_id',
					'type' => 'user',
					'instructions' => 'User who claimed this job',
					'required' => 0,
					'role' => '',
					'allow_null' => 1,
					'multiple' => 0,
				),
				array(
					'key' => 'field_job_completed_at',
					'label' => 'Completed At',
					'name' => 'completed_at',
					'type' => 'date_time_picker',
					'instructions' => 'When this job was completed',
					'required' => 0,
					'display_format' => 'F j, Y g:i a',
					'return_format' => 'Y-m-d H:i:s',
				),
				array(
					'key' => 'field_job_visibility',
					'label' => 'Visibility',
					'name' => 'visibility',
					'type' => 'select',
					'instructions' => 'Control who can see this job',
					'required' => 0,
					'choices' => array(
						'public' => 'Public',
						'hidden' => 'Hidden (flagged)',
						'private' => 'Private',
					),
					'default_value' => 'public',
					'allow_null' => 0,
					'multiple' => 0,
				),
				array(
					'key' => 'field_job_location',
					'label' => 'Location',
					'name' => 'location',
					'type' => 'text',
					'instructions' => 'Where will this job take place?',
					'required' => 0,
				),
				array(
					'key' => 'field_job_skills',
					'label' => 'Skills Required',
					'name' => 'skills_required',
					'type' => 'text',
					'instructions' => 'Comma-separated list of required skills',
					'required' => 0,
				),
				array(
					'key' => 'field_job_preferred_date',
					'label' => 'Preferred Date',
					'name' => 'preferred_date',
					'type' => 'date_picker',
					'instructions' => 'When would you like this job done?',
					'required' => 0,
					'display_format' => 'F j, Y',
					'return_format' => 'Y-m-d',
				),
				array(
					'key' => 'field_job_flexible_timing',
					'label' => 'Flexible Timing',
					'name' => 'flexible_timing',
					'type' => 'true_false',
					'instructions' => 'Are you flexible on when this job is completed?',
					'default_value' => 1,
					'ui' => 1,
				),
			),
			'location' => array(
				array(
					array(
						'param' => 'post_type',
						'operator' => '==',
						'value' => 'timebank_job',
					),
				),
			),
			'menu_order' => 0,
			'position' => 'normal',
			'style' => 'default',
			'label_placement' => 'top',
			'instruction_placement' => 'label',
		));
	}

	/**
	 * Register user profile ACF fields.
	 */
	private function register_user_profile_fields() {
		acf_add_local_field_group(array(
			'key' => 'group_zaobank_user_profile',
			'title' => 'Time Bank Profile',
			'fields' => array(
				array(
					'key' => 'field_user_skills',
					'label' => 'Skills I Can Offer',
					'name' => 'user_skills',
					'type' => 'textarea',
					'instructions' => 'What skills or services can you provide to the community?',
					'required' => 0,
					'rows' => 4,
				),
				array(
					'key' => 'field_user_availability',
					'label' => 'Availability',
					'name' => 'user_availability',
					'type' => 'text',
					'instructions' => 'When are you generally available? (e.g., "Weekday evenings", "Flexible")',
					'required' => 0,
				),
				array(
					'key' => 'field_user_bio',
					'label' => 'Bio',
					'name' => 'user_bio',
					'type' => 'textarea',
					'instructions' => 'Tell the community a bit about yourself',
					'required' => 0,
					'rows' => 6,
				),
				array(
					'key' => 'field_user_primary_region',
					'label' => 'Primary Region',
					'name' => 'user_primary_region',
					'type' => 'taxonomy',
					'taxonomy' => 'zaobank_region',
					'field_type' => 'select',
					'allow_null' => 1,
					'instructions' => 'Which region are you primarily based in?',
				),
				array(
					'key' => 'field_user_profile_tags',
					'label' => 'Profile Tags',
					'name' => 'user_profile_tags',
					'type' => 'checkbox',
					'instructions' => 'Select tags that describe you (visible to community)',
					'choices' => array(
						'reliable' => 'Reliable',
						'flexible' => 'Flexible Schedule',
						'quick-responder' => 'Quick Responder',
						'detail-oriented' => 'Detail-Oriented',
						'team-player' => 'Team Player',
						'creative' => 'Creative',
						'technical' => 'Technical',
						'physical-tasks' => 'Enjoys Physical Tasks',
					),
					'layout' => 'vertical',
				),
				array(
					'key' => 'field_user_contact_preferences',
					'label' => 'Contact Preferences',
					'name' => 'user_contact_preferences',
					'type' => 'checkbox',
					'instructions' => 'How do you prefer to be contacted?',
					'choices' => array(
						'email' => 'Email',
						'phone' => 'Phone',
						'text' => 'Text Message',
						'platform-message' => 'Platform Messages',
					),
					'default_value' => array('email', 'platform-message'),
					'layout' => 'vertical',
				),
				array(
					'key' => 'field_user_phone',
					'label' => 'Phone Number',
					'name' => 'user_phone',
					'type' => 'text',
					'instructions' => 'Optional phone number for contact',
					'required' => 0,
				),
			),
			'location' => array(
				array(
					array(
						'param' => 'user_form',
						'operator' => '==',
						'value' => 'edit',
					),
				),
			),
			'menu_order' => 0,
			'position' => 'normal',
			'style' => 'default',
		));
	}
}