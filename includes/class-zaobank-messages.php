<?php
/**
 * Message management for 1:1 communication.
 */
class ZAOBank_Messages {

	/**
	 * Create a message.
	 */
	public static function create_message($data) {
		global $wpdb;
		$table = ZAOBank_Database::get_messages_table();

		if (empty($data['from_user_id']) || empty($data['to_user_id']) || empty($data['message'])) {
			return new WP_Error(
				'missing_message_data',
				__('Missing required message data', 'zaobank')
			);
		}

		$result = $wpdb->insert(
			$table,
			array(
				'exchange_id' => isset($data['exchange_id']) ? (int) $data['exchange_id'] : null,
				'from_user_id' => (int) $data['from_user_id'],
				'to_user_id' => (int) $data['to_user_id'],
				'message' => wp_kses_post($data['message']),
				'is_read' => 0,
				'created_at' => wp_date('Y-m-d H:i:s')
			),
			array('%d', '%d', '%d', '%s', '%d', '%s')
		);

		if ($result === false) {
			return new WP_Error(
				'message_creation_failed',
				__('Failed to create message', 'zaobank')
			);
		}

		return $wpdb->insert_id;
	}

	/**
	 * Get messages for a user.
	 */
	public static function get_user_messages($user_id, $type = 'inbox') {
		global $wpdb;
		$table = ZAOBank_Database::get_messages_table();

		if ($type === 'inbox') {
			$where = $wpdb->prepare('to_user_id = %d', $user_id);
		} elseif ($type === 'sent') {
			$where = $wpdb->prepare('from_user_id = %d', $user_id);
		} else {
			$where = $wpdb->prepare('(to_user_id = %d OR from_user_id = %d)', $user_id, $user_id);
		}

		$messages = $wpdb->get_results(
			"SELECT * FROM $table WHERE $where ORDER BY created_at DESC"
		);

		return array_map(array(__CLASS__, 'format_message_data'), $messages);
	}

	/**
	 * Mark message as read.
	 */
	public static function mark_as_read($message_id, $user_id) {
		global $wpdb;
		$table = ZAOBank_Database::get_messages_table();

		// Verify user is the recipient
		$message = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM $table WHERE id = %d AND to_user_id = %d",
			$message_id,
			$user_id
		));

		if (!$message) {
			return new WP_Error('invalid_message', __('Invalid message', 'zaobank'));
		}

		$wpdb->update(
			$table,
			array('is_read' => 1),
			array('id' => $message_id),
			array('%d'),
			array('%d')
		);

		return true;
	}

	/**
	 * Format message data.
	 */
	private static function format_message_data($message) {
		return array(
			'id' => (int) $message->id,
			'exchange_id' => $message->exchange_id ? (int) $message->exchange_id : null,
			'from_user_id' => (int) $message->from_user_id,
			'from_user_name' => get_the_author_meta('display_name', $message->from_user_id),
			'to_user_id' => (int) $message->to_user_id,
			'to_user_name' => get_the_author_meta('display_name', $message->to_user_id),
			'message' => $message->message,
			'is_read' => (bool) $message->is_read,
			'created_at' => $message->created_at
		);
	}
}