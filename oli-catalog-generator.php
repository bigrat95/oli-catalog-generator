<?php
/**
 * Plugin Name: Oli Catalog Generator
 * Plugin URI: https://github.com/bigrat95/oli-catalog-generator
 * Description: Build printable product catalogues (e.g. accessories) from WooCommerce categories, with manual add/remove, Canada / US editions and end-user or dealer pricing.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * Author: Olivier Bigras
 * Author URI: https://olivierbigras.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: oli-catalog-generator
 */

defined( 'ABSPATH' ) || exit;

define( 'OLICG_VERSION', '1.0.0' );
define( 'OLICG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OLICG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once OLICG_PLUGIN_DIR . 'includes/class-olicg-pricing.php';
require_once OLICG_PLUGIN_DIR . 'includes/class-olicg-catalog.php';
require_once OLICG_PLUGIN_DIR . 'includes/class-olicg-admin.php';

add_action( 'plugins_loaded', function () {
	if ( class_exists( 'WooCommerce' ) ) {
		OLICG_Admin::init();
	}
} );
