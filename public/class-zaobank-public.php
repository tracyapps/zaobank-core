<?php
/**
 * Public-facing functionality.
 */
class ZAOBank_Public {

	private $plugin_name;
	private $version;

	public function __construct($plugin_name, $version) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function enqueue_styles() {
		wp_enqueue_style(
			$this->plugin_name,
			ZAOBANK_PLUGIN_URL . 'assets/css/zaobank-public.css',
			array(),
			$this->version,
			'all'
		);
	}

	public function enqueue_scripts() {
		wp_enqueue_script(
			$this->plugin_name,
			ZAOBANK_PLUGIN_URL . 'assets/js/zaobank-public.js',
			array('jquery'),
			$this->version,
			false
		);

		// Enqueue media scripts for profile edit page (image upload)
		if (is_user_logged_in()) {
			wp_enqueue_media();
		}

		wp_localize_script(
			$this->plugin_name,
			'zaobank',
			array(
				'restUrl' => rest_url('zaobank/v1/'),
				'restNonce' => wp_create_nonce('wp_rest'),
				'userId' => get_current_user_id(),
				'isLoggedIn' => is_user_logged_in(),
				'hasMemberAccess' => ZAOBank_Security::user_has_member_access(),
				'appreciationTags' => array_values(get_option('zaobank_appreciation_tags', array())),
				'privateNoteTags' => array_values(get_option('zaobank_private_note_tags', array()))
			)
		);
	}
}
