<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 */
class ZAOBank_Core {

	/**
	 * The loader that's responsible for maintaining and registering all hooks.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 */
	public function __construct() {
		$this->version = ZAOBANK_VERSION;
		$this->plugin_name = 'zaobank';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_rest_api_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 */
	private function load_dependencies() {
		// Core loader
		require_once ZAOBANK_PLUGIN_DIR . 'includes/class-zaobank-loader.php';

		// Internationalization
		require_once ZAOBANK_PLUGIN_DIR . 'includes/class-zaobank-i18n.php';

		// Database management
		require_once ZAOBANK_PLUGIN_DIR . 'includes/class-zaobank-database.php';

		// Custom post types
		require_once ZAOBANK_PLUGIN_DIR . 'includes/class-zaobank-post-types.php';

		// Taxonomies
		require_once ZAOBANK_PLUGIN_DIR . 'includes/class-zaobank-taxonomies.php';

		// ACF integration
		require_once ZAOBANK_PLUGIN_DIR . 'includes/class-zaobank-acf.php';

		// Security & permissions
		require_once ZAOBANK_PLUGIN_DIR . 'includes/class-zaobank-security.php';

		// Business logic
		require_once ZAOBANK_PLUGIN_DIR . 'includes/class-zaobank-jobs.php';
		require_once ZAOBANK_PLUGIN_DIR . 'includes/class-zaobank-exchanges.php';
		require_once ZAOBANK_PLUGIN_DIR . 'includes/class-zaobank-appreciations.php';
		require_once ZAOBANK_PLUGIN_DIR . 'includes/class-zaobank-flags.php';
		require_once ZAOBANK_PLUGIN_DIR . 'includes/class-zaobank-messages.php';
		require_once ZAOBANK_PLUGIN_DIR . 'includes/class-zaobank-private-notes.php';

		// REST API
		require_once ZAOBANK_PLUGIN_DIR . 'includes/rest-api/class-zaobank-rest-controller.php';
		require_once ZAOBANK_PLUGIN_DIR . 'includes/rest-api/class-zaobank-rest-jobs.php';
		require_once ZAOBANK_PLUGIN_DIR . 'includes/rest-api/class-zaobank-rest-user.php';
		require_once ZAOBANK_PLUGIN_DIR . 'includes/rest-api/class-zaobank-rest-regions.php';
		require_once ZAOBANK_PLUGIN_DIR . 'includes/rest-api/class-zaobank-rest-appreciations.php';
		require_once ZAOBANK_PLUGIN_DIR . 'includes/rest-api/class-zaobank-rest-flags.php';
		require_once ZAOBANK_PLUGIN_DIR . 'includes/rest-api/class-zaobank-rest-messages.php';
		require_once ZAOBANK_PLUGIN_DIR . 'includes/rest-api/class-zaobank-rest-notes.php';
		require_once ZAOBANK_PLUGIN_DIR . 'includes/rest-api/class-zaobank-rest-moderation.php';

		// Admin
		require_once ZAOBANK_PLUGIN_DIR . 'admin/class-zaobank-admin.php';

		// Public
		require_once ZAOBANK_PLUGIN_DIR . 'public/class-zaobank-public.php';

		// Utilities
		require_once ZAOBANK_PLUGIN_DIR . 'includes/class-zaobank-helpers.php';

		// Shortcodes
		require_once ZAOBANK_PLUGIN_DIR . 'includes/class-zaobank-shortcodes.php';

		$this->loader = new ZAOBank_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 */
	private function set_locale() {
		$plugin_i18n = new ZAOBank_i18n();
		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the admin area functionality.
	 */
	private function define_admin_hooks() {
		$plugin_admin = new ZAOBank_Admin($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
		$this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');

		// Register custom post types and taxonomies
		$post_types = new ZAOBank_Post_Types();
		$this->loader->add_action('init', $post_types, 'register_post_types');

		$taxonomies = new ZAOBank_Taxonomies();
		$this->loader->add_action('init', $taxonomies, 'register_taxonomies');

		// ACF field groups
		$acf = new ZAOBank_ACF();
		$this->loader->add_action('acf/init', $acf, 'register_field_groups');

		// Database tables
		$database = new ZAOBank_Database();
		$this->loader->add_action('init', $database, 'check_database_version', 5);
	}

	/**
	 * Register all of the hooks related to the public-facing functionality.
	 */
	private function define_public_hooks() {
		$plugin_public = new ZAOBank_Public($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

		// Register shortcodes
		$shortcodes = new ZAOBank_Shortcodes();
		$this->loader->add_action('init', $shortcodes, 'register_shortcodes');

		// Notify moderators when a new user registers
		$this->loader->add_action('user_register', $this, 'notify_mod_on_registration');
	}

	/**
	 * Send a mod_alert when a new user registers.
	 */
	public function notify_mod_on_registration($user_id) {
		$user = get_userdata($user_id);
		if (!$user) {
			return;
		}

		ZAOBank_Flags::send_mod_alert(
			sprintf(
				__('New user registered: %s (%s). Please verify their account.', 'zaobank'),
				$user->display_name,
				$user->user_email
			),
			$user_id
		);
	}

	/**
	 * Register all REST API endpoints.
	 */
	private function define_rest_api_hooks() {
		$rest_jobs = new ZAOBank_REST_Jobs();
		$rest_user = new ZAOBank_REST_User();
		$rest_regions = new ZAOBank_REST_Regions();
		$rest_appreciations = new ZAOBank_REST_Appreciations();
		$rest_flags = new ZAOBank_REST_Flags();
		$rest_messages = new ZAOBank_REST_Messages();
		$rest_notes = new ZAOBank_REST_Notes();
		$rest_moderation = new ZAOBank_REST_Moderation();

		$this->loader->add_action('rest_api_init', $rest_jobs, 'register_routes');
		$this->loader->add_action('rest_api_init', $rest_user, 'register_routes');
		$this->loader->add_action('rest_api_init', $rest_regions, 'register_routes');
		$this->loader->add_action('rest_api_init', $rest_appreciations, 'register_routes');
		$this->loader->add_action('rest_api_init', $rest_flags, 'register_routes');
		$this->loader->add_action('rest_api_init', $rest_messages, 'register_routes');
		$this->loader->add_action('rest_api_init', $rest_notes, 'register_routes');
		$this->loader->add_action('rest_api_init', $rest_moderation, 'register_routes');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}