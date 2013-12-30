<?php
/**
 * The WordPress Plugin Boilerplate.
 *
 * A foundation off of which to build well-documented WordPress plugins that
 * also follow WordPress Coding Standards and PHP best practices.
 *
 * @package   PredictionIOWP
 * @author    Matt Read <mread@ideacouture.com>
 * @license   GPL-2.0+
 * @link      http://ideacouture.com
 * @copyright 2013 Idea Couture
 *
 * @wordpress-plugin
 * Plugin Name:       Prediction.IO WP
 * Plugin URI:        http://www.ideacouture.com
 * Description:       A Prediction.IO wrapper created by IdeaCouture
 * Version:           1.0.0
 * Author:            Matt Read <mread@ideacouture.com>
 * Author URI:        http://www.ideacouture.com
 * Text Domain:       PredictionIOWP-locale
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/<owner>/<repo>
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

require_once( plugin_dir_path( __FILE__ ) . 'public/class-PredictionIOWP.php' );

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 *
 */
register_activation_hook( __FILE__, array( 'PredictionIOWP', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'PredictionIOWP', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'PredictionIOWP', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

/*
 * If you want to include Ajax within the dashboard, change the following
 * conditional to:
 *
 * if ( is_admin() ) {
 *   ...
 * }
 *
 * The code below is intended to to give the lightest footprint possible.
 */
if ( is_admin() ) {

	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-PredictionIOWP-admin.php' );
	add_action( 'plugins_loaded', array( 'PredictionIOWP_Admin', 'get_instance' ) );

}
