<?php
/**
 * Flags & Moderation Display
 */

if (!defined('ABSPATH')) {
	exit;
}

// Get open flags
$open_flags = ZAOBank_Flags::get_flags_for_review('open');
$under_review = ZAOBank_Flags::get_flags_for_review('under_review');

?>

<div class="wrap">
	<h1><?php echo esc_html(get_admin_page_title()); ?></h1>

	<p class="description">
		<?php _e('Review flagged content and take appropriate action. Remember: flags reduce exposure immediately; humans determine outcomes.', 'zaobank'); ?>
	</p>

	<div class="zaobank-flags-section">
		<h2><?php _e('Open Flags', 'zaobank'); ?> (<?php echo count($open_flags); ?>)</h2>

		<?php if ($open_flags): ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
				<tr>
					<th><?php _e('Date', 'zaobank'); ?></th>
					<th><?php _e('Type', 'zaobank'); ?></th>
					<th><?php _e('Item ID', 'zaobank'); ?></th>
					<th><?php _e('Reporter', 'zaobank'); ?></th>
					<th><?php _e('Reason', 'zaobank'); ?></th>
					<th><?php _e('Context', 'zaobank'); ?></th>
					<th><?php _e('Actions', 'zaobank'); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ($open_flags as $flag): ?>
					<tr data-flag-id="<?php echo esc_attr($flag['id']); ?>">
						<td><?php echo esc_html(date('M j, Y', strtotime($flag['created_at']))); ?></td>
						<td><?php echo esc_html($flag['flagged_item_type']); ?></td>
						<td><?php echo esc_html($flag['flagged_item_id']); ?></td>
						<td><?php echo esc_html($flag['reporter_name']); ?></td>
						<td><?php echo esc_html($flag['reason_slug']); ?></td>
						<td><?php echo esc_html($flag['context_note']); ?></td>
						<td>
							<button class="button button-primary zaobank-review-flag"
									data-flag-id="<?php echo esc_attr($flag['id']); ?>">
								<?php _e('Review', 'zaobank'); ?>
							</button>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php else: ?>
			<p><?php _e('No open flags at this time.', 'zaobank'); ?></p>
		<?php endif; ?>
	</div>

	<div class="zaobank-flags-section">
		<h2><?php _e('Under Review', 'zaobank'); ?> (<?php echo count($under_review); ?>)</h2>

		<?php if ($under_review): ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
				<tr>
					<th><?php _e('Date', 'zaobank'); ?></th>
					<th><?php _e('Type', 'zaobank'); ?></th>
					<th><?php _e('Item ID', 'zaobank'); ?></th>
					<th><?php _e('Reporter', 'zaobank'); ?></th>
					<th><?php _e('Reason', 'zaobank'); ?></th>
					<th><?php _e('Actions', 'zaobank'); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ($under_review as $flag): ?>
					<tr>
						<td><?php echo esc_html(date('M j, Y', strtotime($flag['created_at']))); ?></td>
						<td><?php echo esc_html($flag['flagged_item_type']); ?></td>
						<td><?php echo esc_html($flag['flagged_item_id']); ?></td>
						<td><?php echo esc_html($flag['reporter_name']); ?></td>
						<td><?php echo esc_html($flag['reason_slug']); ?></td>
						<td>
							<button class="button zaobank-resolve-flag"
									data-flag-id="<?php echo esc_attr($flag['id']); ?>"
									data-action="resolved">
								<?php _e('Resolve', 'zaobank'); ?>
							</button>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php else: ?>
			<p><?php _e('No flags under review.', 'zaobank'); ?></p>
		<?php endif; ?>
	</div>
</div>

<style>
	.zaobank-flags-section {
		background: #fff;
		padding: 20px;
		margin: 20px 0;
		box-shadow: 0 1px 1px rgba(0,0,0,.04);
	}

	.zaobank-flags-section h2 {
		margin-top: 0;
	}
</style>

<script>
	jQuery(document).ready(function($) {
		$('.zaobank-review-flag').on('click', function() {
			const flagId = $(this).data('flag-id');
			// TODO: Implement review modal
			alert('Review modal for flag ' + flagId);
		});

		$('.zaobank-resolve-flag').on('click', function() {
			const flagId = $(this).data('flag-id');
			if (confirm('<?php _e('Mark this flag as resolved?', 'zaobank'); ?>')) {
				// TODO: Implement AJAX resolution
				$.ajax({
					url: '<?php echo rest_url('zaobank/v1/flags/'); ?>' + flagId,
					method: 'PUT',
					data: { status: 'resolved' },
					beforeSend: function(xhr) {
						xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
					},
					success: function() {
						location.reload();
					}
				});
			}
		});
	});
</script>