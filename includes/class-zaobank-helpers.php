<?php
/**
 * Helper and utility functions.
 */
class ZAOBank_Helpers {

	/**
	 * Format hours for display.
	 */
	public static function format_hours($hours) {
		$hours = floatval($hours);

		if ($hours == 1) {
			return '1 hour';
		}

		return number_format($hours, 2) . ' hours';
	}

	/**
	 * Get time ago string.
	 */
	public static function time_ago($datetime) {
		$time = strtotime($datetime);
		$diff = time() - $time;

		if ($diff < 60) {
			return 'just now';
		}

		if ($diff < 3600) {
			$mins = floor($diff / 60);
			return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
		}

		if ($diff < 86400) {
			$hours = floor($diff / 3600);
			return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
		}

		if ($diff < 604800) {
			$days = floor($diff / 86400);
			return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
		}

		return date('M j, Y', $time);
	}

	/**
	 * Get avatar HTML.
	 */
	public static function get_user_avatar($user_id, $size = 48) {
		// Check ACF profile image first
		$image_id = get_user_meta($user_id, 'user_profile_image', true);
		if ($image_id) {
			$image_url = wp_get_attachment_image_url($image_id, 'thumbnail');
			if ($image_url) {
				return '<img src="' . esc_url($image_url) . '" alt="" width="' . esc_attr($size) . '" height="' . esc_attr($size) . '" class="zaobank-avatar">';
			}
		}
		return get_avatar($user_id, $size, '', '', array('class' => 'zaobank-avatar'));
	}

	/**
	 * Get user avatar URL (for API responses).
	 */
	public static function get_user_avatar_url($user_id, $size = 96) {
		$image_id = get_user_meta($user_id, 'user_profile_image', true);
		if ($image_id) {
			$image_url = wp_get_attachment_image_url($image_id, 'thumbnail');
			if ($image_url) {
				return $image_url;
			}
		}
		return get_avatar_url($user_id, array('size' => $size));
	}

	/**
	 * Build hierarchical region tree.
	 */
	public static function build_region_tree($terms, $parent = 0) {
		$branch = array();

		foreach ($terms as $term) {
			if ($term->parent == $parent) {
				$children = self::build_region_tree($terms, $term->term_id);

				$item = array(
					'id' => $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
					'parent' => $term->parent
				);

				if ($children) {
					$item['children'] = $children;
				}

				$branch[] = $item;
			}
		}

		return $branch;
	}

	/**
	 * Get region ancestors.
	 */
	public static function get_region_ancestors($term_id) {
		$ancestors = array();
		$term = get_term($term_id, 'zaobank_region');

		while ($term && !is_wp_error($term) && $term->parent > 0) {
			$term = get_term($term->parent, 'zaobank_region');
			if ($term && !is_wp_error($term)) {
				array_unshift($ancestors, $term);
			}
		}

		return $ancestors;
	}

	/**
	 * Sanitize and validate region IDs.
	 */
	public static function validate_regions($region_ids) {
		if (!is_array($region_ids)) {
			$region_ids = array($region_ids);
		}

		$valid_regions = array();

		foreach ($region_ids as $region_id) {
			$region = get_term($region_id, 'zaobank_region');
			if ($region && !is_wp_error($region)) {
				$valid_regions[] = (int) $region_id;
			}
		}

		return $valid_regions;
	}

	/**
	 * Check if user has completed onboarding.
	 */
	public static function has_completed_onboarding($user_id) {
		return (bool) get_user_meta($user_id, 'zaobank_onboarding_completed', true);
	}

	/**
	 * Mark onboarding as complete.
	 */
	public static function complete_onboarding($user_id) {
		update_user_meta($user_id, 'zaobank_onboarding_completed', true);
		update_user_meta($user_id, 'zaobank_onboarding_completed_at', wp_date('Y-m-d H:i:s'));
	}

	/**
	 * Get user display name with fallback.
	 */
	public static function get_user_display_name($user_id) {
		$user = get_userdata($user_id);

		if (!$user) {
			return __('Unknown User', 'zaobank');
		}

		return $user->display_name ?: $user->user_login;
	}

	/**
	 * Format currency (hours).
	 */
	public static function format_balance($balance) {
		$balance = floatval($balance);
		$class = $balance >= 0 ? 'positive' : 'negative';
		$sign = $balance >= 0 ? '+' : '';

		return sprintf(
			'<span class="zaobank-balance zaobank-balance-%s">%s%s</span>',
			$class,
			$sign,
			self::format_hours(abs($balance))
		);
	}

	/**
	 * Get job status label.
	 */
	public static function get_job_status_label($job_id) {
		$provider_id = get_post_meta($job_id, 'provider_user_id', true);
		$completed_at = get_post_meta($job_id, 'completed_at', true);

		if ($completed_at) {
			return array(
				'label' => __('Completed', 'zaobank'),
				'class' => 'completed'
			);
		}

		if ($provider_id) {
			return array(
				'label' => __('In Progress', 'zaobank'),
				'class' => 'in-progress'
			);
		}

		return array(
			'label' => __('Available', 'zaobank'),
			'class' => 'available'
		);
	}
}