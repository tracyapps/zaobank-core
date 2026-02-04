<?php
/**
 * Subpage Tab Navigation Component
 *
 * Usage:
 *   $tabs = array(
 *       array('label' => 'All Jobs', 'url' => '/jobs/', 'current' => true),
 *       array('label' => 'My Jobs',  'url' => '/my-jobs/'),
 *       array('label' => 'Post a Job', 'url' => '/post-job/'),
 *   );
 *   include ZAOBANK_PLUGIN_DIR . 'public/templates/components/subpage-tabs.php';
 *
 * @var array $tabs Array of tab items with 'label', 'url', and optional 'current' keys.
 */

if (!defined('ABSPATH') || empty($tabs)) {
	return;
}
?>
<nav class="zaobank-subpage-tabs">
	<ul role="tablist">
		<?php foreach ($tabs as $tab) :
			$is_current = !empty($tab['current']);
		?>
		<li role="tab" class="subpage-tab<?php echo $is_current ? ' current-tab' : ''; ?>">
			<?php if ($is_current) : ?>
				<span><?php echo esc_html($tab['label']); ?></span>
			<?php else : ?>
				<a href="<?php echo esc_url($tab['url']); ?>"><?php echo esc_html($tab['label']); ?></a>
			<?php endif; ?>
		</li>
		<?php endforeach; ?>
	</ul>
</nav>
