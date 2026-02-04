<?php
/**
 * Shortcode registration and template rendering.
 */
class ZAOBank_Shortcodes {

	/**
	 * Template directory path.
	 */
	private $template_dir;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->template_dir = ZAOBANK_PLUGIN_DIR . 'public/templates/';
	}

	/**
	 * Register all shortcodes.
	 */
	public function register_shortcodes() {
		add_shortcode('zaobank_dashboard', array($this, 'render_dashboard'));
		add_shortcode('zaobank_jobs', array($this, 'render_jobs_list'));
		add_shortcode('zaobank_job', array($this, 'render_job_single'));
		add_shortcode('zaobank_job_form', array($this, 'render_job_form'));
		add_shortcode('zaobank_my_jobs', array($this, 'render_my_jobs'));
		add_shortcode('zaobank_profile', array($this, 'render_profile'));
		add_shortcode('zaobank_profile_edit', array($this, 'render_profile_edit'));
		add_shortcode('zaobank_messages', array($this, 'render_messages'));
		add_shortcode('zaobank_conversation', array($this, 'render_conversation'));
		add_shortcode('zaobank_exchanges', array($this, 'render_exchanges'));
		add_shortcode('zaobank_appreciations', array($this, 'render_appreciations'));
	}

	/**
	 * Render dashboard template.
	 */
	public function render_dashboard($atts) {
		if (!is_user_logged_in()) {
			return $this->render_login_required(__('Please log in to view your dashboard.', 'zaobank'));
		}

		return $this->load_template('dashboard');
	}

	/**
	 * Render jobs list template.
	 *
	 * If a job_id URL parameter is present, renders the single job view
	 * instead. This allows the jobs list page to handle both views.
	 */
	public function render_jobs_list($atts) {
		// If job_id is in the URL, render single job view
		if (isset($_GET['job_id']) && absint($_GET['job_id']) > 0) {
			return $this->render_job_single(array('id' => absint($_GET['job_id'])));
		}

		$atts = shortcode_atts(array(
			'region' => '',
			'status' => 'available'
		), $atts);

		return $this->load_template('jobs-list', $atts);
	}

	/**
	 * Render single job template.
	 */
	public function render_job_single($atts) {
		$atts = shortcode_atts(array(
			'id' => isset($_GET['job_id']) ? absint($_GET['job_id']) : 0
		), $atts);

		if (empty($atts['id'])) {
			return '<div class="zaobank-error">' . __('No job specified.', 'zaobank') . '</div>';
		}

		return $this->load_template('job-single', $atts);
	}

	/**
	 * Render job form template.
	 */
	public function render_job_form($atts) {
		if (!is_user_logged_in()) {
			return $this->render_login_required(__('Please log in to create a job.', 'zaobank'));
		}

		$atts = shortcode_atts(array(
			'id' => isset($_GET['job_id']) ? absint($_GET['job_id']) : 0
		), $atts);

		return $this->load_template('job-form', $atts);
	}

	/**
	 * Render my jobs template.
	 */
	public function render_my_jobs($atts) {
		if (!is_user_logged_in()) {
			return $this->render_login_required(__('Please log in to view your jobs.', 'zaobank'));
		}

		return $this->load_template('my-jobs');
	}

	/**
	 * Render profile template.
	 */
	public function render_profile($atts) {
		$atts = shortcode_atts(array(
			'user_id' => isset($_GET['user_id']) ? absint($_GET['user_id']) : 0
		), $atts);

		// If no user_id specified and user is logged in, show own profile
		if (empty($atts['user_id']) && is_user_logged_in()) {
			$atts['user_id'] = get_current_user_id();
			$atts['is_own_profile'] = true;
		} elseif (empty($atts['user_id'])) {
			return $this->render_login_required(__('Please log in to view your profile.', 'zaobank'));
		} else {
			$atts['is_own_profile'] = (is_user_logged_in() && $atts['user_id'] == get_current_user_id());
		}

		return $this->load_template('profile', $atts);
	}

	/**
	 * Render profile edit template.
	 */
	public function render_profile_edit($atts) {
		if (!is_user_logged_in()) {
			return $this->render_login_required(__('Please log in to edit your profile.', 'zaobank'));
		}

		return $this->load_template('profile-edit');
	}

	/**
	 * Render messages template.
	 *
	 * Routes to conversation view if user_id param is present,
	 * or to job updates view if view=updates.
	 */
	public function render_messages($atts) {
		if (!is_user_logged_in()) {
			return $this->render_login_required(__('Please log in to view your messages.', 'zaobank'));
		}

		// If user_id is in the URL, render conversation view
		if (isset($_GET['user_id']) && absint($_GET['user_id']) > 0) {
			return $this->render_conversation(array('user_id' => absint($_GET['user_id'])));
		}

		// If view=updates, show job updates view
		if (isset($_GET['view']) && $_GET['view'] === 'updates') {
			return $this->load_template('messages', array('view' => 'updates'));
		}

		return $this->load_template('messages');
	}

	/**
	 * Render conversation template.
	 */
	public function render_conversation($atts) {
		if (!is_user_logged_in()) {
			return $this->render_login_required(__('Please log in to view this conversation.', 'zaobank'));
		}

		$atts = shortcode_atts(array(
			'user_id' => isset($_GET['user_id']) ? absint($_GET['user_id']) : 0
		), $atts);

		if (empty($atts['user_id'])) {
			return '<div class="zaobank-error">' . __('No user specified.', 'zaobank') . '</div>';
		}

		return $this->load_template('conversation', $atts);
	}

	/**
	 * Render exchanges template.
	 */
	public function render_exchanges($atts) {
		if (!is_user_logged_in()) {
			return $this->render_login_required(__('Please log in to view your exchange history.', 'zaobank'));
		}

		return $this->load_template('exchanges');
	}

	/**
	 * Render appreciations template.
	 */
	public function render_appreciations($atts) {
		$atts = shortcode_atts(array(
			'user_id' => isset($_GET['user_id']) ? absint($_GET['user_id']) : 0
		), $atts);

		// If no user_id specified and user is logged in, show own appreciations
		if (empty($atts['user_id']) && is_user_logged_in()) {
			$atts['user_id'] = get_current_user_id();
		} elseif (empty($atts['user_id'])) {
			return $this->render_login_required(__('Please log in to view appreciations.', 'zaobank'));
		}

		return $this->load_template('appreciations', $atts);
	}

	/**
	 * Load a template file with theme override support.
	 *
	 * Template lookup order:
	 * 1. Theme: {theme}/zaobank/templates/{template}.php
	 * 2. Theme: {theme}/zaobank/{template}.php
	 * 3. Plugin: {plugin}/public/templates/{template}.php
	 *
	 * @param string $template_name Template name without .php extension.
	 * @param array  $args          Variables to pass to the template.
	 * @return string Rendered template HTML.
	 */
	private function load_template($template_name, $args = array()) {
		$template_path = $this->locate_template($template_name);

		if (!$template_path) {
			return '<div class="zaobank-error">' .
				sprintf(__('Template not found: %s', 'zaobank'), esc_html($template_name)) .
				'</div>';
		}

		// Extract args for use in template
		if (!empty($args)) {
			extract($args);
		}

		// Start output buffering
		ob_start();

		// Include template
		include $template_path;

		// Return buffered content
		return ob_get_clean();
	}

	/**
	 * Locate a template file, checking theme directories first.
	 *
	 * Template lookup order:
	 * 1. {theme}/zaobank/templates/{template}.php
	 * 2. {theme}/zaobank/{template}.php
	 * 3. {theme}/app/{template}.php (for /app section themes)
	 * 4. {plugin}/public/templates/{template}.php
	 *
	 * @param string $template_name Template name without .php extension.
	 * @return string|false Full path to template file, or false if not found.
	 */
	public function locate_template($template_name) {
		$template_file = $template_name . '.php';

		// Build list of paths to check (theme first, then plugin)
		$paths = array(
			// Standard zaobank template locations
			get_stylesheet_directory() . '/zaobank/templates/' . $template_file,
			get_stylesheet_directory() . '/zaobank/' . $template_file,
			// App section location (for themes that organize by section)
			get_stylesheet_directory() . '/app/' . $template_file,
			// Parent theme fallbacks
			get_template_directory() . '/zaobank/templates/' . $template_file,
			get_template_directory() . '/zaobank/' . $template_file,
			get_template_directory() . '/app/' . $template_file,
			// Plugin default
			$this->template_dir . $template_file,
		);

		// Allow filtering of template paths
		$paths = apply_filters('zaobank_template_paths', $paths, $template_name);

		// Return first path that exists
		foreach ($paths as $path) {
			if (file_exists($path)) {
				return $path;
			}
		}

		return false;
	}

	/**
	 * Get the path to a template for theme overrides.
	 *
	 * Helper method for themes to know where to place override templates.
	 *
	 * @param string $template_name Template name.
	 * @return array Array with 'theme_path' and 'plugin_path' keys.
	 */
	public static function get_template_paths($template_name) {
		return array(
			'theme_path'  => get_stylesheet_directory() . '/zaobank/templates/' . $template_name . '.php',
			'plugin_path' => ZAOBANK_PLUGIN_DIR . 'public/templates/' . $template_name . '.php',
		);
	}

	/**
	 * Render login required message.
	 */
	private function render_login_required($message) {
		$login_url = wp_login_url(get_permalink());

		ob_start();
		?>
		<div class="zaobank-login-required">
			<div class="zaobank-login-icon">
				<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<circle cx="12" cy="7" r="4"/>
					<path d="M5.5 21a7.5 7.5 0 0 1 13 0"/>
				</svg>
			</div>
			<p><?php echo esc_html($message); ?></p>
			<a href="<?php echo esc_url($login_url); ?>" class="zaobank-btn zaobank-btn-primary">
				<?php _e('Log In', 'zaobank'); ?>
			</a>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get page URLs for navigation.
	 */
	public static function get_page_urls() {
		// Allow filtering of page slugs
		$slugs = apply_filters('zaobank_page_slugs', array(
			'dashboard' => 'timebank-dashboard',
			'jobs' => 'timebank-jobs',
			'job_form' => 'timebank-new-job',
			'my_jobs' => 'timebank-my-jobs',
			'profile' => 'timebank-profile',
			'profile_edit' => 'timebank-profile-edit',
			'messages' => 'timebank-messages',
			'exchanges' => 'timebank-exchanges',
			'appreciations' => 'timebank-appreciations'
		));

		$urls = array();
		foreach ($slugs as $key => $slug) {
			$page = get_page_by_path($slug);
			$urls[$key] = $page ? get_permalink($page) : home_url('/' . $slug . '/');
		}

		return $urls;
	}

	/**
	 * Get list of all available templates.
	 *
	 * Useful for themes to know what can be overridden.
	 *
	 * @return array Template names and descriptions.
	 */
	public static function get_available_templates() {
		return array(
			'dashboard'     => __('User dashboard with balance and recent activity', 'zaobank'),
			'jobs-list'     => __('Browse available jobs listing', 'zaobank'),
			'job-single'    => __('Single job detail view', 'zaobank'),
			'job-form'      => __('Create or edit job form', 'zaobank'),
			'my-jobs'       => __('User\'s posted and claimed jobs', 'zaobank'),
			'profile'       => __('User profile view', 'zaobank'),
			'profile-edit'  => __('Profile edit form', 'zaobank'),
			'messages'      => __('Conversations list', 'zaobank'),
			'conversation'  => __('Single conversation thread', 'zaobank'),
			'exchanges'     => __('Exchange history', 'zaobank'),
			'appreciations' => __('Appreciations received/given', 'zaobank'),
			'components/bottom-nav' => __('Mobile bottom navigation bar', 'zaobank'),
		);
	}

	/**
	 * Check if a template has a theme override.
	 *
	 * @param string $template_name Template name.
	 * @return bool True if theme has an override.
	 */
	public function has_theme_override($template_name) {
		$template_path = $this->locate_template($template_name);
		if (!$template_path) {
			return false;
		}
		// Check if the located template is outside the plugin directory
		return strpos($template_path, ZAOBANK_PLUGIN_DIR) !== 0;
	}

	/**
	 * Get unread message count for current user.
	 */
	public static function get_unread_message_count() {
		if (!is_user_logged_in()) {
			return 0;
		}

		global $wpdb;
		$table = ZAOBank_Database::get_messages_table();
		$user_id = get_current_user_id();

		$count = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM $table WHERE to_user_id = %d AND is_read = 0",
			$user_id
		));

		return (int) $count;
	}

	/**
	 * Render a template directly (bypassing shortcode auth checks).
	 *
	 * Use this in page templates where authentication is handled externally
	 * (e.g., by AAM plugin restricting the /app/ section).
	 *
	 * Usage in page template:
	 *   <?php zaobank_render_template('dashboard'); ?>
	 *
	 * @param string $template_name Template name without .php extension.
	 * @param array  $args          Variables to pass to template.
	 * @return void Outputs template directly.
	 */
	public function render_template_direct($template_name, $args = array()) {
		echo $this->load_template($template_name, $args);
	}

	/**
	 * Get template content as string (bypassing shortcode auth checks).
	 *
	 * @param string $template_name Template name without .php extension.
	 * @param array  $args          Variables to pass to template.
	 * @return string Template HTML.
	 */
	public function get_template($template_name, $args = array()) {
		return $this->load_template($template_name, $args);
	}

	/**
	 * Get singleton instance.
	 *
	 * @return ZAOBank_Shortcodes
	 */
	public static function instance() {
		static $instance = null;
		if (null === $instance) {
			$instance = new self();
		}
		return $instance;
	}
}
