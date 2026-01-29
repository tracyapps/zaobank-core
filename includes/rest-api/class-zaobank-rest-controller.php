<?php
/**
 * Base REST API controller.
 */
class ZAOBank_REST_Controller extends WP_REST_Controller {

	/**
	 * Namespace for REST API routes.
	 */
	protected $namespace = 'zaobank/v1';

	/**
	 * Check if user is authenticated for REST requests.
	 */
	public function check_authentication($request) {
		if (!is_user_logged_in()) {
			return new WP_Error(
				'rest_forbidden',
				__('You must be logged in to perform this action.', 'zaobank'),
				array('status' => 401)
			);
		}

		return true;
	}

	/**
	 * Prepare pagination headers.
	 */
	protected function prepare_pagination_headers($total, $per_page, $page) {
		$max_pages = ceil($total / $per_page);

		return array(
			'X-WP-Total' => $total,
			'X-WP-TotalPages' => $max_pages
		);
	}

	/**
	 * Get sanitized pagination parameters.
	 */
	protected function get_pagination_params($request) {
		$per_page = $request->get_param('per_page');
		$page = $request->get_param('page');

		return array(
			'per_page' => min(max(1, (int) $per_page ?: 20), 100),
			'page' => max(1, (int) $page ?: 1)
		);
	}

	/**
	 * Standard error response.
	 */
	protected function error_response($code, $message, $status = 400, $data = array()) {
		return new WP_Error($code, $message, array_merge(array('status' => $status), $data));
	}

	/**
	 * Standard success response.
	 */
	protected function success_response($data, $status = 200) {
		return new WP_REST_Response($data, $status);
	}
}