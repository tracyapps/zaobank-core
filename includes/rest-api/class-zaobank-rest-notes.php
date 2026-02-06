<?php
/**
 * REST API: Private Notes endpoints.
 */
class ZAOBank_REST_Notes extends ZAOBank_REST_Controller {

	public function register_routes() {
		register_rest_route($this->namespace, '/me/notes', array(
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_notes'),
				'permission_callback' => array($this, 'check_member_access'),
				'args' => array(
					'subject_user_id' => array(
						'type' => 'integer',
						'description' => __('Filter by subject user ID.', 'zaobank')
					)
				)
			),
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array($this, 'create_note'),
				'permission_callback' => array($this, 'check_member_access'),
				'args' => array(
					'subject_user_id' => array(
						'required' => true,
						'type' => 'integer',
						'description' => __('User the note is about.', 'zaobank')
					),
					'tag_slug' => array(
						'required' => true,
						'type' => 'string',
						'description' => __('Note tag.', 'zaobank')
					),
					'note' => array(
						'type' => 'string',
						'description' => __('Note text.', 'zaobank')
					)
				)
			)
		));

		register_rest_route($this->namespace, '/me/notes/(?P<id>[\d]+)', array(
			'methods' => WP_REST_Server::DELETABLE,
			'callback' => array($this, 'delete_note'),
			'permission_callback' => array($this, 'check_member_access'),
			'args' => array(
				'id' => array(
					'validate_callback' => function($param) {
						return is_numeric($param);
					}
				)
			)
		));
	}

	public function get_notes($request) {
		$user_id = get_current_user_id();
		$subject_user_id = $request->get_param('subject_user_id');

		$notes = ZAOBank_Private_Notes::get_user_notes(
			$user_id,
			$subject_user_id ? (int) $subject_user_id : null
		);

		return $this->success_response(array(
			'notes' => $notes
		));
	}

	public function create_note($request) {
		$user_id = get_current_user_id();

		$note_id = ZAOBank_Private_Notes::create_note(array(
			'author_user_id' => $user_id,
			'subject_user_id' => (int) $request->get_param('subject_user_id'),
			'tag_slug' => $request->get_param('tag_slug'),
			'note' => $request->get_param('note') ?: ''
		));

		if (is_wp_error($note_id)) {
			return $this->error_response(
				$note_id->get_error_code(),
				$note_id->get_error_message()
			);
		}

		return $this->success_response(array(
			'message' => __('Note created successfully', 'zaobank'),
			'id' => $note_id
		), 201);
	}

	public function delete_note($request) {
		$note_id = (int) $request['id'];
		$user_id = get_current_user_id();

		$result = ZAOBank_Private_Notes::delete_note($note_id, $user_id);

		if (is_wp_error($result)) {
			return $this->error_response(
				$result->get_error_code(),
				$result->get_error_message()
			);
		}

		return $this->success_response(array(
			'message' => __('Note deleted successfully', 'zaobank')
		));
	}
}
