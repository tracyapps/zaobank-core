<?php
/**
 * Admin Dashboard Display
 */

if (!defined('ABSPATH')) {
	exit;
}

// Get statistics
global $wpdb;
// set defaults so no errors appear when no content available
$total_exchanges = 0;
$total_hours = 0;
$total_jobs = 0;

$exchanges_table = ZAOBank_Database::get_exchanges_table();

$total_exchanges = $wpdb->get_var("SELECT COUNT(*) FROM $exchanges_table");
$total_hours = $wpdb->get_var("SELECT SUM(hours) FROM $exchanges_table");

$total_jobs = wp_count_posts('timebank_job');
$total_users = count_users();

?>

<div class="wrap">
	<h1><?php echo esc_html(get_admin_page_title()); ?></h1>

	<div class="zaobank-dashboard">
		<div class="zaobank-stats-grid">
			<div class="zaobank-stat-card">
				<h3><?php _e('Total Exchanges', 'zaobank'); ?></h3>
				<p class="stat-number"><?php echo number_format($total_exchanges); ?></p>
			</div>

			<div class="zaobank-stat-card">
				<h3><?php _e('Total Hours Exchanged', 'zaobank'); ?></h3>
				<p class="stat-number"><?php echo number_format($total_hours, 1); ?></p>
			</div>

			<div class="zaobank-stat-card">
				<h3><?php _e('Total Jobs', 'zaobank'); ?></h3>
				<p class="stat-number"><?php echo number_format($total_jobs->publish); ?></p>
			</div>

			<div class="zaobank-stat-card">
				<h3><?php _e('Active Users', 'zaobank'); ?></h3>
				<p class="stat-number"><?php echo number_format($total_users['total_users']); ?></p>
			</div>
		</div>

		<div class="zaobank-recent-activity">
			<h2><?php _e('Recent Exchanges', 'zaobank'); ?></h2>

			<?php
			$recent_exchanges = $wpdb->get_results(
				"SELECT * FROM $exchanges_table ORDER BY created_at DESC LIMIT 10"
			);

			if ($recent_exchanges) {
				echo '<table class="wp-list-table widefat fixed striped">';
				echo '<thead><tr>';
				echo '<th>' . __('Date', 'zaobank') . '</th>';
				echo '<th>' . __('Job', 'zaobank') . '</th>';
				echo '<th>' . __('Provider', 'zaobank') . '</th>';
				echo '<th>' . __('Requester', 'zaobank') . '</th>';
				echo '<th>' . __('Hours', 'zaobank') . '</th>';
				echo '</tr></thead><tbody>';

				foreach ($recent_exchanges as $exchange) {
					$job = get_post($exchange->job_id);
					echo '<tr>';
					echo '<td>' . esc_html(date('M j, Y', strtotime($exchange->created_at))) . '</td>';
					echo '<td>' . ($job ? esc_html($job->post_title) : 'N/A') . '</td>';
					echo '<td>' . esc_html(get_the_author_meta('display_name', $exchange->provider_user_id)) . '</td>';
					echo '<td>' . esc_html(get_the_author_meta('display_name', $exchange->requester_user_id)) . '</td>';
					echo '<td>' . esc_html($exchange->hours) . '</td>';
					echo '</tr>';
				}

				echo '</tbody></table>';
			} else {
				echo '<p>' . __('No exchanges yet.', 'zaobank') . '</p>';
			}
			?>
		</div>
	</div>
</div>

<style>
	.zaobank-stats-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
		gap: 20px;
		margin: 20px 0;
	}

	.zaobank-stat-card {
		background: #fff;
		padding: 20px;
		border-left: 4px solid #2271b1;
		box-shadow: 0 1px 1px rgba(0,0,0,.04);
	}

	.zaobank-stat-card h3 {
		margin: 0 0 10px 0;
		font-size: 14px;
		color: #666;
	}

	.stat-number {
		font-size: 32px;
		font-weight: bold;
		margin: 0;
		color: #2271b1;
	}

	.zaobank-recent-activity {
		background: #fff;
		padding: 20px;
		margin-top: 20px;
		box-shadow: 0 1px 1px rgba(0,0,0,.04);
	}
</style>