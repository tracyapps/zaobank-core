<?php
/**
 * Admin-specific functionality.
 */
class ZAOBank_Admin {

	private $plugin_name;
	private $version;

	public function __construct($plugin_name, $version) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function enqueue_styles() {
		wp_enqueue_style(
			$this->plugin_name,
			ZAOBANK_PLUGIN_URL . 'assets/css/zaobank-admin.css',
			array(),
			$this->version,
			'all'
		);
	}

	public function enqueue_scripts() {
		wp_enqueue_script(
			$this->plugin_name,
			ZAOBANK_PLUGIN_URL . 'assets/js/zaobank-admin.js',
			array('jquery'),
			$this->version,
			false
		);

		wp_localize_script(
			$this->plugin_name,
			'zaobankAdmin',
			array(
				'ajaxurl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('zaobank-admin'),
				'restUrl' => rest_url('zaobank/v1/'),
				'restNonce' => wp_create_nonce('wp_rest')
			)
		);
	}

	public function add_admin_menu() {
		add_menu_page(
			__('ZAO Bank', 'zaobank'),
			__('ZAO Bank', 'zaobank'),
			'manage_options',
			'zaobank',
			array($this, 'display_admin_page'),
			'dashicons-clock',
			25
		);

		add_submenu_page(
			'zaobank',
			__('Dashboard', 'zaobank'),
			__('Dashboard', 'zaobank'),
			'manage_options',
			'zaobank',
			array($this, 'display_admin_page')
		);

		add_submenu_page(
			'zaobank',
			__('Flags & Moderation', 'zaobank'),
			__('Flags & Moderation', 'zaobank'),
			'review_zaobank_flags',
			'zaobank-flags',
			array($this, 'display_flags_page')
		);

		add_submenu_page(
			'zaobank',
			__('Settings', 'zaobank'),
			__('Settings', 'zaobank'),
			'manage_options',
			'zaobank-settings',
			array($this, 'display_settings_page')
		);
	}

	public function display_admin_page() {
		include ZAOBANK_PLUGIN_DIR . 'admin/partials/zaobank-admin-display.php';
	}

	public function display_flags_page() {
		include ZAOBANK_PLUGIN_DIR . 'admin/partials/zaobank-flags-display.php';
	}

	public function display_settings_page() {
		include ZAOBANK_PLUGIN_DIR . 'admin/partials/zaobank-settings-display.php';
	}
}