<?php
/**
 * REST API: Messages endpoints.
 */
class ZAOBank_REST_Messages extends ZAOBank_REST_Controller {

	public function register_routes() {
		register_rest_route($this->namespace, '/me/messages', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array($this, 'get_messages'),
			'permission_callback' => array($this, 'check_member_access'),
			'args' => array(
				'with_user' => array(
					'type' => 'integer',
					'description' => __('Filter to conversation with specific user.', 'zaobank')
				),
				'message_type' => array(
					'type' => 'string',
					'description' => __('Filter by message type (direct, job_update).', 'zaobank')
				)
			)
		));

		register_rest_route($this->namespace, '/messages', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array($this, 'create_message'),
			'permission_callback' => array($this, 'check_member_access')
		));

		register_rest_route($this->namespace, '/messages/(?P<id>[\d]+)/read', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array($this, 'mark_as_read'),
			'permission_callback' => array($this, 'check_member_access')
		));

		// Mark all messages from a user as read
		register_rest_route($this->namespace, '/me/messages/read-all', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array($this, 'mark_conversation_read'),
			'permission_callback' => array($this, 'check_member_access'),
			'args' => array(
				'with_user' => array(
					'required' => true,
					'type' => 'integer',
					'description' => __('Other user ID in the conversation.', 'zaobank')
				)
			)
		));

		// Mark all messages of a type as read
		register_rest_route($this->namespace, '/me/messages/read-type', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array($this, 'mark_type_read'),
			'permission_callback' => array($this, 'check_member_access'),
			'args' => array(
				'message_type' => array(
					'required' => true,
					'type' => 'string',
					'description' => __('Message type to mark as read.', 'zaobank')
				)
			)
		));

		// Archive a conversation
		register_rest_route($this->namespace, '/me/messages/archive', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array($this, 'archive_conversation'),
			'permission_callback' => array($this, 'check_member_access'),
			'args' => array(
				'other_user_id' => array(
					'required' => true,
					'type' => 'integer',
					'description' => __('Other user ID to archive conversation with.', 'zaobank')
				)
			)
		));
	}

	public function get_messages($request) {
		$user_id = get_current_user_id();
		$with_user = $request->get_param('with_user');
		$message_type = $request->get_param('message_type');

		$args = array();
		if ($message_type) {
			$args['message_type'] = sanitize_key($message_type);
		}

		$messages = ZAOBank_Messages::get_user_messages($user_id, 'all', $args);

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

	public function mark_conversation_read($request) {
		$user_id = get_current_user_id();
		$with_user = (int) $request->get_param('with_user');

		ZAOBank_Messages::mark_conversation_read($user_id, $with_user);

		return $this->success_response(array(
			'message' => __('Conversation marked as read', 'zaobank')
		));
	}

	public function mark_type_read($request) {
		$user_id = get_current_user_id();
		$message_type = sanitize_key($request->get_param('message_type'));

		if (empty($message_type)) {
			return $this->error_response(
				'invalid_message_type',
				__('Invalid message type.', 'zaobank'),
				400
			);
		}

		$count = ZAOBank_Messages::mark_type_read($user_id, $message_type);

		return $this->success_response(array(
			'message' => __('Messages marked as read', 'zaobank'),
			'count' => $count
		));
	}

	public function archive_conversation($request) {
		$user_id = get_current_user_id();
		$other_user_id = (int) $request->get_param('other_user_id');

		$result = ZAOBank_Messages::archive_conversation($user_id, $other_user_id);

		if (!$result) {
			return $this->error_response(
				'archive_failed',
				__('Failed to archive conversation', 'zaobank')
			);
		}

		return $this->success_response(array(
			'message' => __('Conversation archived', 'zaobank')
		));
	}
}
