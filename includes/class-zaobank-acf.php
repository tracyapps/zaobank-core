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
					'key' => 'field_job_virtual_ok',
					'label' => 'Virtual Option',
					'name' => 'virtual_ok',
					'type' => 'true_false',
					'instructions' => 'Can this job be done virtually?',
					'default_value' => 0,
					'ui' => 1,
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
		$profile_tag_choices = $this->build_tag_choices(
			get_option('zaobank_profile_tags', array()),
			$this->get_default_profile_tag_labels()
		);
		$skill_tag_choices = $this->build_tag_choices(
			get_option('zaobank_skill_tags', array())
		);

		acf_add_local_field_group(array(
			'key' => 'group_zaobank_user_profile',
			'title' => 'Time Bank Profile',
			'fields' => array(
				array(
					'key' => 'field_user_profile_image',
					'label' => 'Profile Image',
					'name' => 'user_profile_image',
					'type' => 'image',
					'instructions' => 'Upload a profile photo (replaces Gravatar)',
					'required' => 0,
					'return_format' => 'id',
					'preview_size' => 'thumbnail',
					'library' => 'all',
					'min_width' => 96,
					'min_height' => 96,
					'mime_types' => 'jpg,jpeg,png,gif,webp',
				),
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
					'key' => 'field_user_skill_tags',
					'label' => 'Skill Tags',
					'name' => 'user_skill_tags',
					'type' => 'checkbox',
					'instructions' => 'Select tags that describe the skills you offer (used in community filters).',
					'choices' => $skill_tag_choices,
					'layout' => 'vertical',
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
					'label' => 'Personality Tags',
					'name' => 'user_profile_tags',
					'type' => 'checkbox',
					'instructions' => 'Select tags that describe your working style (visible on your profile).',
					'choices' => $profile_tag_choices,
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
						'signal' => 'Signal',
						'discord' => 'Discord',
						'platform-message' => 'ZAO bank Messages',
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
				array(
					'key' => 'field_user_discord_id',
					'label' => 'Discord User ID',
					'name' => 'user_discord_id',
					'type' => 'text',
					'instructions' => 'Your Discord User ID (enable Developer Mode in Discord settings, then right-click your profile and select Copy User ID)',
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

	/**
	 * Build a slug => label map for checkbox choices.
	 */
	private function build_tag_choices($raw_tags, $fallback = array()) {
		$tags = is_array($raw_tags) ? $raw_tags : array();
		$tags = array_filter(array_map('trim', $tags));

		if (empty($tags) && !empty($fallback)) {
			$is_assoc = array_keys($fallback) !== range(0, count($fallback) - 1);
			if ($is_assoc) {
				return $fallback;
			}
			$tags = $fallback;
		}

		$choices = array();
		foreach ($tags as $tag) {
			if (!is_string($tag)) {
				continue;
			}
			$label = trim($tag);
			if ($label === '') {
				continue;
			}
			$slug = sanitize_key($label);
			if ($slug === '') {
				continue;
			}
			$choices[$slug] = $label;
		}

		return $choices;
	}

	/**
	 * Default profile tag labels (used when settings are empty).
	 */
	private function get_default_profile_tag_labels() {
		return array(
			'Reliable',
			'Flexible Schedule',
			'Quick Responder',
			'Busy, But Reliable',
			'Detail-Oriented',
			'Big-Picture Thinker',
			'Team Player/Collaborator',
			'Creative',
			'Technical',
			'Word Person: Writer and/or Editor',
			'"Type A" Person (order/organization)',
			'Enjoys Physical Tasks',
			'Needs Clear Instructions',
			'Good With Ambiguity',
			'Neurodivergent',
			'Talkative',
			'Needs Music/Noise',
			'Prefers Quiet',
			'Visual Learner',
			'Prefers Written Instructions',
		);
	}
}
