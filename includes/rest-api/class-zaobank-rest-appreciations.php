<?php
/**
 * REST API: Appreciations endpoints.
 */
class ZAOBank_REST_Appreciations extends ZAOBank_REST_Controller {

	public function register_routes() {
		register_rest_route($this->namespace, '/appreciations', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array($this, 'create_appreciation'),
			'permission_callback' => array($this, 'check_member_access')
		));

		register_rest_route($this->namespace, '/me/appreciations/given', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array($this, 'get_given_appreciations'),
			'permission_callback' => array($this, 'check_member_access')
		));

		register_rest_route($this->namespace, '/users/(?P<id>[\d]+)/appreciations', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array($this, 'get_user_appreciations'),
			'permission_callback' => '__return_true'
		));
	}

	public function create_appreciation($request) {
		$data = $request->get_params();
		$data['from_user_id'] = get_current_user_id();

		$appreciation_id = ZAOBank_Appreciations::create_appreciation($data);

		if (is_wp_error($appreciation_id)) {
			return $this->error_response(
				$appreciation_id->get_error_code(),
				$appreciation_id->get_error_message()
			);
		}

		return $this->success_response(array(
			'message' => __('Appreciation created successfully', 'zaobank'),
			'id' => $appreciation_id
		), 201);
	}

	public function get_user_appreciations($request) {
		$user_id = (int) $request['id'];
		$appreciations = ZAOBank_Appreciations::get_user_appreciations($user_id, true);

		return $this->success_response(array(
			'appreciations' => $appreciations
		));
	}

	public function get_given_appreciations($request) {
		$user_id = get_current_user_id();
		$appreciations = ZAOBank_Appreciations::get_given_appreciations($user_id);

		return $this->success_response(array(
			'appreciations' => $appreciations
		));
	}
}
