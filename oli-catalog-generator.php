<?php
/**
 * Plugin Name: Oli Catalog & Product PDF
 * Plugin URI: https://github.com/bigrat95/oli-catalog-generator
 * Description: Build printable product catalogues from WooCommerce categories (manual add/remove, Canada / US editions, end-user or dealer pricing), plus an optional "Download PDF" product sheet button for product pages.
 * Version: 1.11.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * Author: Olivier Bigras
 * Author URI: https://olivierbigras.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: oli-catalog-generator
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'OLICG_VERSION', '1.11.0' );
define( 'OLICG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OLICG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once OLICG_PLUGIN_DIR . 'includes/class-olicg-i18n.php';
require_once OLICG_PLUGIN_DIR . 'includes/class-olicg-pricing.php';
require_once OLICG_PLUGIN_DIR . 'includes/class-olicg-catalog.php';
require_once OLICG_PLUGIN_DIR . 'includes/class-olicg-design.php';
require_once OLICG_PLUGIN_DIR . 'includes/class-olicg-admin.php';
require_once OLICG_PLUGIN_DIR . 'includes/class-olicg-product-pdf.php';

// Translations must not load before init (WordPress 6.7+).
add_action( 'init', function () {
	load_plugin_textdomain( 'oli-catalog-generator', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	if ( class_exists( 'WooCommerce' ) ) {
		OLICG_Admin::init();
		OLICG_Product_PDF::init();
	}
} );
