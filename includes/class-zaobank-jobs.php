<?php
/**
 * Job management business logic.
 */
class ZAOBank_Jobs {

	/**
	 * Create a new job.
	 */
	public static function create_job($data, $user_id = null) {
		if (!$user_id) {
			$user_id = get_current_user_id();
		}

		// Validate required fields
		if (empty($data['title'])) {
			return new WP_Error('missing_title', __('Job title is required', 'zaobank'));
		}

		if (empty($data['hours'])) {
			return new WP_Error('missing_hours', __('Hours are required', 'zaobank'));
		}

		// Validate hours
		$hours_validation = ZAOBank_Security::validate_hours($data['hours']);
		if (is_wp_error($hours_validation)) {
			return $hours_validation;
		}

		// Prepare post data
		$post_data = array(
			'post_title'   => $data['title'],
			'post_content' => isset($data['description']) ? $data['description'] : '',
			'post_status'  => 'publish',
			'post_author'  => $user_id,
			'post_type'    => 'timebank_job',
		);

		// Insert the post
		$job_id = wp_insert_post($post_data);

		if (is_wp_error($job_id)) {
			return $job_id;
		}

		// Add meta fields
		update_post_meta($job_id, 'hours', floatval($data['hours']));
		update_post_meta($job_id, 'visibility', 'public');

		if (isset($data['location'])) {
			update_post_meta($job_id, 'location', sanitize_text_field($data['location']));
		}

		if (isset($data['skills_required'])) {
			update_post_meta($job_id, 'skills_required', sanitize_text_field($data['skills_required']));
		}

		if (isset($data['preferred_date'])) {
			update_post_meta($job_id, 'preferred_date', sanitize_text_field($data['preferred_date']));
		}

		if (isset($data['flexible_timing'])) {
			update_post_meta($job_id, 'flexible_timing', (bool) $data['flexible_timing']);
		}

		if (isset($data['virtual_ok'])) {
			update_post_meta($job_id, 'virtual_ok', (int) (bool) $data['virtual_ok']);
		}

		// Assign regions
		if (!empty($data['regions']) && is_array($data['regions'])) {
			wp_set_object_terms($job_id, $data['regions'], 'zaobank_region');

			// Update user region affinity
			self::update_user_region_affinity($user_id, $data['regions']);
		}

		// Assign job types
		if (!empty($data['job_types']) && is_array($data['job_types'])) {
			$type_ids = array_map('intval', $data['job_types']);
			wp_set_object_terms($job_id, $type_ids, 'zaobank_job_type');
		}

		return $job_id;
	}

	/**
	 * Claim a job.
	 */
	public static function claim_job($job_id, $user_id = null) {
		if (!$user_id) {
			$user_id = get_current_user_id();
		}

		$job = get_post($job_id);
		if (!$job || $job->post_type !== 'timebank_job') {
			return new WP_Error('invalid_job', __('Invalid job', 'zaobank'));
		}

		// Check if job can be claimed
		if (!ZAOBank_Security::can_claim_job($job_id, $user_id)) {
			return new WP_Error(
				'cannot_claim_job',
				__('You cannot claim this job', 'zaobank')
			);
		}

		// Update job meta
		update_post_meta($job_id, 'provider_user_id', $user_id);

		// Update post status to indicate it's been claimed
		wp_update_post(array(
			'ID' => $job_id,
			'post_status' => 'publish'
		));

		// Notify requester
		ZAOBank_Messages::create_message(array(
			'from_user_id' => $user_id,
			'to_user_id' => (int) $job->post_author,
			'message' => sprintf(
				__("%s claimed your job '%s'.", 'zaobank'),
				get_the_author_meta('display_name', $user_id),
				$job->post_title
			),
			'message_type' => 'job_update',
			'job_id' => $job_id
		));

		// Log the claim
		ZAOBank_Security::log_security_event('job_claimed', array(
			'job_id' => $job_id,
			'provider_id' => $user_id
		));

		return true;
	}

	/**
	 * Release a claimed job (provider gives it up).
	 */
	public static function release_job($job_id, $reason = '', $user_id = null) {
		if (!$user_id) {
			$user_id = get_current_user_id();
		}

		$job = get_post($job_id);
		if (!$job || $job->post_type !== 'timebank_job') {
			return new WP_Error('invalid_job', __('Invalid job', 'zaobank'));
		}

		$provider_id = get_post_meta($job_id, 'provider_user_id', true);
		if ((int) $provider_id !== (int) $user_id) {
			return new WP_Error('not_provider', __('You are not the provider for this job', 'zaobank'));
		}

		$completed_at = get_post_meta($job_id, 'completed_at', true);
		if ($completed_at) {
			return new WP_Error('already_completed', __('Cannot release a completed job', 'zaobank'));
		}

		// Remove provider assignment
		delete_post_meta($job_id, 'provider_user_id');

		// Send a job update message to the requester
		$reason_text = $reason ? sprintf(__("Reason: %s", 'zaobank'), $reason) : '';
		ZAOBank_Messages::create_message(array(
			'from_user_id' => $user_id,
			'to_user_id' => (int) $job->post_author,
			'message' => sprintf(
				__("I've released your job '%s'. %s", 'zaobank'),
				$job->post_title,
				$reason_text
			),
			'message_type' => 'job_update',
			'job_id' => $job_id
		));

		ZAOBank_Security::log_security_event('job_released', array(
			'job_id' => $job_id,
			'provider_id' => $user_id,
			'reason' => $reason
		));

		return true;
	}

	/**
	 * Complete a job and record the exchange.
	 *
	 * @param int        $job_id         Job post ID.
	 * @param int|null   $user_id        User completing the job (defaults to current user).
	 * @param float|null $hours_override Optional hours override.
	 * @return int|WP_Error Exchange ID on success.
	 */
	public static function complete_job($job_id, $user_id = null, $hours_override = null) {
		if (!$user_id) {
			$user_id = get_current_user_id();
		}

		$job = get_post($job_id);

		if (!$job || $job->post_type !== 'timebank_job') {
			return new WP_Error('invalid_job', __('Invalid job', 'zaobank'));
		}

		// Check permissions
		if (!ZAOBank_Security::can_complete_job($job_id, $user_id)) {
			return new WP_Error(
				'cannot_complete_job',
				__('You cannot complete this job', 'zaobank')
			);
		}

		$completed_at = get_post_meta($job_id, 'completed_at', true);
		if ($completed_at) {
			return new WP_Error(
				'already_completed',
				__('This job has already been completed.', 'zaobank')
			);
		}

		// Get provider
		$provider_id = get_post_meta($job_id, 'provider_user_id', true);

		if (!$provider_id) {
			return new WP_Error(
				'no_provider',
				__('This job has not been claimed', 'zaobank')
			);
		}

		// Get hours (allow override)
		$hours = get_post_meta($job_id, 'hours', true);

		if ($hours_override !== null && $hours_override > 0) {
			$hours_validation = ZAOBank_Security::validate_hours($hours_override);
			if (is_wp_error($hours_validation)) {
				return $hours_validation;
			}
			$hours = floatval($hours_override);
			update_post_meta($job_id, 'hours', $hours);
		}

		if (!$hours) {
			return new WP_Error('invalid_hours', __('Invalid hours for this job', 'zaobank'));
		}

		// Get region (first region assigned to the job)
		$regions = wp_get_object_terms($job_id, 'zaobank_region');
		$region_id = !empty($regions) ? $regions[0]->term_id : null;

		// Create the exchange
		$exchange_id = ZAOBank_Exchanges::create_exchange(array(
			'job_id' => $job_id,
			'provider_user_id' => $provider_id,
			'requester_user_id' => $job->post_author,
			'hours' => floatval($hours),
			'region_term_id' => $region_id
		));

		if (is_wp_error($exchange_id)) {
			return $exchange_id;
		}

		// Update job meta
		update_post_meta($job_id, 'completed_at', wp_date('Y-m-d H:i:s'));

		// Update post status
		wp_update_post(array(
			'ID' => $job_id,
			'post_status' => 'publish'
		));

		// Send completion notification to provider
		ZAOBank_Messages::create_message(array(
			'from_user_id' => $user_id,
			'to_user_id' => (int) $provider_id,
			'message' => sprintf(
				__("Your job '%s' has been marked complete. %s hours have been credited to your account.", 'zaobank'),
				$job->post_title,
				$hours
			),
			'message_type' => 'job_update',
			'job_id' => $job_id
		));

		// Log the completion
		ZAOBank_Security::log_security_event('job_completed', array(
			'job_id' => $job_id,
			'exchange_id' => $exchange_id
		));

		return $exchange_id;
	}

	/**
	 * Get available jobs (filtered by region if provided).
	 */
	public static function get_available_jobs($args = array()) {
		$defaults = array(
			'posts_per_page' => 20,
			'post_type' => 'timebank_job',
			'post_status' => 'publish',
			'meta_query' => array(
				array(
					'key' => 'provider_user_id',
					'compare' => 'NOT EXISTS'
				),
				array(
					'key' => 'visibility',
					'value' => 'public',
					'compare' => '='
				)
			)
		);

		$args = wp_parse_args($args, $defaults);

		// Add region filter if specified
		if (!empty($args['region'])) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'zaobank_region',
					'field' => 'term_id',
					'terms' => $args['region']
				)
			);
		}

		$query = new WP_Query($args);
		$jobs = array();

		if ($query->have_posts()) {
			while ($query->have_posts()) {
				$query->the_post();
				$jobs[] = self::format_job_data(get_the_ID());
			}
			wp_reset_postdata();
		}

		return $jobs;
	}

	/**
	 * Format job data for API response.
	 */
	public static function format_job_data($job_id) {
		$job = get_post($job_id);

		if (!$job) {
			return null;
		}

		$requester = get_userdata($job->post_author);
		$provider_id = (int) get_post_meta($job_id, 'provider_user_id', true);

		$regions = wp_get_object_terms($job_id, 'zaobank_region');
		$region_data = array();

		foreach ($regions as $region) {
			$region_data[] = array(
				'id' => $region->term_id,
				'name' => $region->name,
				'slug' => $region->slug
			);
		}

		$job_types = wp_get_object_terms($job_id, 'zaobank_job_type');
		$job_type_data = array();

		if (!is_wp_error($job_types)) {
			foreach ($job_types as $type) {
				$job_type_data[] = array(
					'id' => $type->term_id,
					'name' => $type->name,
					'slug' => $type->slug
				);
			}
		}

		$exchange = null;
		$completed_at = get_post_meta($job_id, 'completed_at', true);
		if (!empty($completed_at)) {
			$exchange = ZAOBank_Exchanges::get_job_exchange($job_id);
		}

		return array(
			'id' => $job->ID,
			'title' => $job->post_title,
			'description' => $job->post_content,
			'requester_id' => (int) $job->post_author,
			'requester_name' => get_the_author_meta('display_name', $job->post_author),
			'requester_avatar' => ZAOBank_Helpers::get_user_avatar_url($job->post_author, 48),
			'requester_registered' => $requester ? $requester->user_registered : null,
			'hours' => (float) get_post_meta($job_id, 'hours', true),
			'location' => get_post_meta($job_id, 'location', true),
			'skills_required' => get_post_meta($job_id, 'skills_required', true),
			'preferred_date' => get_post_meta($job_id, 'preferred_date', true),
			'flexible_timing' => (bool) get_post_meta($job_id, 'flexible_timing', true),
			'virtual_ok' => (bool) get_post_meta($job_id, 'virtual_ok', true),
			'provider_id' => $provider_id ? $provider_id : null,
			'provider_name' => $provider_id ? get_the_author_meta('display_name', $provider_id) : '',
			'provider_avatar' => $provider_id ? ZAOBank_Helpers::get_user_avatar_url($provider_id, 48) : '',
			'completed_at' => $completed_at,
			'exchange_id' => $exchange ? $exchange['id'] : null,
			'visibility' => get_post_meta($job_id, 'visibility', true),
			'regions' => $region_data,
			'job_types' => $job_type_data,
			'status' => $job->post_status,
			'created_at' => $job->post_date,
			'modified_at' => $job->post_modified
		);
	}

	/**
	 * Update user region affinity.
	 */
	private static function update_user_region_affinity($user_id, $region_ids) {
		global $wpdb;
		$table = ZAOBank_Database::get_user_regions_table();

		foreach ($region_ids as $region_id) {
			// Check if affinity record exists
			$existing = $wpdb->get_row($wpdb->prepare(
				"SELECT * FROM $table WHERE user_id = %d AND region_term_id = %d",
				$user_id,
				$region_id
			));

			if ($existing) {
				// Update affinity score and last seen
				$wpdb->update(
					$table,
					array(
						'affinity_score' => $existing->affinity_score + 1,
						'last_seen_at' => wp_date('Y-m-d H:i:s')
					),
					array(
						'id' => $existing->id
					),
					array('%d', '%s'),
					array('%d')
				);
			} else {
				// Insert new affinity record
				$wpdb->insert(
					$table,
					array(
						'user_id' => $user_id,
						'region_term_id' => $region_id,
						'affinity_score' => 1,
						'last_seen_at' => wp_date('Y-m-d H:i:s')
					),
					array('%d', '%d', '%d', '%s')
				);
			}
		}
	}
}
