<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'olicg_settings' );
delete_option( 'olicg_pdf_settings' );
delete_option( 'olicg_design' );
