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

		// Allow from_user_id = 0 for system messages (mod_alert)
		$is_system_message = isset($data['message_type']) && $data['message_type'] === 'mod_alert';
		if ((!$is_system_message && empty($data['from_user_id'])) || empty($data['to_user_id']) || empty($data['message'])) {
			return new WP_Error(
				'missing_message_data',
				__('Missing required message data', 'zaobank')
			);
		}

		$message_type = isset($data['message_type']) ? sanitize_key($data['message_type']) : 'direct';
		$job_id = isset($data['job_id']) ? (int) $data['job_id'] : null;

		$insert_data = array(
			'exchange_id' => isset($data['exchange_id']) ? (int) $data['exchange_id'] : null,
			'from_user_id' => (int) $data['from_user_id'],
			'to_user_id' => (int) $data['to_user_id'],
			'message' => wp_kses_post($data['message']),
			'is_read' => 0,
			'message_type' => $message_type,
			'job_id' => $job_id,
			'created_at' => wp_date('Y-m-d H:i:s')
		);

		$formats = array('%d', '%d', '%d', '%s', '%d', '%s', '%d', '%s');

		$result = $wpdb->insert($table, $insert_data, $formats);

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
	public static function get_user_messages($user_id, $type = 'inbox', $args = array()) {
		global $wpdb;
		$table = ZAOBank_Database::get_messages_table();

		if ($type === 'inbox') {
			$where = $wpdb->prepare('to_user_id = %d', $user_id);
		} elseif ($type === 'sent') {
			$where = $wpdb->prepare('from_user_id = %d', $user_id);
		} else {
			$where = $wpdb->prepare('(to_user_id = %d OR from_user_id = %d)', $user_id, $user_id);
		}

		// Filter by message_type
		if (!empty($args['message_type'])) {
			$where .= $wpdb->prepare(' AND message_type = %s', $args['message_type']);
		}

		// Hide moderator-removed messages from both participants.
		if (class_exists('ZAOBank_Flags')) {
			$hidden_ids = ZAOBank_Flags::get_hidden_message_ids();
			if (!empty($hidden_ids)) {
				$placeholders = implode(',', array_fill(0, count($hidden_ids), '%d'));
				$where .= $wpdb->prepare(
					" AND id NOT IN ($placeholders)",
					...$hidden_ids
				);
			}
		}

		// Exclude archived conversations
		if (empty($args['include_archived'])) {
			$archived_ids = self::get_archived_user_ids($user_id);
			if (!empty($archived_ids)) {
				$placeholders = implode(',', array_fill(0, count($archived_ids), '%d'));
				$where .= $wpdb->prepare(
					" AND NOT (
						(from_user_id IN ($placeholders) AND to_user_id = %d)
						OR (to_user_id IN ($placeholders) AND from_user_id = %d)
					)",
					...array_merge($archived_ids, array($user_id), $archived_ids, array($user_id))
				);
			}
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
	 * Mark all messages in a conversation as read.
	 */
	public static function mark_conversation_read($user_id, $other_user_id) {
		global $wpdb;
		$table = ZAOBank_Database::get_messages_table();

		$wpdb->query($wpdb->prepare(
			"UPDATE $table SET is_read = 1
			 WHERE to_user_id = %d AND from_user_id = %d AND is_read = 0",
			$user_id,
			$other_user_id
		));

		return true;
	}

	/**
	 * Mark all messages of a type as read for a user.
	 *
	 * @param int    $user_id      Recipient user ID.
	 * @param string $message_type Message type to mark (e.g., job_update).
	 * @return int Number of rows updated.
	 */
	public static function mark_type_read($user_id, $message_type) {
		global $wpdb;
		$table = ZAOBank_Database::get_messages_table();

		$updated = $wpdb->query($wpdb->prepare(
			"UPDATE $table SET is_read = 1
			 WHERE to_user_id = %d AND message_type = %s AND is_read = 0",
			$user_id,
			$message_type
		));

		return (int) $updated;
	}

	/**
	 * Archive a conversation.
	 */
	public static function archive_conversation($user_id, $other_user_id) {
		global $wpdb;
		$table = ZAOBank_Database::get_archived_conversations_table();

		$result = $wpdb->replace(
			$table,
			array(
				'user_id' => (int) $user_id,
				'other_user_id' => (int) $other_user_id,
				'archived_at' => wp_date('Y-m-d H:i:s')
			),
			array('%d', '%d', '%s')
		);

		return $result !== false;
	}

	/**
	 * Get archived user IDs for a user.
	 */
	public static function get_archived_user_ids($user_id) {
		global $wpdb;
		$table = ZAOBank_Database::get_archived_conversations_table();

		// Check if table exists (pre-migration)
		$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
		if (!$table_exists) {
			return array();
		}

		return $wpdb->get_col($wpdb->prepare(
			"SELECT other_user_id FROM $table WHERE user_id = %d",
			$user_id
		));
	}

	/**
	 * Get job update messages for a user.
	 */
	public static function get_job_update_messages($user_id) {
		return self::get_user_messages($user_id, 'all', array('message_type' => 'job_update'));
	}

	/**
	 * Format message data.
	 */
	private static function format_message_data($message) {
		$data = array(
			'id' => (int) $message->id,
			'exchange_id' => $message->exchange_id ? (int) $message->exchange_id : null,
			'from_user_id' => (int) $message->from_user_id,
			'from_user_name' => get_the_author_meta('display_name', $message->from_user_id),
			'from_user_pronouns' => get_user_meta($message->from_user_id, 'user_pronouns', true),
			'from_user_avatar' => ZAOBank_Helpers::get_user_avatar_url($message->from_user_id, 40),
			'to_user_id' => (int) $message->to_user_id,
			'to_user_name' => get_the_author_meta('display_name', $message->to_user_id),
			'to_user_pronouns' => get_user_meta($message->to_user_id, 'user_pronouns', true),
			'to_user_avatar' => ZAOBank_Helpers::get_user_avatar_url($message->to_user_id, 40),
			'message' => $message->message,
			'is_read' => (bool) $message->is_read,
			'created_at' => $message->created_at
		);

		if (isset($message->message_type)) {
			$data['message_type'] = $message->message_type;
		}
		if (isset($message->job_id) && $message->job_id) {
			$data['job_id'] = (int) $message->job_id;
			$job = get_post($message->job_id);
			$data['job_title'] = $job ? $job->post_title : '';
		}

		return $data;
	}
}
