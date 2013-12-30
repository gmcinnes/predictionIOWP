<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   PredictionIOWP
 * @author    Matt Read <mread@ideacouture.com>
 * @license   GPL-2.0+
 * @link      http://www.ideacouture.com
 * @copyright 2013 Idea Couture
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// @TODO: Define uninstall functionality here