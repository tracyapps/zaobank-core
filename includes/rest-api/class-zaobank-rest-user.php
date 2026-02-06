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

		// Saved profiles (address book)
		register_rest_route($this->namespace, '/me/saved-profiles', array(
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_saved_profiles'),
				'permission_callback' => array($this, 'check_member_access')
			),
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array($this, 'save_profile'),
				'permission_callback' => array($this, 'check_member_access'),
				'args' => array(
					'user_id' => array(
						'required' => true,
						'type' => 'integer',
						'description' => __('User ID to save.', 'zaobank')
					)
				)
			)
		));

		register_rest_route($this->namespace, '/me/saved-profiles/(?P<id>[\d]+)', array(
			'methods' => WP_REST_Server::DELETABLE,
			'callback' => array($this, 'remove_saved_profile'),
			'permission_callback' => array($this, 'check_member_access'),
			'args' => array(
				'id' => array(
					'validate_callback' => function($param) {
						return is_numeric($param);
					}
				)
			)
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
			'permission_callback' => array($this, 'check_member_access')
		));

		// Search users (members only)
		register_rest_route($this->namespace, '/users/search', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array($this, 'search_users'),
			'permission_callback' => array($this, 'check_member_access'),
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

		// Community directory users
		register_rest_route($this->namespace, '/community/users', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array($this, 'get_community_users'),
			'permission_callback' => array($this, 'check_authentication'),
			'args' => array(
				'q' => array(
					'type' => 'string',
					'description' => __('Search query for user name, email, or skills.', 'zaobank')
				),
				'skill' => array(
					'type' => 'string',
					'description' => __('Filter by skill keyword.', 'zaobank')
				),
				'skill_tags' => array(
					'type' => 'array',
					'description' => __('Filter by skill tags.', 'zaobank')
				),
				'profile_tags' => array(
					'type' => 'array',
					'description' => __('Filter by profile tags (legacy).', 'zaobank')
				),
				'region' => array(
					'type' => 'integer',
					'description' => __('Filter by primary region ID.', 'zaobank')
				),
				'sort' => array(
					'type' => 'string',
					'default' => 'recent',
					'description' => __('Sort order (recent, name).', 'zaobank')
				),
				'page' => array(
					'type' => 'integer',
					'default' => 1
				),
				'per_page' => array(
					'type' => 'integer',
					'default' => 12
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
	 * Get saved profiles for the current user.
	 */
	public function get_saved_profiles($request) {
		$user_id = get_current_user_id();
		$saved_ids = $this->get_saved_profile_ids($user_id);

		if (empty($saved_ids)) {
			return $this->success_response(array(
				'users' => array(),
				'ids' => array()
			));
		}

		$users = get_users(array(
			'include' => $saved_ids,
			'orderby' => 'display_name',
			'order' => 'ASC'
		));

		$formatted = array_map(function($user) {
			return $this->format_directory_user($user);
		}, $users);

		return $this->success_response(array(
			'users' => array_values(array_filter($formatted)),
			'ids' => array_values($saved_ids)
		));
	}

	/**
	 * Save a profile to the current user's address book.
	 */
	public function save_profile($request) {
		$user_id = get_current_user_id();
		$target_id = (int) $request->get_param('user_id');

		if (!$target_id) {
			return $this->error_response('invalid_user', __('Invalid user.', 'zaobank'), 400);
		}

		if ($target_id === $user_id) {
			return $this->error_response('invalid_user', __('You cannot save your own profile.', 'zaobank'), 400);
		}

		$target = get_userdata($target_id);
		if (!$target) {
			return $this->error_response('user_not_found', __('User not found.', 'zaobank'), 404);
		}

		$saved_ids = $this->get_saved_profile_ids($user_id);
		if (!in_array($target_id, $saved_ids, true)) {
			$saved_ids[] = $target_id;
			update_user_meta($user_id, 'zaobank_saved_profiles', array_values($saved_ids));
		}

		return $this->success_response(array(
			'message' => __('Profile saved.', 'zaobank'),
			'ids' => array_values($saved_ids)
		));
	}

	/**
	 * Remove a saved profile from the current user's address book.
	 */
	public function remove_saved_profile($request) {
		$user_id = get_current_user_id();
		$target_id = (int) $request['id'];

		$saved_ids = $this->get_saved_profile_ids($user_id);
		$saved_ids = array_values(array_filter($saved_ids, function($id) use ($target_id) {
			return (int) $id !== $target_id;
		}));

		update_user_meta($user_id, 'zaobank_saved_profiles', $saved_ids);

		return $this->success_response(array(
			'message' => __('Profile removed.', 'zaobank'),
			'ids' => array_values($saved_ids)
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

		if (isset($params['user_available_for_requests'])) {
			$available = (int) $params['user_available_for_requests'] ? 1 : 0;
			update_user_meta($user_id, 'user_available_for_requests', $available);
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

		if (isset($params['user_skill_tags']) && is_array($params['user_skill_tags'])) {
			$sanitized_tags = array_map('sanitize_key', $params['user_skill_tags']);
			update_user_meta($user_id, 'user_skill_tags', $sanitized_tags);
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
		$valid_roles = ZAOBank_Security::get_member_access_roles();

		if (empty($valid_roles)) {
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
	 * Get community directory users.
	 */
	public function get_community_users($request) {
		$search = trim(sanitize_text_field((string) $request->get_param('q')));
		$skill = trim(sanitize_text_field((string) $request->get_param('skill')));
		$region = (int) $request->get_param('region');
		$skill_tags = $request->get_param('skill_tags');
		$profile_tags = $request->get_param('profile_tags');
		$sort = sanitize_key($request->get_param('sort') ?: 'recent');
		$page = max(1, (int) $request->get_param('page'));
		$per_page = (int) $request->get_param('per_page');

		if ($per_page < 1) {
			$per_page = 12;
		}
		if ($per_page > 50) {
			$per_page = 50;
		}

		$valid_roles = ZAOBank_Security::get_member_access_roles();

		if (empty($valid_roles)) {
			return $this->success_response(array('users' => array(), 'total' => 0, 'pages' => 0));
		}

		$meta_query = array('relation' => 'AND');

		$availability_clause = array(
			'relation' => 'OR',
			array(
				'key' => 'user_available_for_requests',
				'compare' => 'NOT EXISTS'
			),
			array(
				'key' => 'user_available_for_requests',
				'value' => '1',
				'compare' => '='
			)
		);
		$meta_query[] = $availability_clause;

		if ($region) {
			$meta_query[] = array(
				'key' => 'user_primary_region',
				'value' => $region,
				'compare' => '='
			);
		}

		if ($skill !== '') {
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key' => 'user_skills',
					'value' => $skill,
					'compare' => 'LIKE'
				),
				array(
					'key' => 'user_skill_tags',
					'value' => $skill,
					'compare' => 'LIKE'
				)
			);
		}

		if (!empty($skill_tags)) {
			if (!is_array($skill_tags)) {
				$skill_tags = explode(',', (string) $skill_tags);
			}

			$skill_tags = array_filter(array_map('sanitize_key', (array) $skill_tags));
			foreach ($skill_tags as $tag) {
				$meta_query[] = array(
					'key' => 'user_skill_tags',
					'value' => '"' . $tag . '"',
					'compare' => 'LIKE'
				);
			}
		}

		if (!empty($profile_tags)) {
			if (!is_array($profile_tags)) {
				$profile_tags = explode(',', (string) $profile_tags);
			}

			$profile_tags = array_filter(array_map('sanitize_key', (array) $profile_tags));
			foreach ($profile_tags as $tag) {
				$meta_query[] = array(
					'key' => 'user_profile_tags',
					'value' => '"' . $tag . '"',
					'compare' => 'LIKE'
				);
			}
		}

		$args = array(
			'number' => $per_page,
			'paged' => $page,
			'role__in' => $valid_roles,
			'meta_query' => $meta_query,
			'exclude' => array(get_current_user_id())
		);

		if ($search !== '') {
			$args['search'] = '*' . $search . '*';
			$args['search_columns'] = array('display_name', 'user_login', 'user_email');
		}

		if ($sort === 'name') {
			$args['orderby'] = 'display_name';
			$args['order'] = 'ASC';
		} else {
			$args['orderby'] = 'registered';
			$args['order'] = 'DESC';
		}

		$user_query = new WP_User_Query($args);
		$users = array_map(function($user) {
			return $this->format_directory_user($user);
		}, $user_query->get_results());
		$users = array_values(array_filter($users));

		$total = (int) $user_query->get_total();
		$pages = $per_page ? (int) ceil($total / $per_page) : 1;

		return $this->success_response(array(
			'users' => $users,
			'total' => $total,
			'pages' => $pages
		));
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
	 * Get saved profile IDs for a user.
	 */
	private function get_saved_profile_ids($user_id) {
		$saved_ids = get_user_meta($user_id, 'zaobank_saved_profiles', true);
		if (!is_array($saved_ids)) {
			return array();
		}

		$saved_ids = array_filter(array_map('intval', $saved_ids));
		$saved_ids = array_values(array_unique($saved_ids));

		return $saved_ids;
	}

	/**
	 * Format a user for directory/address book cards.
	 */
	private function format_directory_user($user) {
		if (!$user) {
			return null;
		}

		$region_id = get_user_meta($user->ID, 'user_primary_region', true);
		$region_data = null;
		if ($region_id) {
			$region = get_term($region_id, 'zaobank_region');
			if ($region && !is_wp_error($region)) {
				$region_data = array(
					'id' => $region->term_id,
					'name' => $region->name,
					'slug' => $region->slug
				);
			}
		}

		$available_raw = get_user_meta($user->ID, 'user_available_for_requests', true);
		$available_for_requests = ($available_raw === '' || $available_raw === null) ? true : (bool) $available_raw;

		return array(
			'id' => $user->ID,
			'name' => $user->display_name,
			'display_name' => $user->display_name,
			'avatar_url' => ZAOBank_Helpers::get_user_avatar_url($user->ID, 64),
			'skills' => get_user_meta($user->ID, 'user_skills', true),
			'skill_tags' => get_user_meta($user->ID, 'user_skill_tags', true),
			'availability' => get_user_meta($user->ID, 'user_availability', true),
			'available_for_requests' => $available_for_requests,
			'profile_tags' => get_user_meta($user->ID, 'user_profile_tags', true),
			'primary_region' => $region_data
		);
	}

	/**
	 * Format user profile data.
	 */
	private function format_user_profile($user, $public_only = false) {
		$available_raw = get_user_meta($user->ID, 'user_available_for_requests', true);
		$available_for_requests = ($available_raw === '' || $available_raw === null) ? true : (bool) $available_raw;

		$profile = array(
			'id' => $user->ID,
			'name' => $user->display_name,
			'display_name' => $user->display_name,
			'email' => $public_only ? null : $user->user_email,
			'avatar_url' => ZAOBank_Helpers::get_user_avatar_url($user->ID, 96),
			'skills' => get_user_meta($user->ID, 'user_skills', true),
			'skill_tags' => get_user_meta($user->ID, 'user_skill_tags', true),
			'availability' => get_user_meta($user->ID, 'user_availability', true),
			'available_for_requests' => $available_for_requests,
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
