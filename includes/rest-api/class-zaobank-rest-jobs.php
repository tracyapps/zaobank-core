<?php
/**
 * REST API: Jobs endpoints.
 */
class ZAOBank_REST_Jobs extends ZAOBank_REST_Controller {

	/**
	 * Register routes.
	 */
	public function register_routes() {
		// List jobs
		register_rest_route($this->namespace, '/jobs', array(
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_jobs'),
				'permission_callback' => '__return_true',
				'args' => $this->get_collection_params()
			),
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array($this, 'create_job'),
				'permission_callback' => array($this, 'create_job_permissions_check'),
				'args' => $this->get_create_job_params()
			)
		));

		// Single job
		register_rest_route($this->namespace, '/jobs/(?P<id>[\d]+)', array(
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_job'),
				'permission_callback' => '__return_true',
				'args' => array(
					'id' => array(
						'validate_callback' => function($param) {
							return is_numeric($param);
						}
					)
				)
			),
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array($this, 'update_job'),
				'permission_callback' => array($this, 'update_job_permissions_check'),
				'args' => $this->get_update_job_params()
			),
			array(
				'methods' => WP_REST_Server::DELETABLE,
				'callback' => array($this, 'delete_job'),
				'permission_callback' => array($this, 'delete_job_permissions_check')
			)
		));

		// Claim job
		register_rest_route($this->namespace, '/jobs/(?P<id>[\d]+)/claim', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array($this, 'claim_job'),
			'permission_callback' => array($this, 'check_authentication'),
			'args' => array(
				'id' => array(
					'validate_callback' => function($param) {
						return is_numeric($param);
					}
				)
			)
		));

		// Complete job
		register_rest_route($this->namespace, '/jobs/(?P<id>[\d]+)/complete', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array($this, 'complete_job'),
			'permission_callback' => array($this, 'check_authentication'),
			'args' => array(
				'id' => array(
					'validate_callback' => function($param) {
						return is_numeric($param);
					}
				)
			)
		));

		// Get my jobs
		register_rest_route($this->namespace, '/jobs/mine', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array($this, 'get_my_jobs'),
			'permission_callback' => array($this, 'check_authentication')
		));

		// List job types
		register_rest_route($this->namespace, '/job-types', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array($this, 'get_job_types'),
			'permission_callback' => '__return_true'
		));
	}

	/**
	 * Get jobs list.
	 */
	public function get_jobs($request) {
		$params = $this->get_pagination_params($request);

		$args = array(
			'posts_per_page' => $params['per_page'],
			'paged' => $params['page']
		);

		// Filter by region
		if ($request->get_param('region')) {
			$args['region'] = (int) $request->get_param('region');
		}

		// Filter by job types
		$job_types = $request->get_param('job_types');
		if (!empty($job_types)) {
			if (!is_array($job_types)) {
				$job_types = explode(',', $job_types);
			}
			$job_types = array_map('intval', $job_types);

			if (!isset($args['tax_query'])) {
				$args['tax_query'] = array();
			}
			$args['tax_query'][] = array(
				'taxonomy' => 'zaobank_job_type',
				'field'    => 'term_id',
				'terms'    => $job_types,
			);
		}

		// Filter by status
		$status = $request->get_param('status');
		if ($status === 'available') {
			// Only unclaimed jobs
			$args['meta_query'] = array(
				array(
					'key' => 'provider_user_id',
					'compare' => 'NOT EXISTS'
				)
			);
		} elseif ($status === 'claimed') {
			$args['meta_query'] = array(
				array(
					'key' => 'provider_user_id',
					'compare' => 'EXISTS'
				),
				array(
					'key' => 'completed_at',
					'compare' => 'NOT EXISTS'
				)
			);
		} elseif ($status === 'completed') {
			$args['meta_query'] = array(
				array(
					'key' => 'completed_at',
					'compare' => 'EXISTS'
				)
			);
		}

		$jobs = ZAOBank_Jobs::get_available_jobs($args);

		// Get total for pagination
		$count_args = $args;
		$count_args['posts_per_page'] = -1;
		$count_args['fields'] = 'ids';
		$count_query = new WP_Query($count_args);
		$total = $count_query->post_count;

		$response = $this->success_response(array(
			'jobs' => $jobs,
			'total' => $total,
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
	 * Get single job.
	 */
	public function get_job($request) {
		$job_id = (int) $request['id'];
		$job = ZAOBank_Jobs::format_job_data($job_id);

		if (!$job) {
			return $this->error_response(
				'job_not_found',
				__('Job not found', 'zaobank'),
				404
			);
		}

		// Check visibility
		if (!ZAOBank_Security::is_content_visible('job', $job_id)) {
			return $this->error_response(
				'job_not_available',
				__('This job is not available', 'zaobank'),
				403
			);
		}

		return $this->success_response($job);
	}

	/**
	 * Create job.
	 */
	public function create_job($request) {
		// Rate limiting
		$rate_check = ZAOBank_Security::check_rate_limit('create_job', null, 10, 3600);
		if (is_wp_error($rate_check)) {
			return $rate_check;
		}

		$data = ZAOBank_Security::sanitize_job_data($request->get_params());
		$job_id = ZAOBank_Jobs::create_job($data);

		if (is_wp_error($job_id)) {
			return $this->error_response(
				$job_id->get_error_code(),
				$job_id->get_error_message()
			);
		}

		$job = ZAOBank_Jobs::format_job_data($job_id);

		return $this->success_response(array(
			'message' => __('Job created successfully', 'zaobank'),
			'job' => $job
		), 201);
	}

	/**
	 * Claim job.
	 */
	public function claim_job($request) {
		$job_id = (int) $request['id'];

		$result = ZAOBank_Jobs::claim_job($job_id);

		if (is_wp_error($result)) {
			return $this->error_response(
				$result->get_error_code(),
				$result->get_error_message()
			);
		}

		$job = ZAOBank_Jobs::format_job_data($job_id);

		return $this->success_response(array(
			'message' => __('Job claimed successfully', 'zaobank'),
			'job' => $job
		));
	}

	/**
	 * Complete job.
	 */
	public function complete_job($request) {
		$job_id = (int) $request['id'];

		$exchange_id = ZAOBank_Jobs::complete_job($job_id);

		if (is_wp_error($exchange_id)) {
			return $this->error_response(
				$exchange_id->get_error_code(),
				$exchange_id->get_error_message()
			);
		}

		$job = ZAOBank_Jobs::format_job_data($job_id);
		$exchange = ZAOBank_Exchanges::get_exchange($exchange_id);

		return $this->success_response(array(
			'message' => __('Job completed successfully', 'zaobank'),
			'job' => $job,
			'exchange' => $exchange
		));
	}

	/**
	 * Get current user's jobs.
	 */
	public function get_my_jobs($request) {
		$user_id = get_current_user_id();

		$args = array(
			'post_type' => 'timebank_job',
			'posts_per_page' => -1
		);

		$type = $request->get_param('type');

		if ($type === 'requested') {
			// Jobs created by user
			$args['author'] = $user_id;
		} elseif ($type === 'claimed') {
			// Jobs claimed by user
			$args['meta_query'] = array(
				array(
					'key' => 'provider_user_id',
					'value' => $user_id
				)
			);
		} else {
			// All jobs (requested or claimed)
			$args['author'] = $user_id;
		}

		$query = new WP_Query($args);
		$jobs = array();

		if ($query->have_posts()) {
			while ($query->have_posts()) {
				$query->the_post();
				$jobs[] = ZAOBank_Jobs::format_job_data(get_the_ID());
			}
			wp_reset_postdata();
		}

		// If type is not specified, also get claimed jobs
		if (!$type) {
			$claimed_args = array(
				'post_type' => 'timebank_job',
				'posts_per_page' => -1,
				'meta_query' => array(
					array(
						'key' => 'provider_user_id',
						'value' => $user_id
					)
				)
			);

			$claimed_query = new WP_Query($claimed_args);

			if ($claimed_query->have_posts()) {
				while ($claimed_query->have_posts()) {
					$claimed_query->the_post();
					$jobs[] = ZAOBank_Jobs::format_job_data(get_the_ID());
				}
				wp_reset_postdata();
			}
		}

		return $this->success_response(array(
			'jobs' => $jobs,
			'total' => count($jobs)
		));
	}

	/**
	 * Permission check for creating jobs.
	 */
	public function create_job_permissions_check($request) {
		$auth_check = $this->check_authentication($request);
		if (is_wp_error($auth_check)) {
			return $auth_check;
		}

		if (!ZAOBank_Security::can_create_job()) {
			return $this->error_response(
				'rest_forbidden',
				__('You do not have permission to create jobs.', 'zaobank'),
				403
			);
		}

		return true;
	}

	/**
	 * Permission check for updating jobs.
	 */
	public function update_job_permissions_check($request) {
		$auth_check = $this->check_authentication($request);
		if (is_wp_error($auth_check)) {
			return $auth_check;
		}

		$job_id = (int) $request['id'];

		if (!ZAOBank_Security::can_edit_job($job_id)) {
			return $this->error_response(
				'rest_forbidden',
				__('You do not have permission to edit this job.', 'zaobank'),
				403
			);
		}

		return true;
	}

	/**
	 * Permission check for deleting jobs.
	 */
	public function delete_job_permissions_check($request) {
		return $this->update_job_permissions_check($request);
	}

	/**
	 * Get collection parameters.
	 */
	public function get_collection_params() {
		return array(
			'page' => array(
				'description' => __('Current page of the collection.', 'zaobank'),
				'type' => 'integer',
				'default' => 1,
				'minimum' => 1
			),
			'per_page' => array(
				'description' => __('Maximum number of items to be returned.', 'zaobank'),
				'type' => 'integer',
				'default' => 20,
				'minimum' => 1,
				'maximum' => 100
			),
			'region' => array(
				'description' => __('Filter by region ID.', 'zaobank'),
				'type' => 'integer'
			),
			'status' => array(
				'description' => __('Filter by job status.', 'zaobank'),
				'type' => 'string',
				'enum' => array('available', 'claimed', 'completed')
			),
			'job_types' => array(
				'description' => __('Filter by job type term IDs (comma-separated or array).', 'zaobank'),
				'type' => 'array',
				'items' => array('type' => 'integer')
			)
		);
	}

	/**
	 * Get create job parameters.
	 */
	public function get_create_job_params() {
		return array(
			'title' => array(
				'required' => true,
				'type' => 'string',
				'description' => __('Job title', 'zaobank')
			),
			'description' => array(
				'type' => 'string',
				'description' => __('Job description', 'zaobank')
			),
			'hours' => array(
				'required' => true,
				'type' => 'number',
				'description' => __('Estimated hours', 'zaobank'),
				'minimum' => 0.25,
				'maximum' => 100
			),
			'location' => array(
				'type' => 'string',
				'description' => __('Job location', 'zaobank')
			),
			'regions' => array(
				'type' => 'array',
				'items' => array('type' => 'integer'),
				'description' => __('Region IDs', 'zaobank')
			),
			'job_types' => array(
				'type' => 'array',
				'items' => array('type' => 'integer'),
				'description' => __('Job Type IDs', 'zaobank')
			)
		);
	}

	/**
	 * Get update job parameters.
	 */
	public function get_update_job_params() {
		$params = $this->get_create_job_params();

		// Make all fields optional for updates
		foreach ($params as &$param) {
			unset($param['required']);
		}

		return $params;
	}

	/**
	 * Get all job types.
	 */
	public function get_job_types($request) {
		$terms = get_terms(array(
			'taxonomy'   => 'zaobank_job_type',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		));

		if (is_wp_error($terms)) {
			return $this->success_response(array('job_types' => array()));
		}

		$job_types = array();
		foreach ($terms as $term) {
			$job_types[] = array(
				'id'    => $term->term_id,
				'name'  => $term->name,
				'slug'  => $term->slug,
				'count' => $term->count,
			);
		}

		return $this->success_response(array('job_types' => $job_types));
	}
}