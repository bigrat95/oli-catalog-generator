<?php
/**
 * Removes every option the plugin created.
 *
 * @package OliCatalogGenerator
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

foreach ( array( 'olicg_settings', 'olicg_pdf_settings', 'olicg_design', 'olicg_cover', 'olicg_version' ) as $olicg_option ) {
	delete_option( $olicg_option );
}
