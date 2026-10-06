<?php
/**
 * Plugin Name: Oli Catalog & Product PDF
 * Plugin URI: https://github.com/bigrat95/oli-catalog-generator
 * Description: Build private, print-ready product catalogues from WooCommerce categories (Canada / US editions, dealer or retail prices, price list tables), plus an optional "Download PDF" product sheet. Works with ACF.
 * Version: 1.15.2
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * Author: Olivier Bigras
 * Author URI: https://olivierbigras.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: oli-catalog-generator
 * Domain Path: /languages
 * WC requires at least: 8.2
 * WC tested up to: 11.1
 *
 * @package OliCatalogGenerator
 * @author  Olivier Bigras (bigrat95)
 * @link    https://olivierbigras.com
 */

defined( 'ABSPATH' ) || exit;

define( 'OLICG_VERSION', '1.15.2' );
define( 'OLICG_FILE', __FILE__ );
define( 'OLICG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OLICG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OLICG_BASENAME', plugin_basename( __FILE__ ) );

require_once OLICG_PLUGIN_DIR . 'includes/class-olicg-i18n.php';
require_once OLICG_PLUGIN_DIR . 'includes/class-olicg-acf.php';
require_once OLICG_PLUGIN_DIR . 'includes/class-olicg-pricing.php';
require_once OLICG_PLUGIN_DIR . 'includes/class-olicg-catalog.php';
require_once OLICG_PLUGIN_DIR . 'includes/class-olicg-design.php';
require_once OLICG_PLUGIN_DIR . 'includes/class-olicg-cover.php';
require_once OLICG_PLUGIN_DIR . 'includes/class-olicg-admin.php';
require_once OLICG_PLUGIN_DIR . 'includes/class-olicg-product-pdf.php';

add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', OLICG_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', OLICG_FILE, true );
		}
	}
);

/**
 * Boot once WordPress is ready (translations must not load before init since WordPress 6.7).
 *
 * @return void
 */
function olicg_boot() {
	// phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- bundled French translations for installs from GitHub; WordPress.org language packs still take precedence.
	load_plugin_textdomain( 'oli-catalog-generator', false, dirname( OLICG_BASENAME ) . '/languages' );
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	olicg_maybe_upgrade();
	OLICG_Admin::init();
	OLICG_Product_PDF::init();
}
add_action( 'init', 'olicg_boot' );

/**
 * One-time changes when the plugin is updated.
 *
 * @return void
 */
function olicg_maybe_upgrade() {
	$installed = (string) get_option( 'olicg_version', '' );
	if ( OLICG_VERSION === $installed ) {
		return;
	}
	// Before 1.14 fonts came from Google Fonts by default; existing catalogues keep their look.
	if ( version_compare( '' !== $installed ? $installed : '0', '1.14.0', '<' ) && false !== get_option( OLICG_Catalog::OPTION ) ) {
		$design = get_option( OLICG_Design::OPTION, array() );
		$design = is_array( $design ) ? $design : array();
		if ( ! isset( $design['font_source'] ) ) {
			$design['font_source'] = 'google';
			update_option( OLICG_Design::OPTION, $design, false );
		}
	}
	if ( version_compare( '' !== $installed ? $installed : '0', '1.15.2', '<' ) ) {
		$cover = get_option( OLICG_Cover::OPTION, array() );
		if ( is_array( $cover ) && OLICG_Cover::skip_band2( $cover ) ) {
			if ( ! isset( $cover['texts'] ) || ! is_array( $cover['texts'] ) ) {
				$cover['texts'] = array();
			}
			$cover['texts']['band2_label'] = '';
			$cover['texts']['band2_value'] = '';
			update_option( OLICG_Cover::OPTION, $cover, false );
		}
	}
	update_option( 'olicg_version', OLICG_VERSION );
}

/**
 * "Settings" link on the Plugins screen.
 *
 * @param string[] $links Action links.
 * @return string[]
 */
function olicg_action_links( $links ) {
	array_unshift(
		$links,
		'<a href="' . esc_url( admin_url( 'admin.php?page=oli-catalog-generator' ) ) . '">' . esc_html__( 'Settings', 'oli-catalog-generator' ) . '</a>'
	);
	return $links;
}
add_filter( 'plugin_action_links_' . OLICG_BASENAME, 'olicg_action_links' );
