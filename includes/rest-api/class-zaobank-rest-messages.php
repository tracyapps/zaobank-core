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
					'description' => __('Filter by message type (direct, job_update, job_request, job_offer).', 'zaobank')
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

		register_rest_route($this->namespace, '/messages/(?P<id>[\d]+)/convert-intent', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array($this, 'convert_message_to_intent'),
			'permission_callback' => array($this, 'check_member_access'),
			'args' => array(
				'intent' => array(
					'required' => true,
					'type' => 'string',
					'enum' => array('request', 'offer')
				),
				'title' => array(
					'type' => 'string'
				),
				'hours' => array(
					'type' => 'number'
				),
				'details' => array(
					'type' => 'string'
				)
			)
		));

		register_rest_route($this->namespace, '/messages/(?P<id>[\d]+)/accept-intent', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array($this, 'accept_intent_message'),
			'permission_callback' => array($this, 'check_member_access'),
			'args' => array(
				'hours' => array(
					'type' => 'number'
				),
				'title' => array(
					'type' => 'string'
				),
				'details' => array(
					'type' => 'string'
				)
			)
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
		$message_type = sanitize_key((string) $request->get_param('message_type'));
		if (!in_array($message_type, array('direct', 'job_update', 'job_request', 'job_offer', 'mod_alert'), true)) {
			$message_type = 'direct';
		}

		$message = $request->get_param('message');
		if (($message_type === 'job_request' || $message_type === 'job_offer') && empty($message)) {
			$intent = $message_type === 'job_offer' ? 'offer' : 'request';
			$hours = $this->normalize_hours($request->get_param('hours'), true);
			if (is_wp_error($hours)) {
				return $this->error_response($hours->get_error_code(), $hours->get_error_message(), 400);
			}

			$title = sanitize_text_field((string) $request->get_param('title'));
			$details = trim((string) $request->get_param('details'));
			if ($title === '') {
				$title = $this->derive_intent_title($details, $intent);
			}
			$message = $this->build_intent_message($intent, $title, $hours, $details);
		}

		$data = array(
			'from_user_id' => get_current_user_id(),
			'to_user_id' => $request->get_param('to_user_id'),
			'message' => $message,
			'message_type' => $message_type,
		);

		$exchange_id = $request->get_param('exchange_id');
		if ($exchange_id) {
			$data['exchange_id'] = $exchange_id;
		}

		$job_id = $request->get_param('job_id');
		if ($job_id) {
			$data['job_id'] = (int) $job_id;
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
			'id' => $message_id,
			'message_data' => ZAOBank_Messages::get_message($message_id)
		), 201);
	}

	public function convert_message_to_intent($request) {
		$message_id = (int) $request['id'];
		$user_id = get_current_user_id();
		$intent = sanitize_key((string) $request->get_param('intent'));

		if (!in_array($intent, array('request', 'offer'), true)) {
			return $this->error_response(
				'invalid_intent',
				__('Intent must be request or offer.', 'zaobank'),
				400
			);
		}

		$message_row = ZAOBank_Messages::get_message_row($message_id);
		if (!$message_row) {
			return $this->error_response(
				'message_not_found',
				__('Message not found.', 'zaobank'),
				404
			);
		}

		if ((int) $message_row->from_user_id !== (int) $user_id) {
			return $this->error_response(
				'rest_forbidden',
				__('You can only convert messages you sent.', 'zaobank'),
				403
			);
		}

		if (!empty($message_row->job_id)) {
			return $this->error_response(
				'message_already_linked',
				__('This message is already linked to a job.', 'zaobank'),
				400
			);
		}

		$hours = $this->normalize_hours($request->get_param('hours'), true);
		if (is_wp_error($hours)) {
			return $this->error_response($hours->get_error_code(), $hours->get_error_message(), 400);
		}

		$details = trim((string) $request->get_param('details'));
		if ($details === '') {
			$details = trim(wp_strip_all_tags((string) $message_row->message));
		}

		$title = sanitize_text_field((string) $request->get_param('title'));
		if ($title === '') {
			$title = $this->derive_intent_title($details, $intent);
		}

		$updated = ZAOBank_Messages::update_message(
			$message_id,
			array(
				'message' => $this->build_intent_message($intent, $title, $hours, $details),
				'message_type' => 'job_' . $intent,
				'job_id' => null
			),
			array('%s', '%s', '%d')
		);

		if (!$updated) {
			return $this->error_response(
				'message_update_failed',
				__('Failed to convert message.', 'zaobank')
			);
		}

		return $this->success_response(array(
			'message' => __('Message converted successfully.', 'zaobank'),
			'message_data' => ZAOBank_Messages::get_message($message_id)
		));
	}

	public function accept_intent_message($request) {
		$message_id = (int) $request['id'];
		$user_id = get_current_user_id();
		$message_row = ZAOBank_Messages::get_message_row($message_id);

		if (!$message_row) {
			return $this->error_response(
				'message_not_found',
				__('Message not found.', 'zaobank'),
				404
			);
		}

		if ((int) $message_row->to_user_id !== (int) $user_id) {
			return $this->error_response(
				'rest_forbidden',
				__('Only the recipient can accept this request.', 'zaobank'),
				403
			);
		}

		if (!empty($message_row->job_id)) {
			$existing_job = ZAOBank_Jobs::format_job_data((int) $message_row->job_id);
			return $this->success_response(array(
				'message' => __('This request has already been accepted.', 'zaobank'),
				'job' => $existing_job
			));
		}

		$intent_data = $this->parse_intent_message($message_row);
		if (is_wp_error($intent_data)) {
			return $this->error_response(
				$intent_data->get_error_code(),
				$intent_data->get_error_message(),
				400
			);
		}

		$title = sanitize_text_field((string) $request->get_param('title'));
		if ($title === '') {
			$title = $intent_data['title'];
		}

		$details = trim((string) $request->get_param('details'));
		if ($details === '') {
			$details = $intent_data['details'];
		}

		$hours = $this->normalize_hours($request->get_param('hours'), false);
		if ($hours === null) {
			$hours = $intent_data['hours'];
		}
		if (is_wp_error($hours)) {
			return $this->error_response($hours->get_error_code(), $hours->get_error_message(), 400);
		}
		if ($hours === null) {
			return $this->error_response(
				'missing_hours',
				__('Please provide estimated hours before accepting.', 'zaobank'),
				400
			);
		}

		$sender_id = (int) $message_row->from_user_id;
		if ($intent_data['intent'] === 'request') {
			$requester_id = $sender_id;
			$provider_id = $user_id;
		} else {
			$requester_id = $user_id;
			$provider_id = $sender_id;
		}

		if ($requester_id === $provider_id) {
			return $this->error_response(
				'invalid_participants',
				__('Requester and provider cannot be the same user.', 'zaobank'),
				400
			);
		}

		$job_id = ZAOBank_Jobs::create_job(array(
			'title' => $title,
			'description' => $details,
			'hours' => $hours
		), $requester_id);

		if (is_wp_error($job_id)) {
			return $this->error_response(
				$job_id->get_error_code(),
				$job_id->get_error_message()
			);
		}

		$claim_result = ZAOBank_Jobs::claim_job((int) $job_id, $provider_id);
		if (is_wp_error($claim_result)) {
			wp_trash_post((int) $job_id);
			return $this->error_response(
				$claim_result->get_error_code(),
				$claim_result->get_error_message()
			);
		}

		ZAOBank_Messages::update_message(
			$message_id,
			array(
				'job_id' => (int) $job_id,
				'message_type' => 'job_' . $intent_data['intent']
			),
			array('%d', '%s')
		);

		if ($intent_data['intent'] === 'offer') {
			ZAOBank_Messages::create_message(array(
				'from_user_id' => $user_id,
				'to_user_id' => $sender_id,
				'message' => sprintf(
					__("%s accepted your offer '%s'.", 'zaobank'),
					get_the_author_meta('display_name', $user_id),
					$title
				),
				'message_type' => 'job_update',
				'job_id' => (int) $job_id
			));
		}

		return $this->success_response(array(
			'message' => __('Request accepted and job created.', 'zaobank'),
			'job' => ZAOBank_Jobs::format_job_data((int) $job_id),
			'message_data' => ZAOBank_Messages::get_message($message_id)
		));
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

	private function normalize_hours($raw_hours, $required = false) {
		if ($raw_hours === null || $raw_hours === '') {
			return $required ? new WP_Error('missing_hours', __('Estimated hours are required.', 'zaobank')) : null;
		}

		$hours = floatval($raw_hours);
		$hours_check = ZAOBank_Security::validate_hours($hours);
		if (is_wp_error($hours_check)) {
			return $hours_check;
		}

		return $hours;
	}

	private function derive_intent_title($details, $intent) {
		$clean = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags((string) $details)));
		if ($clean !== '') {
			return sanitize_text_field(wp_html_excerpt($clean, 80, '...'));
		}

		return $intent === 'offer'
			? __('Help offer', 'zaobank')
			: __('Community request', 'zaobank');
	}

	private function build_intent_message($intent, $title, $hours, $details) {
		$label = $intent === 'offer'
			? __('Job offer', 'zaobank')
			: __('Job request', 'zaobank');
		$hours_line = sprintf(__('Estimated hours: %s', 'zaobank'), rtrim(rtrim((string) $hours, '0'), '.'));
		$body = trim((string) $details);

		return $label . ': ' . sanitize_text_field($title) . "\n" . $hours_line . "\n\n" . $body;
	}

	private function parse_intent_message($message_row) {
		$message_type = sanitize_key((string) $message_row->message_type);
		$text = trim((string) $message_row->message);
		$intent = '';

		if ($message_type === 'job_request') {
			$intent = 'request';
		} elseif ($message_type === 'job_offer') {
			$intent = 'offer';
		}

		if ($intent === '') {
			if (preg_match('/^\s*job\s+request\s*:/i', $text)) {
				$intent = 'request';
			} elseif (preg_match('/^\s*job\s+offer\s*:/i', $text)) {
				$intent = 'offer';
			} elseif (preg_match('/^\s*skill\s+request\s*:/i', $text)) {
				$intent = 'request';
			}
		}

		if ($intent === '') {
			return new WP_Error(
				'not_a_request',
				__('This message is not a request or offer.', 'zaobank')
			);
		}

		$title = '';
		if (preg_match('/^\s*(?:job\s+request|job\s+offer|skill\s+request)\s*:\s*(.+)$/im', $text, $matches)) {
			$title = sanitize_text_field(trim($matches[1]));
		}

		$hours = null;
		if (preg_match('/estimated\s+hours\s*:\s*([0-9]+(?:\.[0-9]+)?)/i', $text, $matches)) {
			$hours = floatval($matches[1]);
			$hours_check = ZAOBank_Security::validate_hours($hours);
			if (is_wp_error($hours_check)) {
				$hours = null;
			}
		}

		$details = '';
		$parts = preg_split('/\R\s*\R/', $text, 2);
		if (is_array($parts) && count($parts) > 1) {
			$details = trim($parts[1]);
		} else {
			$details = preg_replace('/^\s*(?:job\s+request|job\s+offer|skill\s+request)\s*:.*$/im', '', $text);
			$details = preg_replace('/^\s*estimated\s+hours\s*:.*$/im', '', $details);
			$details = trim($details);
		}

		if ($title === '') {
			$title = $this->derive_intent_title($details, $intent);
		}

		return array(
			'intent' => $intent,
			'title' => $title,
			'hours' => $hours,
			'details' => $details
		);
	}
}
