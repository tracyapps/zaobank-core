<?php
/**
 * REST API: Flags endpoints.
 */
class ZAOBank_REST_Flags extends ZAOBank_REST_Controller {

	public function register_routes() {
		// Create a flag
		register_rest_route($this->namespace, '/flags', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array($this, 'create_flag'),
			'permission_callback' => array($this, 'check_authentication')
		));

		// Get flags for review (admin only)
		register_rest_route($this->namespace, '/flags', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array($this, 'get_flags'),
			'permission_callback' => array($this, 'check_review_permission')
		));

		// Update flag status (admin only)
		register_rest_route($this->namespace, '/flags/(?P<id>[\d]+)', array(
			'methods' => WP_REST_Server::EDITABLE,
			'callback' => array($this, 'update_flag'),
			'permission_callback' => array($this, 'check_review_permission')
		));
	}

	public function create_flag($request) {
		$data = $request->get_params();
		$data['reporter_user_id'] = get_current_user_id();

		$flag_id = ZAOBank_Flags::create_flag($data);

		if (is_wp_error($flag_id)) {
			return $this->error_response(
				$flag_id->get_error_code(),
				$flag_id->get_error_message()
			);
		}

		return $this->success_response(array(
			'message' => __('Content flagged for review', 'zaobank'),
			'id' => $flag_id
		), 201);
	}

	public function get_flags($request) {
		$status = $request->get_param('status') ?: 'open';
		$flags = ZAOBank_Flags::get_flags_for_review($status);

		return $this->success_response($flags);
	}

	public function update_flag($request) {
		$flag_id = (int) $request['id'];
		$status = $request->get_param('status');
		$resolution_note = $request->get_param('resolution_note');

		$result = ZAOBank_Flags::update_flag_status($flag_id, $status, $resolution_note);

		if (is_wp_error($result)) {
			return $this->error_response(
				$result->get_error_code(),
				$result->get_error_message()
			);
		}

		return $this->success_response(array(
			'message' => __('Flag updated successfully', 'zaobank')
		));
	}

	public function check_review_permission($request) {
		$auth_check = $this->check_authentication($request);
		if (is_wp_error($auth_check)) {
			return $auth_check;
		}

		if (!ZAOBank_Security::can_review_flags()) {
			return $this->error_response(
				'rest_forbidden',
				__('You do not have permission to review flags.', 'zaobank'),
				403
			);
		}

		return true;
	}
}