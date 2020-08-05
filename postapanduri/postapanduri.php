<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://postapanduri.ro
 * @since             1.0.0
 * @package           PostaPanduri
 *
 * @wordpress-plugin
 * Plugin Name:       PostaPanduri Courier Services and Smartlocker pickpup
 * Plugin URI:        https://postapanduri.ro/
 * Description:       Clients who buy online and can opt to pick up the ordered packages from the nearest preset pickup point or to have the parcel delivered to their door.
 * Version:           1.0.4
 * Author:            C Solution SRL
 * Author URI:        https://postapanduri.ro/
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       postapanduri
 * Domain Path:       /languages
 */

namespace PostaPanduri;

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

global $jal_db_version;
$jal_db_version = 1.2;


/**
 * Define Constants
 */

define(__NAMESPACE__ . '\NS', __NAMESPACE__ . '\\');
define(NS . 'PLUGIN_NAME', 'postapanduri');
define(NS . 'PLUGIN_VERSION', '1.0.4');
define(NS . 'PLUGIN_NAME_DIR', plugin_dir_path(__FILE__));
define(NS . 'PLUGIN_NAME_URL', plugin_dir_url(__FILE__));
define(NS . 'PLUGIN_BASENAME', plugin_basename(__FILE__));
define(NS . 'PLUGIN_TEXT_DOMAIN', 'postapanduri');


/**
 * Autoload Classes
 */
require_once(PLUGIN_NAME_DIR . 'inc/libraries/autoloader.php');

/**
 * Register Activation and Deactivation Hooks
 * This action is documented in inc/core/class-activator.php
 */
register_activation_hook(__FILE__, array(NS . 'Inc\Core\Activator', 'activate'));

/**
 * The code that runs during plugin deactivation.
 * This action is documented inc/core/class-deactivator.php
 */
register_deactivation_hook(__FILE__, array(NS . 'Inc\Core\Deactivator', 'deactivate'));


/**
 * Plugin Singleton Container
 *
 * Maintains a single copy of the plugin app object
 *
 * @since    1.0.0
 */
class PostaPanduri
{

	/**
	 * The instance of the plugin.
	 *
	 * @since    1.0.0
	 * @var      Init $init Instance of the plugin.
	 */
	private static $init;

	/**
	 * Loads the plugin
	 *
	 * @access    public
	 */
	public static function init()
	{
		if (null === self::$init) {
			self::$init = new Inc\Core\Init();
			self::$init->run();
		}

		return self::$init;
	}
}

return PostaPanduri::init();
