<?php
/**
 * REST API: Messages endpoints.
 */
class ZAOBank_REST_Messages extends ZAOBank_REST_Controller {

	public function register_routes() {
		register_rest_route($this->namespace, '/me/messages', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array($this, 'get_messages'),
			'permission_callback' => array($this, 'check_authentication')
		));

		register_rest_route($this->namespace, '/messages', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array($this, 'create_message'),
			'permission_callback' => array($this, 'check_authentication')
		));

		register_rest_route($this->namespace, '/messages/(?P<id>[\d]+)/read', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array($this, 'mark_as_read'),
			'permission_callback' => array($this, 'check_authentication')
		));
	}

	public function get_messages($request) {
		$user_id = get_current_user_id();
		$with_user = $request->get_param('with_user');

		$messages = ZAOBank_Messages::get_user_messages($user_id, 'all');

		if ($with_user) {
			$with_user = (int) $with_user;
			$messages = array_values(array_filter($messages, function($msg) use ($user_id, $with_user) {
				return ($msg['from_user_id'] === $with_user && $msg['to_user_id'] === $user_id)
					|| ($msg['from_user_id'] === $user_id && $msg['to_user_id'] === $with_user);
			}));
		}

		return $this->success_response(array(
			'messages' => $messages
		));
	}

	public function create_message($request) {
		$data = array(
			'from_user_id' => get_current_user_id(),
			'to_user_id' => $request->get_param('to_user_id'),
			'message' => $request->get_param('message'),
		);

		$exchange_id = $request->get_param('exchange_id');
		if ($exchange_id) {
			$data['exchange_id'] = $exchange_id;
		}

		$message_id = ZAOBank_Messages::create_message($data);

		if (is_wp_error($message_id)) {
			return $this->error_response(
				$message_id->get_error_code(),
				$message_id->get_error_message()
			);
		}

		return $this->success_response(array(
			'message' => __('Message sent successfully', 'zaobank'),
			'id' => $message_id
		), 201);
	}

	public function mark_as_read($request) {
		$message_id = (int) $request['id'];
		$user_id = get_current_user_id();

		$result = ZAOBank_Messages::mark_as_read($message_id, $user_id);

		if (is_wp_error($result)) {
			return $this->error_response(
				$result->get_error_code(),
				$result->get_error_message()
			);
		}

		return $this->success_response(array(
			'message' => __('Message marked as read', 'zaobank')
		));
	}
}
