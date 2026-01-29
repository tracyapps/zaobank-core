<?php
/**
 * Private notes management - personal memory aids.
 *
 * CRITICAL PRIVACY: These notes are NEVER visible to anyone except the author.
 * They are NEVER aggregated, analyzed, or exposed via any API except to the author.
 */
class ZAOBank_Private_Notes {

	/**
	 * Create a private note.
	 */
	public static function create_note($data) {
		global $wpdb;
		$table = ZAOBank_Database::get_private_notes_table();

		if (empty($data['author_user_id']) || empty($data['subject_user_id']) || empty($data['tag_slug'])) {
			return new WP_Error(
				'missing_note_data',
				__('Missing required note data', 'zaobank')
			);
		}

		// Validate tag
		$valid_tags = get_option('zaobank_private_note_tags', array());
		if (!in_array($data['tag_slug'], $valid_tags)) {
			return new WP_Error(
				'invalid_tag',
				__('Invalid note tag', 'zaobank')
			);
		}

		$result = $wpdb->insert(
			$table,
			array(
				'author_user_id' => (int) $data['author_user_id'],
				'subject_user_id' => (int) $data['subject_user_id'],
				'tag_slug' => sanitize_key($data['tag_slug']),
				'note' => isset($data['note']) ? sanitize_textarea_field($data['note']) : null,
				'created_at' => wp_date('Y-m-d H:i:s')
			),
			array('%d', '%d', '%s', '%s', '%s')
		);

		if ($result === false) {
			return new WP_Error(
				'note_creation_failed',
				__('Failed to create note', 'zaobank')
			);
		}

		return $wpdb->insert_id;
	}

	/**
	 * Get notes authored by a specific user.
	 * CRITICAL: Always scoped to author_user_id.
	 */
	public static function get_user_notes($author_user_id, $subject_user_id = null) {
		global $wpdb;
		$table = ZAOBank_Database::get_private_notes_table();

		// SECURITY: Always scope to author
		if ($subject_user_id) {
			$where = $wpdb->prepare(
				'author_user_id = %d AND subject_user_id = %d',
				$author_user_id,
				$subject_user_id
			);
		} else {
			$where = $wpdb->prepare('author_user_id = %d', $author_user_id);
		}

		$notes = $wpdb->get_results(
			"SELECT * FROM $table WHERE $where ORDER BY created_at DESC"
		);

		return array_map(array(__CLASS__, 'format_note_data'), $notes);
	}

	/**
	 * Update a note.
	 * SECURITY: Verify ownership.
	 */
	public static function update_note($note_id, $author_user_id, $data) {
		global $wpdb;
		$table = ZAOBank_Database::get_private_notes_table();

		// Verify ownership
		$note = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM $table WHERE id = %d AND author_user_id = %d",
			$note_id,
			$author_user_id
		));

		if (!$note) {
			return new WP_Error('invalid_note', __('Invalid note or permission denied', 'zaobank'));
		}

		$update_data = array();

		if (isset($data['tag_slug'])) {
			$update_data['tag_slug'] = sanitize_key($data['tag_slug']);
		}

		if (isset($data['note'])) {
			$update_data['note'] = sanitize_textarea_field($data['note']);
		}

		if (empty($update_data)) {
			return true;
		}

		$wpdb->update(
			$table,
			$update_data,
			array('id' => $note_id),
			array_fill(0, count($update_data), '%s'),
			array('%d')
		);

		return true;
	}

	/**
	 * Delete a note.
	 * SECURITY: Verify ownership.
	 */
	public static function delete_note($note_id, $author_user_id) {
		global $wpdb;
		$table = ZAOBank_Database::get_private_notes_table();

		// Verify ownership
		$note = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM $table WHERE id = %d AND author_user_id = %d",
			$note_id,
			$author_user_id
		));

		if (!$note) {
			return new WP_Error('invalid_note', __('Invalid note or permission denied', 'zaobank'));
		}

		$wpdb->delete(
			$table,
			array('id' => $note_id),
			array('%d')
		);

		return true;
	}

	/**
	 * Format note data.
	 */
	private static function format_note_data($note) {
		return array(
			'id' => (int) $note->id,
			'author_user_id' => (int) $note->author_user_id,
			'subject_user_id' => (int) $note->subject_user_id,
			'subject_user_name' => get_the_author_meta('display_name', $note->subject_user_id),
			'tag_slug' => $note->tag_slug,
			'note' => $note->note,
			'created_at' => $note->created_at
		);
	}
}