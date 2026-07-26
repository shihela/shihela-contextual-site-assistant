<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://yukdigitalz.com/shihela-contextual-site-assistant
 * @since             1.0.0
 * @package           Shihela_Contextual_Site_Assistant
 *
 * @wordpress-plugin
 * Plugin Name:       Shihela Contextual Site Assistant
 * Plugin URI:        https://yukdigitalz.com/shihela-contextual-site-assistant
 * Description:       A professional floating AI assistant that answers visitors' questions in the context of the pages they browse, powered by Gemini and OpenAI.
 * Version:           1.1.0
 * Author:            Shihela
 * Author URI:        https://yukdigitalz.com/
 * License:           GPL-2.0+
 * Text Domain:       shihela-contextual-site-assistant
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently active version of the plugin.
 */
define( 'SHIHELA_CONTEXTUAL_SITE_ASSISTANT_VERSION', '1.1.0' );

/**
 * Path constant for the plugin directory.
 */
define( 'SHIHELA_CONTEXTUAL_SITE_ASSISTANT_PATH', plugin_dir_path( __FILE__ ) );

/**
 * URL constant for the plugin directory.
 */
define( 'SHIHELA_CONTEXTUAL_SITE_ASSISTANT_URL', plugin_dir_url( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 */
function shihela_contextual_site_assistant_activate() {
	require_once SHIHELA_CONTEXTUAL_SITE_ASSISTANT_PATH . 'includes/class-shihela-contextual-site-assistant-activator.php';
	Shihela_Contextual_Site_Assistant_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function shihela_contextual_site_assistant_deactivate() {
	require_once SHIHELA_CONTEXTUAL_SITE_ASSISTANT_PATH . 'includes/class-shihela-contextual-site-assistant-deactivator.php';
	Shihela_Contextual_Site_Assistant_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'shihela_contextual_site_assistant_activate' );
register_deactivation_hook( __FILE__, 'shihela_contextual_site_assistant_deactivate' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once SHIHELA_CONTEXTUAL_SITE_ASSISTANT_PATH . 'includes/class-shihela-contextual-site-assistant.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * kicking off the plugin from this point will register all hooks.
 *
 * @since    1.0.0
 */
function shihela_contextual_site_assistant_run() {
	$plugin = new Shihela_Contextual_Site_Assistant();
	$plugin->run();
}
shihela_contextual_site_assistant_run();
