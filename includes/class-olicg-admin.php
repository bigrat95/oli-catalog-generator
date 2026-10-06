<?php
/**
 * WooCommerce → Catalog Generator screen, save handler and printable output.
 *
 * @package OliCatalogGenerator
 */

defined( 'ABSPATH' ) || exit;

class OLICG_Admin {

	const SLUG = 'oli-catalog-generator';
	const CAP  = 'manage_woocommerce';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 60 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_olicg_save', array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_post_olicg_render', array( __CLASS__, 'handle_render' ) );
		add_action( 'admin_post_nopriv_olicg_render', array( __CLASS__, 'deny' ) );
		add_action( 'admin_post_olicg_export', array( __CLASS__, 'handle_export' ) );
		add_action( 'admin_post_nopriv_olicg_export', array( __CLASS__, 'deny' ) );
		add_action( 'admin_post_olicg_save_design', array( 'OLICG_Design', 'handle_save' ) );
		add_action( 'admin_post_olicg_save_cover', array( 'OLICG_Cover', 'handle_save' ) );
		add_action( 'wp_ajax_olicg_arrange', array( __CLASS__, 'handle_arrange' ) );
	}

	/**
	 * Drag-and-drop order and × removal from the catalogue preview.
	 */
	public static function handle_arrange() {
		if ( ! current_user_can( self::cap() ) ) {
			wp_send_json_error( null, 403 );
		}
		check_ajax_referer( 'olicg_arrange' );

		$op = isset( $_POST['op'] ) ? sanitize_key( wp_unslash( $_POST['op'] ) ) : '';
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		switch ( $op ) {
			case 'order':
				$settings          = OLICG_Catalog::get_settings();
				$ids               = isset( $_POST['ids'] ) ? (array) wp_unslash( $_POST['ids'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- absint in merge_order().
				$settings['order'] = OLICG_Catalog::merge_order( $ids, $settings['order'] );
				OLICG_Catalog::save_settings( $settings );
				wp_send_json_success();
				break;
			case 'remove':
				$id || wp_send_json_error( null, 400 );
				wp_send_json_success( array( 'was_added' => OLICG_Catalog::remove_product( $id ) ) );
				break;
			case 'restore':
				$id || wp_send_json_error( null, 400 );
				OLICG_Catalog::restore_product( $id, ! empty( $_POST['was_added'] ) );
				wp_send_json_success();
				break;
			case 'picture':
				$ids = isset( $_POST['ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['ids'] ) ) : array( $id );
				array_filter( $ids ) || wp_send_json_error( null, 400 );
				OLICG_Catalog::set_pictures( $ids, ! empty( $_POST['show'] ) );
				wp_send_json_success();
				break;
			case 'image':
				$layout = isset( $_POST['layout'] ) ? sanitize_key( wp_unslash( $_POST['layout'] ) ) : '';
				( $id && isset( OLICG_Catalog::layouts()[ $layout ] ) ) || wp_send_json_error( null, 400 );
				OLICG_Catalog::save_image_fit(
					$layout,
					$id,
					isset( $_POST['s'] ) ? (float) $_POST['s'] : 1,
					isset( $_POST['x'] ) ? (float) $_POST['x'] : 0,
					isset( $_POST['y'] ) ? (float) $_POST['y'] : 0
				);
				wp_send_json_success();
				break;
		}
		wp_send_json_error( null, 400 );
	}

	/**
	 * Capability needed to build catalogues (they contain dealer pricing).
	 */
	public static function cap() {
		return (string) apply_filters( 'olicg_capability', self::CAP );
	}

	public static function deny() {
		wp_die( esc_html__( 'You are not allowed to view this catalogue.', 'oli-catalog-generator' ), '', array( 'response' => 403 ) );
	}

	public static function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Oli Catalog & Product PDF', 'oli-catalog-generator' ),
			__( 'Catalog & Product PDF', 'oli-catalog-generator' ),
			self::cap(),
			self::SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	public static function assets( $hook ) {
		if ( false === strpos( (string) $hook, self::SLUG ) ) {
			return;
		}
		wp_enqueue_style( 'woocommerce_admin_styles' );
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_media();
		wp_enqueue_script( 'wc-enhanced-select' );
		wp_enqueue_style( 'olicg-admin', OLICG_PLUGIN_URL . 'assets/css/admin.css', array(), OLICG_VERSION );
		wp_enqueue_script( 'olicg-admin', OLICG_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-sortable', 'wc-enhanced-select', 'wp-color-picker' ), OLICG_VERSION, true );
	}

	/**
	 * @param string[]|null $components Price lines; null = the saved selection.
	 */
	public static function render_url( $region, $components = null, $lang = '' ) {
		return add_query_arg(
			array_filter( array(
				'action'   => 'olicg_render',
				'region'   => $region,
				'prices'   => null === $components ? '' : ( $components ? implode( ',', $components ) : 'none' ),
				'lang'     => $lang,
				'_wpnonce' => wp_create_nonce( 'olicg_render' ),
			), 'strlen' ),
			admin_url( 'admin-post.php' )
		);
	}

	/**
	 * Spreadsheet (CSV) of the saved selection: same products, order and prices as the catalogue.
	 */
	public static function export_url( $region, $lang = '' ) {
		return add_query_arg(
			array_filter( array(
				'action'   => 'olicg_export',
				'region'   => $region,
				'lang'     => $lang,
				'_wpnonce' => wp_create_nonce( 'olicg_export' ),
			), 'strlen' ),
			admin_url( 'admin-post.php' )
		);
	}

	/**
	 * Custom texts appear in WPML String Translation / Polylang → Translations.
	 */
	private static function register_strings() {
		$catalog = OLICG_Catalog::get_settings();
		if ( ! in_array( $catalog['title'], array( OLICG_Catalog::DEFAULT_TITLE, __( 'Accessories Catalogue', 'oli-catalog-generator' ) ), true ) ) {
			OLICG_I18n::register_string( 'Catalogue title', $catalog['title'] );
		}
		foreach ( $catalog['price_labels'] as $key => $label ) {
			OLICG_I18n::register_string( 'Price label: ' . $key, $label );
		}
		OLICG_Product_PDF::register_strings();
		OLICG_Cover::register_strings();
	}

	public static function handle_save() {
		if ( ! current_user_can( self::cap() ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'oli-catalog-generator' ), 403 );
		}
		check_admin_referer( 'olicg_save' );

		$old      = OLICG_Catalog::get_settings();
		$regions  = OLICG_Pricing::regions();
		$ids      = static function ( $key ) {
			return isset( $_POST[ $key ] ) ? array_values( array_unique( array_filter( array_map( 'absint', (array) wp_unslash( $_POST[ $key ] ) ) ) ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- check_admin_referer() above.
		};

		// Only rows shown in the product table can be removed/restored; categories
		// added in this same save are not listed yet, so their products stay in.
		$listed   = $ids( 'olicg_listed' );
		$included = $ids( 'olicg_included' );
		$excluded = array_diff( array_map( 'intval', $old['excluded'] ), $listed );
		$excluded = array_values( array_unique( array_merge( $excluded, array_diff( $listed, $included ) ) ) );

		$region = isset( $_POST['olicg_region'] ) ? sanitize_key( wp_unslash( $_POST['olicg_region'] ) ) : 'ca';
		$paper  = isset( $_POST['olicg_paper'] ) ? sanitize_key( wp_unslash( $_POST['olicg_paper'] ) ) : 'letter';
		$cols   = isset( $_POST['olicg_columns'] ) ? absint( $_POST['olicg_columns'] ) : 6;
		$layout = isset( $_POST['olicg_layout'] ) ? sanitize_key( wp_unslash( $_POST['olicg_layout'] ) ) : 'compact';
		$title  = isset( $_POST['olicg_title'] ) ? sanitize_text_field( wp_unslash( $_POST['olicg_title'] ) ) : '';
		$sort   = isset( $_POST['olicg_sort'] ) ? sanitize_key( wp_unslash( $_POST['olicg_sort'] ) ) : 'name';

		$settings = array(
			'title'             => OLICG_I18n::normalize_default( $title, OLICG_Catalog::DEFAULT_TITLE, __( 'Accessories Catalogue', 'oli-catalog-generator' ) ),
			'categories'        => isset( $_POST['tax_input']['product_cat'] ) ? array_values( array_filter( array_map( 'absint', (array) wp_unslash( $_POST['tax_input']['product_cat'] ) ) ) ) : array(),
			'excluded'          => $excluded,
			'added'             => $ids( 'olicg_added' ),
			'order'             => empty( $_POST['olicg_order_changed'] ) ? $old['order'] : OLICG_Catalog::merge_order( $ids( 'olicg_order' ), $old['order'] ),
			'images'            => $old['images'],
			'pictures_hidden'   => $old['pictures_hidden'],
			'region'            => isset( $regions[ $region ] ) ? $region : 'ca',
			'prices'            => OLICG_Pricing::sanitize_components( isset( $_POST['olicg_prices'] ) ? wp_unslash( $_POST['olicg_prices'] ) : array() ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitize_components().
			'price_labels'      => OLICG_Catalog::sanitize_price_labels( isset( $_POST['olicg_price_labels'] ) ? wp_unslash( $_POST['olicg_price_labels'] ) : array() ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitize_price_labels().
			'language'          => OLICG_I18n::sanitize_language( isset( $_POST['olicg_language'] ) ? wp_unslash( $_POST['olicg_language'] ) : '' ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitize_language().
			'layout'            => isset( OLICG_Catalog::layouts()[ $layout ] ) ? $layout : 'compact',
			'columns'           => $cols >= 2 && $cols <= 6 ? $cols : 6,
			'paper'             => in_array( $paper, array( 'letter', 'a4' ), true ) ? $paper : 'letter',
			'hide_no_price'     => empty( $_POST['olicg_hide_no_price'] ) ? 0 : 1,
			'hide_out_of_stock' => empty( $_POST['olicg_hide_out_of_stock'] ) ? 0 : 1,
			'show_image'        => empty( $_POST['olicg_show_image'] ) ? 0 : 1,
			'show_sku'          => empty( $_POST['olicg_show_sku'] ) ? 0 : 1,
			'show_upc'          => empty( $_POST['olicg_show_upc'] ) ? 0 : 1,
			'show_brand'        => empty( $_POST['olicg_show_brand'] ) ? 0 : 1,
			'section_new_page'  => empty( $_POST['olicg_section_new_page'] ) ? 0 : 1,
			'sort'              => isset( OLICG_Catalog::sort_options()[ $sort ] ) ? $sort : 'name',
			'link_products'     => empty( $_POST['olicg_link_products'] ) ? 0 : 1,
			'logo_url'          => $old['logo_url'],
			'fields'            => OLICG_Catalog::sanitize_fields( isset( $_POST['olicg_fields'] ) ? wp_unslash( $_POST['olicg_fields'] ) : array() ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitize_fields().
		);
		if ( '' === $settings['title'] ) {
			$settings['title'] = OLICG_Catalog::DEFAULT_TITLE;
		}

		OLICG_Catalog::save_settings( $settings );
		self::register_strings();

		if ( isset( $_POST['olicg_generate'] ) ) {
			wp_safe_redirect( self::render_url( $settings['region'], null, $settings['language'] ) );
			exit;
		}
		if ( isset( $_POST['olicg_export'] ) ) {
			wp_safe_redirect( self::export_url( $settings['region'], $settings['language'] ) );
			exit;
		}

		wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG, 'saved' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function handle_render() {
		if ( ! is_user_logged_in() || ! current_user_can( self::cap() ) ) {
			self::deny();
		}
		check_admin_referer( 'olicg_render' );

		$settings = OLICG_Catalog::get_settings();
		$lang     = OLICG_I18n::sanitize_language( isset( $_GET['lang'] ) ? wp_unslash( $_GET['lang'] ) : $settings['language'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitize_language().

		// Everything below (labels, dates, product texts) renders in the catalogue language.
		OLICG_I18n::switch_to( $lang );

		$settings['title'] = OLICG_Catalog::title( $settings, $lang );
		$html_lang         = str_replace( '_', '-', '' !== $lang ? OLICG_I18n::locale( $lang ) : determine_locale() );
		$regions           = OLICG_Pricing::regions();
		$region            = isset( $_GET['region'] ) ? sanitize_key( wp_unslash( $_GET['region'] ) ) : $settings['region'];
		$region            = isset( $regions[ $region ] ) ? $region : 'ca';

		if ( isset( $_GET['prices'] ) ) {
			$prices = OLICG_Pricing::sanitize_components( sanitize_text_field( wp_unslash( $_GET['prices'] ) ) );
		} elseif ( isset( $_GET['price'] ) ) {
			// Links made before price lines could be picked individually.
			$prices = OLICG_Pricing::legacy_components( sanitize_key( wp_unslash( $_GET['price'] ) ) );
		} else {
			$prices = $settings['prices'];
		}

		$sections = OLICG_Catalog::get_sections( $settings, $region, $prices, $lang );
		$currency = $regions[ $region ]['currency'];
		$logo_url = OLICG_Catalog::logo_url();

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- standard page cache constant.
		}
		nocache_headers();
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private' );
		header( 'X-Robots-Tag: noindex, nofollow, noarchive' );
		header( 'Referrer-Policy: no-referrer' );
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'Content-Type: text/html; charset=UTF-8' );
		include OLICG_PLUGIN_DIR . 'templates/catalog.php';
		OLICG_I18n::restore();
		exit;
	}

	public static function handle_export() {
		if ( ! is_user_logged_in() || ! current_user_can( self::cap() ) ) {
			self::deny();
		}
		check_admin_referer( 'olicg_export' );

		$settings = OLICG_Catalog::get_settings();
		$lang     = OLICG_I18n::sanitize_language( isset( $_GET['lang'] ) ? wp_unslash( $_GET['lang'] ) : $settings['language'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitize_language().
		$regions  = OLICG_Pricing::regions();
		$region   = isset( $_GET['region'] ) ? sanitize_key( wp_unslash( $_GET['region'] ) ) : $settings['region'];
		$region   = isset( $regions[ $region ] ) ? $region : 'ca';

		OLICG_I18n::switch_to( $lang );
		$labels   = array_intersect_key( OLICG_Catalog::price_labels( $settings, $lang ), OLICG_Pricing::components( $settings['prices'] ) );
		$currency = $regions[ $region ]['currency'];
		$header   = array(
			__( 'Category', 'oli-catalog-generator' ),
			__( 'Product', 'oli-catalog-generator' ),
			__( 'SKU', 'oli-catalog-generator' ),
			__( 'UPC', 'oli-catalog-generator' ),
			__( 'Brand', 'oli-catalog-generator' ),
		);
		foreach ( $labels as $label ) {
			$header[] = $label . ' (' . $currency . ')';
		}
		$header[] = __( 'In stock', 'oli-catalog-generator' );
		$header[] = __( 'Link', 'oli-catalog-generator' );

		$lines = array( (array) apply_filters( 'olicg_csv_header', $header, $region, $lang ) );
		foreach ( OLICG_Catalog::get_sections( $settings, $region, $settings['prices'], $lang ) as $section ) {
			$category = implode( ' › ', array_merge( $section['path'], array( $section['title'] ) ) );
			foreach ( $section['items'] as $item ) {
				$row = array( $category, $item['name'], $item['sku'], $item['upc'], $item['brand'] );
				foreach ( array_keys( $labels ) as $key ) {
					$row[] = $item['prices'][ $key ] ? number_format( $item['prices'][ $key ]['min'], 2, '.', '' ) : '';
				}
				$row[]   = $item['stock'] ? __( 'Yes', 'oli-catalog-generator' ) : __( 'No', 'oli-catalog-generator' );
				$row[]   = $item['url'];
				$lines[] = (array) apply_filters( 'olicg_csv_row', $row, $item, $section, $region, $lang );
			}
		}
		$filename = sanitize_file_name( OLICG_Catalog::title( $settings, $lang ) . '-' . $regions[ $region ]['short'] . '-' . wp_date( 'Y-m-d' ) . '.csv' );
		OLICG_I18n::restore();

		nocache_headers();
		header( 'X-Robots-Tag: noindex, nofollow, noarchive' );
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		// UTF-8 byte order mark: Excel opens accents correctly.
		echo "\xEF\xBB\xBF" . implode( "\r\n", array_map( array( __CLASS__, 'csv_line' ), $lines ) ) . "\r\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV download, cells escaped by csv_line().
		exit;
	}

	/**
	 * One CSV line (RFC 4180). Text starting with = + - @ is prefixed with ' so
	 * spreadsheets never run it as a formula.
	 */
	private static function csv_line( array $cells ) {
		return implode( ',', array_map( static function ( $cell ) {
			$cell = html_entity_decode( wp_strip_all_tags( (string) $cell ), ENT_QUOTES, 'UTF-8' );
			if ( '' !== $cell && ! is_numeric( $cell ) && false !== strpos( "=+-@\t\r", $cell[0] ) ) {
				$cell = "'" . $cell;
			}
			return preg_match( '/[",\r\n]/', $cell ) ? '"' . str_replace( '"', '""', $cell ) . '"' : $cell;
		}, $cells ) );
	}

	public static function render_page() {
		if ( ! current_user_can( self::cap() ) ) {
			return;
		}

		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'catalog'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- tab navigation, read only.
		$tab  = in_array( $tab, array( 'catalog', 'cover', 'pdf', 'design' ), true ) ? $tab : 'catalog';
		$base = admin_url( 'admin.php?page=' . self::SLUG );
		self::register_strings();
		?>
		<div class="wrap olicg">
			<h1><?php esc_html_e( 'Oli Catalog & Product PDF', 'oli-catalog-generator' ); ?></h1>
			<nav class="nav-tab-wrapper">
				<a href="<?php echo esc_url( $base ); ?>" class="nav-tab<?php echo 'catalog' === $tab ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'Catalog', 'oli-catalog-generator' ); ?></a>
				<a href="<?php echo esc_url( $base . '&tab=cover' ); ?>" class="nav-tab<?php echo 'cover' === $tab ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'Cover & pages', 'oli-catalog-generator' ); ?></a>
				<a href="<?php echo esc_url( $base . '&tab=pdf' ); ?>" class="nav-tab<?php echo 'pdf' === $tab ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'Product PDF', 'oli-catalog-generator' ); ?></a>
				<a href="<?php echo esc_url( $base . '&tab=design' ); ?>" class="nav-tab<?php echo 'design' === $tab ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'Design', 'oli-catalog-generator' ); ?></a>
			</nav>

			<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- notice after the save redirect. ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'oli-catalog-generator' ); ?></p></div>
			<?php endif; ?>

			<?php
			if ( 'pdf' === $tab ) {
				OLICG_Product_PDF::render_settings();
			} elseif ( 'design' === $tab ) {
				OLICG_Design::render_settings();
			} elseif ( 'cover' === $tab ) {
				OLICG_Cover::render_settings();
			} else {
				self::render_catalog_tab();
			}
			?>
		</div>
		<?php
	}

	private static function render_catalog_tab() {
		if ( ! function_exists( 'wp_terms_checklist' ) ) {
			require_once ABSPATH . 'wp-admin/includes/template.php';
		}

		$settings = OLICG_Catalog::get_settings();
		$regions  = OLICG_Pricing::regions();
		$excluded = array_map( 'intval', $settings['excluded'] );
		$added    = array_map( 'intval', $settings['added'] );
		$cat_ids  = OLICG_Catalog::get_category_product_ids( $settings['categories'] );
		$rows     = self::product_rows( array_values( array_unique( array_merge( $cat_ids, $added ) ) ), $settings, $cat_ids, $added, $excluded );
		$order     = $settings['order'];
		$included  = count( array_filter( $rows, static function ( $row ) { return $row['included']; } ) );
		$languages = OLICG_I18n::is_multilingual() ? OLICG_I18n::languages() : array();
		$language  = '' !== $settings['language'] ? $settings['language'] : OLICG_I18n::default_language();
		?>
			<p class="olicg-intro"><?php esc_html_e( 'Pick categories, remove or add products, choose the edition, prices and details, then generate a print-ready catalogue (Print → Save as PDF).', 'oli-catalog-generator' ); ?></p>
			<p class="olicg-private"><?php esc_html_e( 'Private: catalogues are only generated here in the admin. They never appear on your website, and catalogue links only open for logged-in shop managers and administrators.', 'oli-catalog-generator' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="olicg_save">
				<?php wp_nonce_field( 'olicg_save' ); ?>

				<div class="olicg-grid">
					<div class="olicg-card">
						<h2><?php esc_html_e( '1. Content', 'oli-catalog-generator' ); ?></h2>

						<p>
							<label for="olicg_title"><strong><?php esc_html_e( 'Catalogue title', 'oli-catalog-generator' ); ?></strong></label><br>
							<input type="text" id="olicg_title" name="olicg_title" class="regular-text" value="<?php echo esc_attr( OLICG_Catalog::DEFAULT_TITLE === $settings['title'] ? __( 'Accessories Catalogue', 'oli-catalog-generator' ) : $settings['title'] ); ?>">
							<?php if ( $languages ) : ?>
								<br><span class="description"><?php esc_html_e( 'The default title is translated automatically. A custom title can be translated in your multilingual plugin’s string translations.', 'oli-catalog-generator' ); ?></span>
							<?php endif; ?>
						</p>

						<p><strong><?php esc_html_e( 'Categories', 'oli-catalog-generator' ); ?></strong><br>
						<span class="description"><?php esc_html_e( 'Sub-categories are included automatically. Save to refresh the product list below.', 'oli-catalog-generator' ); ?></span></p>
						<div class="olicg-cats">
							<ul class="categorychecklist">
								<?php
								wp_terms_checklist( 0, array(
									'taxonomy'      => 'product_cat',
									'selected_cats' => array_map( 'intval', $settings['categories'] ),
									'checked_ontop' => false,
								) );
								?>
							</ul>
						</div>

						<p>
							<label for="olicg_added"><strong><?php esc_html_e( 'Add products manually', 'oli-catalog-generator' ); ?></strong></label><br>
							<select id="olicg_added" name="olicg_added[]" class="wc-product-search olicg-full" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Search for a product…', 'oli-catalog-generator' ); ?>" data-action="woocommerce_json_search_products">
								<?php foreach ( $added as $product_id ) : ?>
									<?php $product = wc_get_product( $product_id ); ?>
									<?php if ( $product ) : ?>
										<option value="<?php echo esc_attr( $product_id ); ?>" selected="selected"><?php echo esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ); ?></option>
									<?php endif; ?>
								<?php endforeach; ?>
							</select>
						</p>
					</div>

					<div class="olicg-card">
						<h2><?php esc_html_e( '2. Edition', 'oli-catalog-generator' ); ?></h2>

						<fieldset class="olicg-choice">
							<legend><strong><?php esc_html_e( 'Market', 'oli-catalog-generator' ); ?></strong></legend>
							<?php foreach ( $regions as $key => $region ) : ?>
								<label><input type="radio" name="olicg_region" value="<?php echo esc_attr( $key ); ?>" <?php checked( $settings['region'], $key ); ?>> <?php echo esc_html( $region['label'] . ' (' . $region['currency'] . ')' ); ?></label>
							<?php endforeach; ?>
						</fieldset>

						<?php if ( $languages ) : ?>
							<fieldset class="olicg-choice">
								<legend><strong><?php esc_html_e( 'Language', 'oli-catalog-generator' ); ?></strong></legend>
								<?php foreach ( $languages as $code => $lang ) : ?>
									<label><input type="radio" name="olicg_language" value="<?php echo esc_attr( $code ); ?>" <?php checked( $language, $code ); ?>> <?php echo esc_html( $lang['label'] ); ?></label>
								<?php endforeach; ?>
								<p class="description"><?php esc_html_e( 'Product names, categories, brands and labels use this language’s translations. Prices, order, removed products and image zoom are shared by all languages.', 'oli-catalog-generator' ); ?></p>
							</fieldset>
						<?php endif; ?>

						<fieldset class="olicg-choice">
							<legend><strong><?php esc_html_e( 'Prices shown', 'oli-catalog-generator' ); ?></strong></legend>
							<?php $olicg_default_labels = OLICG_Pricing::all_components(); ?>
							<?php foreach ( OLICG_Pricing::component_descriptions() as $key => $label ) : ?>
								<div class="olicg-price-choice">
									<label><input type="checkbox" name="olicg_prices[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $settings['prices'], true ) ); ?>> <?php echo esc_html( $label ); ?></label>
									<input type="text" class="olicg-price-label" name="olicg_price_labels[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( isset( $settings['price_labels'][ $key ] ) ? $settings['price_labels'][ $key ] : '' ); ?>" placeholder="<?php echo esc_attr( $olicg_default_labels[ $key ] ); ?>" title="<?php esc_attr_e( 'Label printed in the catalogue', 'oli-catalog-generator' ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: default price label, e.g. Cost */ __( 'Label printed for “%s”', 'oli-catalog-generator' ), $olicg_default_labels[ $key ] ) ); ?>">
								</div>
							<?php endforeach; ?>
							<p class="description"><?php esc_html_e( 'Tick any combination — each ticked price gets its own line on every product. Leave all unticked for a catalogue without prices.', 'oli-catalog-generator' ); ?></p>
							<p class="description"><?php esc_html_e( 'The box next to each price is the label printed in the catalogue (e.g. “Dealer”, “MSRP”, “Street”). Leave it empty to use the default shown in grey.', 'oli-catalog-generator' ); ?></p>
						</fieldset>

						<fieldset class="olicg-choice">
							<legend><strong><?php esc_html_e( 'Product details shown', 'oli-catalog-generator' ); ?></strong></legend>
							<label><input type="checkbox" name="olicg_show_image" value="1" <?php checked( $settings['show_image'] ); ?>> <?php esc_html_e( 'Image', 'oli-catalog-generator' ); ?></label>
							<label><input type="checkbox" name="olicg_show_brand" value="1" <?php checked( $settings['show_brand'] ); ?>> <?php esc_html_e( 'Brand', 'oli-catalog-generator' ); ?></label>
							<label><input type="checkbox" name="olicg_show_sku" value="1" <?php checked( $settings['show_sku'] ); ?>> <?php esc_html_e( 'SKU', 'oli-catalog-generator' ); ?></label>
							<label><input type="checkbox" name="olicg_show_upc" value="1" <?php checked( $settings['show_upc'] ); ?>> <?php esc_html_e( 'UPC', 'oli-catalog-generator' ); ?></label>
							<label><input type="checkbox" name="olicg_link_products" value="1" <?php checked( $settings['link_products'] ); ?>> <?php esc_html_e( 'Link product names to their page (clickable PDF)', 'oli-catalog-generator' ); ?></label>
						</fieldset>

						<?php self::render_sources( $settings ); ?>

						<fieldset class="olicg-choice">
							<legend><strong><?php esc_html_e( 'Layout', 'oli-catalog-generator' ); ?></strong></legend>
							<label><?php esc_html_e( 'Paper', 'oli-catalog-generator' ); ?>
								<select name="olicg_paper">
									<option value="letter" <?php selected( $settings['paper'], 'letter' ); ?>><?php esc_html_e( 'Letter (8.5 × 11 in)', 'oli-catalog-generator' ); ?></option>
									<option value="a4" <?php selected( $settings['paper'], 'a4' ); ?>><?php esc_html_e( 'A4', 'oli-catalog-generator' ); ?></option>
								</select>
							</label>
							<label><?php esc_html_e( 'Layout', 'oli-catalog-generator' ); ?>
								<select name="olicg_layout">
									<?php foreach ( OLICG_Catalog::layouts() as $key => $label ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $settings['layout'], $key ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<label><?php esc_html_e( 'Products per row (grids) / pictures per row (price list)', 'oli-catalog-generator' ); ?>
								<select name="olicg_columns">
									<?php foreach ( array( 2, 3, 4, 5, 6 ) as $cols ) : ?>
										<option value="<?php echo esc_attr( $cols ); ?>" <?php selected( (int) $settings['columns'], $cols ); ?>><?php echo esc_html( $cols ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<label><?php esc_html_e( 'Sort products by', 'oli-catalog-generator' ); ?>
								<select name="olicg_sort">
									<?php foreach ( OLICG_Catalog::sort_options() as $key => $label ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $settings['sort'], $key ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<span class="description"><?php esc_html_e( 'Products you drag into place keep their position.', 'oli-catalog-generator' ); ?></span>
							<label><input type="checkbox" name="olicg_section_new_page" value="1" <?php checked( $settings['section_new_page'] ); ?>> <?php esc_html_e( 'Start each category on a new page', 'oli-catalog-generator' ); ?></label>
							<label><input type="checkbox" name="olicg_hide_no_price" value="1" <?php checked( $settings['hide_no_price'] ); ?>> <?php esc_html_e( 'Hide products without any of the chosen prices', 'oli-catalog-generator' ); ?></label>
							<label><input type="checkbox" name="olicg_hide_out_of_stock" value="1" <?php checked( $settings['hide_out_of_stock'] ); ?>> <?php esc_html_e( 'Hide out-of-stock products', 'oli-catalog-generator' ); ?></label>
						</fieldset>

						<p class="description">
							<?php
							printf(
								/* translators: %s: link to the Cover page tab */
								esc_html__( 'Logo, cover texts, cover image, background, footer and page numbers: %s.', 'oli-catalog-generator' ),
								'<a href="' . esc_url( admin_url( 'admin.php?page=' . self::SLUG . '&tab=cover' ) ) . '">' . esc_html__( 'Cover & pages tab', 'oli-catalog-generator' ) . '</a>'
							);
							?>
						</p>

						<div class="olicg-actions">
							<button type="submit" class="button button-secondary" name="olicg_save" value="1"><?php esc_html_e( 'Save selection', 'oli-catalog-generator' ); ?></button>
							<button type="submit" class="button button-primary" name="olicg_generate" value="1" formtarget="_blank"><?php esc_html_e( 'Save & generate catalogue', 'oli-catalog-generator' ); ?></button>
							<button type="submit" class="button button-secondary" name="olicg_export" value="1"><?php esc_html_e( 'Save & download spreadsheet (CSV)', 'oli-catalog-generator' ); ?></button>
						</div>

						<p class="olicg-quick"><strong><?php esc_html_e( 'Quick generate (saved selection):', 'oli-catalog-generator' ); ?></strong>
							<?php foreach ( $languages ? $languages : array( '' => null ) as $code => $lang ) : ?>
								<br>
								<?php if ( $lang ) : ?>
									<span class="olicg-quick-lang"><?php echo esc_html( $lang['label'] ); ?></span>
								<?php endif; ?>
								<?php foreach ( $regions as $rkey => $region ) : ?>
									<a class="button button-small" target="_blank" href="<?php echo esc_url( self::render_url( $rkey, null, $code ) ); ?>"><?php echo esc_html( $region['label'] . ' (' . $region['currency'] . ')' ); ?></a>
									<a class="button button-small olicg-csv" href="<?php echo esc_url( self::export_url( $rkey, $code ) ); ?>" title="<?php esc_attr_e( 'Download as a spreadsheet (CSV)', 'oli-catalog-generator' ); ?>"><?php esc_html_e( 'CSV', 'oli-catalog-generator' ); ?></a>
								<?php endforeach; ?>
							<?php endforeach; ?>
						</p>
					</div>
				</div>

				<div class="olicg-card olicg-products">
					<div class="olicg-products-head">
						<h2>
							<?php
							/* translators: 1: included products, 2: listed products */
							echo esc_html( sprintf( __( '3. Products — %1$d of %2$d included', 'oli-catalog-generator' ), $included, count( $rows ) ) );
							?>
						</h2>
						<input type="search" class="olicg-filter" placeholder="<?php esc_attr_e( 'Filter by name or SKU…', 'oli-catalog-generator' ); ?>">
					</div>
					<p class="description"><?php esc_html_e( 'Drag the ⋮⋮ handle to change the order within a category. Hover a product and click × (or uncheck it) to remove it. You can also rearrange and remove products directly on the generated catalogue. Prices below are what each edition would print.', 'oli-catalog-generator' ); ?></p>
					<input type="hidden" name="olicg_order_changed" value="" class="olicg-order-changed">

					<?php if ( ! $rows ) : ?>
						<p><em><?php esc_html_e( 'No products yet — select categories or add products, then save.', 'oli-catalog-generator' ); ?></em></p>
					<?php else : ?>
						<table class="widefat striped olicg-table">
							<thead>
								<tr>
									<th class="olicg-handle-col"></th>
									<th class="check-column"></th>
									<th></th>
									<th><?php esc_html_e( 'Product', 'oli-catalog-generator' ); ?></th>
									<th><?php esc_html_e( 'SKU', 'oli-catalog-generator' ); ?></th>
									<th><?php esc_html_e( 'CAD end-user', 'oli-catalog-generator' ); ?></th>
									<th><?php esc_html_e( 'CAD dealer', 'oli-catalog-generator' ); ?></th>
									<th><?php esc_html_e( 'USD end-user', 'oli-catalog-generator' ); ?></th>
									<th><?php esc_html_e( 'USD dealer', 'oli-catalog-generator' ); ?></th>
								</tr>
							</thead>
							<?php foreach ( self::group_rows( $rows, $order, $settings['sort'] ) as $section => $section_rows ) : ?>
								<tbody class="olicg-section">
									<tr class="olicg-section-row">
										<td></td>
										<th class="check-column"><input type="checkbox" class="olicg-toggle-section" checked></th>
										<td colspan="7"><strong><?php echo esc_html( $section ); ?></strong> <span class="count">(<?php echo esc_html( count( $section_rows ) ); ?>)</span></td>
									</tr>
									<?php foreach ( $section_rows as $row ) : ?>
										<tr class="olicg-row<?php echo $row['included'] ? '' : ' is-excluded'; ?>" data-id="<?php echo esc_attr( $row['id'] ); ?>" data-search="<?php echo esc_attr( strtolower( $row['name'] . ' ' . $row['sku'] ) ); ?>">
											<td class="olicg-handle" title="<?php esc_attr_e( 'Drag to reorder', 'oli-catalog-generator' ); ?>">
												<span aria-hidden="true">⋮⋮</span>
												<input type="hidden" name="olicg_order[]" value="<?php echo esc_attr( $row['id'] ); ?>">
											</td>
											<th class="check-column">
												<?php if ( $row['manual'] ) : ?>
													<span class="olicg-badge" title="<?php esc_attr_e( 'Added manually', 'oli-catalog-generator' ); ?>">+</span>
												<?php else : ?>
													<input type="hidden" name="olicg_listed[]" value="<?php echo esc_attr( $row['id'] ); ?>">
													<input type="checkbox" class="olicg-include" name="olicg_included[]" value="<?php echo esc_attr( $row['id'] ); ?>" <?php checked( $row['included'] ); ?>>
												<?php endif; ?>
											</th>
											<td class="olicg-thumb"><img src="<?php echo esc_url( $row['thumb'] ); ?>" alt="" loading="lazy" width="40" height="40"></td>
											<td>
												<a href="<?php echo esc_url( get_edit_post_link( $row['id'] ) ); ?>" target="_blank"><?php echo esc_html( $row['name'] ); ?></a>
												<?php if ( $row['manual'] ) : ?><span class="olicg-tag"><?php esc_html_e( 'Manual', 'oli-catalog-generator' ); ?></span><?php endif; ?>
												<button type="button" class="olicg-row-remove" title="<?php esc_attr_e( 'Remove from catalogue', 'oli-catalog-generator' ); ?>" aria-label="<?php esc_attr_e( 'Remove from catalogue', 'oli-catalog-generator' ); ?>">×</button>
											</td>
											<td><code><?php echo esc_html( $row['sku'] ); ?></code></td>
											<?php foreach ( array( 'ca_retail', 'ca_dealer', 'us_retail', 'us_dealer' ) as $col ) : ?>
												<td class="olicg-price"><?php echo $row[ $col ] ? esc_html( $row[ $col ] ) : '<span class="olicg-missing">—</span>'; ?></td>
											<?php endforeach; ?>
										</tr>
									<?php endforeach; ?>
								</tbody>
							<?php endforeach; ?>
						</table>
					<?php endif; ?>
				</div>
			</form>
		<?php
	}

	/**
	 * Where dealer costs, UPC and brand are read: ACF fields or any custom field.
	 */
	private static function render_sources( array $settings ) {
		$acf_fields   = OLICG_Acf::product_fields();
		$placeholders = array(
			'cost_ca' => __( 'None — no dealer cost', 'oli-catalog-generator' ),
			'cost_us' => __( 'None — no dealer cost', 'oli-catalog-generator' ),
			'upc'     => __( 'Automatic — WooCommerce GTIN, then common barcode fields', 'oli-catalog-generator' ),
			'brand'   => __( 'Automatic — Brands taxonomy or “brand” attribute', 'oli-catalog-generator' ),
		);
		?>
		<details class="olicg-choice olicg-sources"<?php echo OLICG_Catalog::default_fields() !== $settings['fields'] ? ' open' : ''; ?>>
			<summary><strong><?php esc_html_e( 'Data sources (ACF / custom fields)', 'oli-catalog-generator' ); ?></strong></summary>
			<p class="description">
				<?php
				echo esc_html(
					OLICG_Acf::is_active()
						? __( 'Type or pick the ACF field name (products or variations) or any custom field key. ACF fields of your product field groups are suggested.', 'oli-catalog-generator' )
						: __( 'Type the custom field key (meta key) used on products and variations. With Advanced Custom Fields active, your product fields are suggested here.', 'oli-catalog-generator' )
				);
				?>
			</p>
			<?php foreach ( OLICG_Catalog::field_labels() as $key => $label ) : ?>
				<label><?php echo esc_html( $label ); ?><br>
					<input type="text" class="regular-text code" name="olicg_fields[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $settings['fields'][ $key ] ); ?>" placeholder="<?php echo esc_attr( $placeholders[ $key ] ); ?>" list="olicg-product-fields" autocomplete="off" spellcheck="false">
				</label>
			<?php endforeach; ?>
			<datalist id="olicg-product-fields">
				<?php foreach ( $acf_fields as $name => $field_label ) : ?>
					<option value="<?php echo esc_attr( $name ); ?>" label="<?php echo esc_attr( $field_label ); ?>"></option>
				<?php endforeach; ?>
				<?php foreach ( array_diff( array( '_dealer_cost_cad', '_dealer_cost_usd', '_gtin', '_upc', '_ean' ), array_keys( $acf_fields ) ) as $name ) : ?>
					<option value="<?php echo esc_attr( $name ); ?>"></option>
				<?php endforeach; ?>
			</datalist>
		</details>
		<?php
	}

	private static function product_rows( array $ids, array $settings, array $cat_ids, array $added, array $excluded ) {
		$selected = array_map( 'intval', $settings['categories'] );
		$rows     = array();

		foreach ( $ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}
			$manual = in_array( $product_id, $added, true ) && ! in_array( $product_id, $cat_ids, true );
			$term   = OLICG_Catalog::section_term( $product_id, $selected );
			$row    = array(
				'id'       => $product_id,
				'name'     => $product->get_name(),
				'sku'      => $product->get_sku(),
				'thumb'    => OLICG_Catalog::image_url( $product, 'thumbnail' ),
				'section'  => $term ? $term->name : __( 'Other products', 'oli-catalog-generator' ),
				'manual'   => $manual,
				'included' => $manual || in_array( $product_id, $added, true ) || ! in_array( $product_id, $excluded, true ),
				'menu'     => $product->get_menu_order(),
				'price'    => 'price' === $settings['sort'] ? OLICG_Catalog::sort_price( OLICG_Pricing::get_prices( $product, $settings['region'], $settings['prices'] ) ) : null,
			);
			foreach ( array( 'ca', 'us' ) as $region ) {
				foreach ( array( 'retail', 'dealer' ) as $type ) {
					$row[ $region . '_' . $type ] = OLICG_Pricing::format( OLICG_Pricing::get_price( $product, $region, $type ), $region );
				}
			}
			$rows[] = $row;
		}

		return $rows;
	}

	private static function group_rows( array $rows, array $order, $sort ) {
		$grouped = array();
		foreach ( $rows as $row ) {
			$grouped[ $row['section'] ][] = $row;
		}
		ksort( $grouped, SORT_NATURAL | SORT_FLAG_CASE );
		foreach ( $grouped as &$section_rows ) {
			OLICG_Catalog::sort_items( $section_rows, $order, $sort );
		}
		unset( $section_rows );
		return $grouped;
	}
}
