<?php
/**
 * Plugin Name: Digicyfr Opening Hours PL
 * Description: Show one location's owner-entered weekly hours and dated exceptions.
 * Version: 0.1.0
 * Requires at least: 7.1
 * Requires PHP: 8.3
 * Author: Digicyfr
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: digicyfr-opening-hours-pl
 * Domain Path: /languages
 * Update URI: https://digicyfr.com/plugins/digicyfr-opening-hours-pl/
 *
 * @package Digicyfr_Opening_Hours_PL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DIGICYFR_OPENING_HOURS_PL_VERSION', '0.1.0' );
define( 'DIGICYFR_OPENING_HOURS_PL_PATH', plugin_dir_path( __FILE__ ) );
define( 'DIGICYFR_OPENING_HOURS_PL_URL', plugin_dir_url( __FILE__ ) );

require_once DIGICYFR_OPENING_HOURS_PL_PATH . 'includes/class-digicyfr-opening-hours-pl.php';

Digicyfr_Opening_Hours_PL::boot();
