<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://shihela.com/contextual-site-assistant
 * @since      1.0.0
 *
 * @package    Shihela_Contextual_Site_Assistant
 * @subpackage Shihela_Contextual_Site_Assistant/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @since      1.0.0
 * @package    Shihela_Contextual_Site_Assistant
 * @subpackage Shihela_Contextual_Site_Assistant/includes
 * @author     shihela
 */
class Shihela_Contextual_Site_Assistant {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->plugin_name = 'shihela-contextual-site-assistant';
		$this->version = SHIHELA_CONTEXTUAL_SITE_ASSISTANT_VERSION;

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_privacy_hooks();
		add_action( 'plugins_loaded', array( $this, 'maybe_upgrade_database' ) );
		add_action( 'wp_initialize_site', array( $this, 'on_initialize_multisite_blog' ) );
	}

	/**
	 * Initialize plugin tables and defaults when a new Multisite blog is created.
	 *
	 * @since    1.4.0
	 * @param    WP_Site $new_site Newly initialized site object.
	 */
	public function on_initialize_multisite_blog( $new_site ) {
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( is_plugin_active_for_network( 'shihela-contextual-site-assistant/shihela-contextual-site-assistant.php' ) ) {
			require_once SHIHELA_CONTEXTUAL_SITE_ASSISTANT_PATH . 'includes/class-shihela-contextual-site-assistant-activator.php';
			switch_to_blog( (int) $new_site->blog_id );
			Shihela_Contextual_Site_Assistant_Activator::activate_single_site();
			restore_current_blog();
		}
	}

	/**
	 * Ensure database schema migrations run after plugin updates.
	 *
	 * @since    1.4.0
	 */
	public function maybe_upgrade_database() {
		if ( '1.1.0' !== get_option( 'shihela_contextual_site_assistant_db_version', '' ) ) {
			require_once SHIHELA_CONTEXTUAL_SITE_ASSISTANT_PATH . 'includes/class-shihela-contextual-site-assistant-activator.php';
			Shihela_Contextual_Site_Assistant_Activator::maybe_upgrade();
		}
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		require_once SHIHELA_CONTEXTUAL_SITE_ASSISTANT_PATH . 'includes/class-shihela-contextual-site-assistant-admin.php';
		require_once SHIHELA_CONTEXTUAL_SITE_ASSISTANT_PATH . 'includes/class-shihela-contextual-site-assistant-public.php';
		require_once SHIHELA_CONTEXTUAL_SITE_ASSISTANT_PATH . 'includes/class-shihela-contextual-site-assistant-privacy.php';
	}

	/**
	 * Register WordPress Privacy Tools (GDPR) hooks.
	 *
	 * @since    1.4.0
	 * @access   private
	 */
	private function define_privacy_hooks() {
		$plugin_privacy = new Shihela_Contextual_Site_Assistant_Privacy();

		add_action( 'admin_init', array( $plugin_privacy, 'add_privacy_policy_content' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( $plugin_privacy, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $plugin_privacy, 'register_eraser' ) );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Shihela_Contextual_Site_Assistant_Admin( $this->get_plugin_name(), $this->get_version() );

		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts' ) );
		add_action( 'admin_menu', array( $plugin_admin, 'add_plugin_admin_menu' ) );
		add_action( 'admin_init', array( $plugin_admin, 'register_settings' ) );
		add_action( 'admin_init', array( $plugin_admin, 'handle_lead_actions' ) );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new Shihela_Contextual_Site_Assistant_Public( $this->get_plugin_name(), $this->get_version() );

		add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_scripts' ) );
		add_action( 'wp_footer', array( $plugin_public, 'render_chat_widget' ) );
		add_action( 'rest_api_init', array( $plugin_public, 'register_rest_routes' ) );
	}

	/**
	 * Run the plugin by letting WordPress parse all registered actions.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		// Hooks are registered automatically during instantiation.
		// This method is kept for boilerplating compliance and extensibility.
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
