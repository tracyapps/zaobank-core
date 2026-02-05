<?php
/**
 * REST API: User endpoints.
 */
class ZAOBank_REST_User extends ZAOBank_REST_Controller {

	/**
	 * Register routes.
	 */
	public function register_routes() {
		// Get current user balance
		register_rest_route($this->namespace, '/me/balance', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array($this, 'get_balance'),
			'permission_callback' => array($this, 'check_authentication')
		));

		// Get current user exchanges
		register_rest_route($this->namespace, '/me/exchanges', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array($this, 'get_exchanges'),
			'permission_callback' => array($this, 'check_authentication'),
			'args' => array(
				'page' => array(
					'default' => 1,
					'type' => 'integer'
				),
				'per_page' => array(
					'default' => 20,
					'type' => 'integer'
				),
				'filter' => array(
					'default' => 'all',
					'type' => 'string',
					'description' => __('Filter exchanges (all, earned, spent).', 'zaobank'),
					'validate_callback' => function($value) {
						return in_array($value, array('all', 'earned', 'spent'), true);
					}
				)
			)
		));

		// Get people the user has worked with
		register_rest_route($this->namespace, '/me/worked-with', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array($this, 'get_worked_with'),
			'permission_callback' => array($this, 'check_authentication')
		));

		// Get current user profile
		register_rest_route($this->namespace, '/me/profile', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array($this, 'get_profile'),
			'permission_callback' => array($this, 'check_authentication')
		));

		// Update current user profile
		register_rest_route($this->namespace, '/me/profile', array(
			'methods' => WP_REST_Server::EDITABLE,
			'callback' => array($this, 'update_profile'),
			'permission_callback' => array($this, 'check_authentication')
		));

		// Search users (members only)
		register_rest_route($this->namespace, '/users/search', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array($this, 'search_users'),
			'permission_callback' => array($this, 'check_authentication'),
			'args' => array(
				'q' => array(
					'type' => 'string',
					'description' => __('Search query for user name or email.', 'zaobank')
				),
				'limit' => array(
					'type' => 'integer',
					'default' => 10
				)
			)
		));

		// Get user by ID
		register_rest_route($this->namespace, '/users/(?P<id>[\d]+)', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array($this, 'get_user'),
			'permission_callback' => '__return_true',
			'args' => array(
				'id' => array(
					'validate_callback' => function($param) {
						return is_numeric($param);
					}
				)
			)
		));

		// Get user statistics
		register_rest_route($this->namespace, '/me/statistics', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array($this, 'get_statistics'),
			'permission_callback' => array($this, 'check_authentication')
		));
	}

	/**
	 * Get current user's balance.
	 */
	public function get_balance($request) {
		$user_id = get_current_user_id();
		$balance = ZAOBank_Exchanges::get_user_balance($user_id);

		return $this->success_response($balance);
	}

	/**
	 * Get current user's exchanges.
	 */
	public function get_exchanges($request) {
		$user_id = get_current_user_id();
		$params = $this->get_pagination_params($request);
		$filter = $request->get_param('filter') ?: 'all';

		$args = array(
			'limit' => $params['per_page'],
			'offset' => ($params['page'] - 1) * $params['per_page'],
			'type' => $filter
		);

		$exchanges = ZAOBank_Exchanges::get_user_exchanges($user_id, $args);

		// Get total count
		global $wpdb;
		$table = ZAOBank_Database::get_exchanges_table();
		$where_sql = '(provider_user_id = %d OR requester_user_id = %d)';
		$where_values = array($user_id, $user_id);

		if ($filter === 'earned') {
			$where_sql = 'provider_user_id = %d';
			$where_values = array($user_id);
		} elseif ($filter === 'spent') {
			$where_sql = 'requester_user_id = %d';
			$where_values = array($user_id);
		}

		$total = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM $table WHERE $where_sql",
			$where_values
		));

		// Add appreciation flag for current user
		if (!empty($exchanges)) {
			$exchange_ids = array_map(function($exchange) {
				return (int) $exchange['id'];
			}, $exchanges);

			$app_table = ZAOBank_Database::get_appreciations_table();
			$placeholders = implode(',', array_fill(0, count($exchange_ids), '%d'));

			$appreciated_ids = $wpdb->get_col($wpdb->prepare(
				"SELECT exchange_id FROM $app_table WHERE from_user_id = %d AND exchange_id IN ($placeholders)",
				array_merge(array($user_id), $exchange_ids)
			));

			$lookup = array_fill_keys(array_map('intval', $appreciated_ids), true);
			foreach ($exchanges as $index => $exchange) {
				$exchanges[$index]['has_appreciation'] = isset($lookup[(int) $exchange['id']]);
			}
		}

		$response = $this->success_response(array(
			'exchanges' => $exchanges,
			'total' => (int) $total,
			'pages' => ceil($total / $params['per_page'])
		));

		// Add pagination headers
		$headers = $this->prepare_pagination_headers($total, $params['per_page'], $params['page']);
		foreach ($headers as $key => $value) {
			$response->header($key, $value);
		}

		return $response;
	}

	/**
	 * Get a summary of people the current user has worked with.
	 */
	public function get_worked_with($request) {
		$user_id = get_current_user_id();
		$people = ZAOBank_Exchanges::get_worked_with_summary($user_id);

		return $this->success_response(array(
			'people' => $people
		));
	}

	/**
	 * Get current user's profile.
	 */
	public function get_profile($request) {
		$user_id = get_current_user_id();
		$user = get_userdata($user_id);

		if (!$user) {
			return $this->error_response(
				'user_not_found',
				__('User not found', 'zaobank'),
				404
			);
		}

		$profile = $this->format_user_profile($user);

		return $this->success_response($profile);
	}

	/**
	 * Update current user's profile.
	 */
	public function update_profile($request) {
		$user_id = get_current_user_id();
		$params = $request->get_params();

		if (isset($params['display_name'])) {
			$display_name = sanitize_text_field($params['display_name']);
			if ($display_name !== '') {
				wp_update_user(array(
					'ID' => $user_id,
					'display_name' => $display_name,
					'nickname' => $display_name
				));
			}
		}

		// Update user meta fields
		if (isset($params['user_skills'])) {
			update_user_meta($user_id, 'user_skills', sanitize_textarea_field($params['user_skills']));
		}

		if (isset($params['user_availability'])) {
			update_user_meta($user_id, 'user_availability', sanitize_text_field($params['user_availability']));
		}

		if (isset($params['user_bio'])) {
			update_user_meta($user_id, 'user_bio', sanitize_textarea_field($params['user_bio']));
		}

		if (isset($params['user_primary_region'])) {
			update_user_meta($user_id, 'user_primary_region', (int) $params['user_primary_region']);
		}

		if (isset($params['user_profile_tags']) && is_array($params['user_profile_tags'])) {
			$sanitized_tags = array_map('sanitize_key', $params['user_profile_tags']);
			update_user_meta($user_id, 'user_profile_tags', $sanitized_tags);
		}

		if (isset($params['user_contact_preferences']) && is_array($params['user_contact_preferences'])) {
			$sanitized_prefs = array_map('sanitize_key', $params['user_contact_preferences']);
			update_user_meta($user_id, 'user_contact_preferences', $sanitized_prefs);
		}

		if (isset($params['user_phone'])) {
			update_user_meta($user_id, 'user_phone', sanitize_text_field($params['user_phone']));
		}

		if (isset($params['user_discord_id'])) {
			update_user_meta($user_id, 'user_discord_id', sanitize_text_field($params['user_discord_id']));
		}

		if (isset($params['user_profile_image'])) {
			$image_id = absint($params['user_profile_image']);
			if ($image_id && get_post($image_id) && wp_attachment_is_image($image_id)) {
				update_user_meta($user_id, 'user_profile_image', $image_id);
			} elseif ($image_id === 0) {
				delete_user_meta($user_id, 'user_profile_image');
			}
		}

		$user = get_userdata($user_id);
		$profile = $this->format_user_profile($user);

		return $this->success_response(array(
			'message' => __('Profile updated successfully', 'zaobank'),
			'profile' => $profile
		));
	}

	/**
	 * Search verified users for messaging.
	 */
	public function search_users($request) {
		$search = trim(sanitize_text_field((string) $request->get_param('q')));
		$limit = (int) $request->get_param('limit');

		if ($limit < 1) {
			$limit = 10;
		}
		if ($limit > 25) {
			$limit = 25;
		}

		if (strlen($search) < 2) {
			return $this->success_response(array('users' => array()));
		}

		$current_user_id = get_current_user_id();
		$roles = get_option('zaobank_message_search_roles', array('member'));
		$roles = apply_filters('zaobank_message_search_roles', $roles);
		$valid_roles = array();
		foreach ((array) $roles as $role) {
			if (wp_roles()->is_role($role)) {
				$valid_roles[] = $role;
			}
		}

		if (empty($roles) || empty($valid_roles)) {
			return $this->success_response(array('users' => array()));
		}

		global $wpdb;

		$args = array(
			'search' => '*' . $wpdb->esc_like($search) . '*',
			'search_columns' => array('user_login', 'user_nicename', 'display_name', 'user_email'),
			'number' => $limit,
			'orderby' => 'display_name',
			'order' => 'ASC',
			'exclude' => array($current_user_id)
		);

		$args['role__in'] = $valid_roles;

		$query = new WP_User_Query($args);
		$users = array();

		foreach ($query->get_results() as $user) {
			$users[] = array(
				'id' => $user->ID,
				'name' => $user->display_name,
				'avatar_url' => ZAOBank_Helpers::get_user_avatar_url($user->ID, 40)
			);
		}

		return $this->success_response(array('users' => $users));
	}

	/**
	 * Get user by ID (public profile).
	 */
	public function get_user($request) {
		$user_id = (int) $request['id'];
		$user = get_userdata($user_id);

		if (!$user) {
			return $this->error_response(
				'user_not_found',
				__('User not found', 'zaobank'),
				404
			);
		}

		$profile = $this->format_user_profile($user, true); // Public view

		return $this->success_response($profile);
	}

	/**
	 * Get current user's statistics.
	 */
	public function get_statistics($request) {
		$user_id = get_current_user_id();

		// Get balance
		$balance = ZAOBank_Exchanges::get_user_balance($user_id);

		// Get job statistics
		global $wpdb;

		$jobs_requested = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type = 'timebank_job' AND post_author = %d",
			$user_id
		));

		$jobs_claimed = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->postmeta} 
            WHERE meta_key = 'provider_user_id' AND meta_value = %d",
			$user_id
		));

		$jobs_completed = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->postmeta} pm1
            INNER JOIN {$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
            WHERE pm1.meta_key = 'provider_user_id' AND pm1.meta_value = %d
            AND pm2.meta_key = 'completed_at'",
			$user_id
		));

		// Get appreciation count
		$appreciations_table = ZAOBank_Database::get_appreciations_table();
		$appreciations_received = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM $appreciations_table 
            WHERE to_user_id = %d AND is_public = 1",
			$user_id
		));

		$statistics = array(
			'balance' => $balance,
			'jobs_requested' => (int) $jobs_requested,
			'jobs_claimed' => (int) $jobs_claimed,
			'jobs_completed' => (int) $jobs_completed,
			'appreciations_received' => (int) $appreciations_received
		);

		return $this->success_response($statistics);
	}

	/**
	 * Format user profile data.
	 */
	private function format_user_profile($user, $public_only = false) {
		$profile = array(
			'id' => $user->ID,
			'name' => $user->display_name,
			'display_name' => $user->display_name,
			'email' => $public_only ? null : $user->user_email,
			'avatar_url' => ZAOBank_Helpers::get_user_avatar_url($user->ID, 96),
			'skills' => get_user_meta($user->ID, 'user_skills', true),
			'availability' => get_user_meta($user->ID, 'user_availability', true),
			'bio' => get_user_meta($user->ID, 'user_bio', true),
			'profile_tags' => get_user_meta($user->ID, 'user_profile_tags', true),
			'registered' => $user->user_registered
		);

		// Add primary region
		$region_id = get_user_meta($user->ID, 'user_primary_region', true);
		if ($region_id) {
			$region = get_term($region_id, 'zaobank_region');
			if ($region && !is_wp_error($region)) {
				$profile['primary_region'] = array(
					'id' => $region->term_id,
					'name' => $region->name,
					'slug' => $region->slug
				);
			}
		}

		// Private fields (only for own profile)
		// Discord ID is public (visible on all profiles for connection)
		$profile['discord_id'] = get_user_meta($user->ID, 'user_discord_id', true);

		// Check if user has Signal in their contact preferences
		$contact_prefs = get_user_meta($user->ID, 'user_contact_preferences', true);
		$profile['has_signal'] = is_array($contact_prefs) && in_array('signal', $contact_prefs);

		if (!$public_only) {
			$profile['contact_preferences'] = $contact_prefs;
			$profile['phone'] = get_user_meta($user->ID, 'user_phone', true);
			$profile['profile_image_id'] = (int) get_user_meta($user->ID, 'user_profile_image', true);
		}

		return $profile;
	}
}
