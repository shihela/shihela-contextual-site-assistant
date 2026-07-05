<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://shihela.com/contextual-site-assistant
 * @since      1.0.0
 *
 * @package    Shihela_Contextual_Site_Assistant
 * @subpackage Shihela_Contextual_Site_Assistant/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Shihela_Contextual_Site_Assistant
 * @subpackage Shihela_Contextual_Site_Assistant/includes
 * @author     shihela
 */
class Shihela_Contextual_Site_Assistant_Deactivator {

	/**
	 * Run deactivation routine.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// Standard WordPress practice: Do not delete options here. 
		// Keep them in case the user is just updating the plugin.
		// Deletion should be handled in an uninstall.php file if necessary.
	}
}
