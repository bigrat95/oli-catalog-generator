<?php
/**
 * WooCommerce → Catalog Generator screen, save handler and printable output.
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
		add_action( 'admin_post_olicg_save_design', array( 'OLICG_Design', 'handle_save' ) );
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
		wp_enqueue_script( 'wc-enhanced-select' );
		wp_enqueue_style( 'olicg-admin', OLICG_PLUGIN_URL . 'assets/admin.css', array(), OLICG_VERSION );
		wp_enqueue_script( 'olicg-admin', OLICG_PLUGIN_URL . 'assets/admin.js', array( 'jquery', 'jquery-ui-sortable', 'wc-enhanced-select', 'wp-color-picker' ), OLICG_VERSION, true );
	}

	public static function render_url( $region, $price_type ) {
		return add_query_arg(
			array(
				'action'   => 'olicg_render',
				'region'   => $region,
				'price'    => $price_type,
				'_wpnonce' => wp_create_nonce( 'olicg_render' ),
			),
			admin_url( 'admin-post.php' )
		);
	}

	public static function handle_save() {
		if ( ! current_user_can( self::cap() ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'oli-catalog-generator' ), 403 );
		}
		check_admin_referer( 'olicg_save' );

		$old      = OLICG_Catalog::get_settings();
		$regions  = OLICG_Pricing::regions();
		$types    = OLICG_Pricing::price_types();
		$ids      = static function ( $key ) {
			return isset( $_POST[ $key ] ) ? array_values( array_unique( array_filter( array_map( 'absint', (array) wp_unslash( $_POST[ $key ] ) ) ) ) ) : array();
		};

		// Only rows shown in the product table can be removed/restored; categories
		// added in this same save are not listed yet, so their products stay in.
		$listed   = $ids( 'olicg_listed' );
		$included = $ids( 'olicg_included' );
		$excluded = array_diff( array_map( 'intval', $old['excluded'] ), $listed );
		$excluded = array_values( array_unique( array_merge( $excluded, array_diff( $listed, $included ) ) ) );

		$region = isset( $_POST['olicg_region'] ) ? sanitize_key( wp_unslash( $_POST['olicg_region'] ) ) : 'ca';
		$type   = isset( $_POST['olicg_price_type'] ) ? sanitize_key( wp_unslash( $_POST['olicg_price_type'] ) ) : 'retail';
		$paper  = isset( $_POST['olicg_paper'] ) ? sanitize_key( wp_unslash( $_POST['olicg_paper'] ) ) : 'letter';
		$cols   = isset( $_POST['olicg_columns'] ) ? absint( $_POST['olicg_columns'] ) : 6;
		$layout = isset( $_POST['olicg_layout'] ) ? sanitize_key( wp_unslash( $_POST['olicg_layout'] ) ) : 'compact';

		$settings = array(
			'title'             => isset( $_POST['olicg_title'] ) ? sanitize_text_field( wp_unslash( $_POST['olicg_title'] ) ) : '',
			'categories'        => isset( $_POST['tax_input']['product_cat'] ) ? array_values( array_filter( array_map( 'absint', (array) wp_unslash( $_POST['tax_input']['product_cat'] ) ) ) ) : array(),
			'excluded'          => $excluded,
			'added'             => $ids( 'olicg_added' ),
			'order'             => empty( $_POST['olicg_order_changed'] ) ? $old['order'] : OLICG_Catalog::merge_order( $ids( 'olicg_order' ), $old['order'] ),
			'region'            => isset( $regions[ $region ] ) ? $region : 'ca',
			'price_type'        => isset( $types[ $type ] ) ? $type : 'retail',
			'layout'            => isset( OLICG_Catalog::layouts()[ $layout ] ) ? $layout : 'compact',
			'columns'           => $cols >= 2 && $cols <= 6 ? $cols : 6,
			'paper'             => in_array( $paper, array( 'letter', 'a4' ), true ) ? $paper : 'letter',
			'hide_no_price'     => empty( $_POST['olicg_hide_no_price'] ) ? 0 : 1,
			'hide_out_of_stock' => empty( $_POST['olicg_hide_out_of_stock'] ) ? 0 : 1,
			'show_sku'          => empty( $_POST['olicg_show_sku'] ) ? 0 : 1,
			'show_brand'        => empty( $_POST['olicg_show_brand'] ) ? 0 : 1,
			'section_new_page'  => empty( $_POST['olicg_section_new_page'] ) ? 0 : 1,
			'logo_url'          => isset( $_POST['olicg_logo_url'] ) ? esc_url_raw( wp_unslash( $_POST['olicg_logo_url'] ) ) : '',
		);
		if ( '' === $settings['title'] ) {
			$settings['title'] = OLICG_Catalog::defaults()['title'];
		}

		OLICG_Catalog::save_settings( $settings );

		if ( isset( $_POST['olicg_generate'] ) ) {
			wp_safe_redirect( self::render_url( $settings['region'], $settings['price_type'] ) );
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

		$settings   = OLICG_Catalog::get_settings();
		$regions    = OLICG_Pricing::regions();
		$types      = OLICG_Pricing::price_types();
		$region     = isset( $_GET['region'] ) ? sanitize_key( wp_unslash( $_GET['region'] ) ) : $settings['region'];
		$price_type = isset( $_GET['price'] ) ? sanitize_key( wp_unslash( $_GET['price'] ) ) : $settings['price_type'];
		$region     = isset( $regions[ $region ] ) ? $region : 'ca';
		$price_type = isset( $types[ $price_type ] ) ? $price_type : 'retail';

		$sections = OLICG_Catalog::get_sections( $settings, $region, $price_type );
		$currency = $regions[ $region ]['currency'];
		$logo_url = OLICG_Catalog::logo_url( $settings );

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private' );
		header( 'X-Robots-Tag: noindex, nofollow, noarchive' );
		header( 'Referrer-Policy: no-referrer' );
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'Content-Type: text/html; charset=UTF-8' );
		include OLICG_PLUGIN_DIR . 'templates/catalog.php';
		exit;
	}

	public static function render_page() {
		if ( ! current_user_can( self::cap() ) ) {
			return;
		}

		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'catalog';
		$tab  = in_array( $tab, array( 'catalog', 'pdf', 'design' ), true ) ? $tab : 'catalog';
		$base = admin_url( 'admin.php?page=' . self::SLUG );
		?>
		<div class="wrap olicg">
			<h1><?php esc_html_e( 'Oli Catalog & Product PDF', 'oli-catalog-generator' ); ?></h1>
			<nav class="nav-tab-wrapper">
				<a href="<?php echo esc_url( $base ); ?>" class="nav-tab<?php echo 'catalog' === $tab ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'Catalog', 'oli-catalog-generator' ); ?></a>
				<a href="<?php echo esc_url( $base . '&tab=pdf' ); ?>" class="nav-tab<?php echo 'pdf' === $tab ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'Product PDF', 'oli-catalog-generator' ); ?></a>
				<a href="<?php echo esc_url( $base . '&tab=design' ); ?>" class="nav-tab<?php echo 'design' === $tab ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'Design', 'oli-catalog-generator' ); ?></a>
			</nav>

			<?php if ( isset( $_GET['saved'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'oli-catalog-generator' ); ?></p></div>
			<?php endif; ?>

			<?php
			if ( 'pdf' === $tab ) {
				OLICG_Product_PDF::render_settings();
			} elseif ( 'design' === $tab ) {
				OLICG_Design::render_settings();
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
		$types    = OLICG_Pricing::price_types();
		$excluded = array_map( 'intval', $settings['excluded'] );
		$added    = array_map( 'intval', $settings['added'] );
		$cat_ids  = OLICG_Catalog::get_category_product_ids( $settings['categories'] );
		$rows     = self::product_rows( array_values( array_unique( array_merge( $cat_ids, $added ) ) ), $settings['categories'], $cat_ids, $added, $excluded );
		$order    = $settings['order'];
		$included = count( array_filter( $rows, static function ( $row ) { return $row['included']; } ) );
		?>
			<p class="olicg-intro"><?php esc_html_e( 'Pick categories, remove or add products, choose the edition and price type, then generate a print-ready catalogue (Print → Save as PDF).', 'oli-catalog-generator' ); ?></p>
			<p class="olicg-private"><?php esc_html_e( 'Private: catalogues are only generated here in the admin. They never appear on your website, and catalogue links only open for logged-in shop managers and administrators.', 'oli-catalog-generator' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="olicg_save">
				<?php wp_nonce_field( 'olicg_save' ); ?>

				<div class="olicg-grid">
					<div class="olicg-card">
						<h2><?php esc_html_e( '1. Content', 'oli-catalog-generator' ); ?></h2>

						<p>
							<label for="olicg_title"><strong><?php esc_html_e( 'Catalogue title', 'oli-catalog-generator' ); ?></strong></label><br>
							<input type="text" id="olicg_title" name="olicg_title" class="regular-text" value="<?php echo esc_attr( $settings['title'] ); ?>">
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
							<select id="olicg_added" name="olicg_added[]" class="wc-product-search" multiple="multiple" style="width:100%;" data-placeholder="<?php esc_attr_e( 'Search for a product…', 'oli-catalog-generator' ); ?>" data-action="woocommerce_json_search_products">
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

						<fieldset class="olicg-choice">
							<legend><strong><?php esc_html_e( 'Prices shown', 'oli-catalog-generator' ); ?></strong></legend>
							<?php foreach ( $types as $key => $label ) : ?>
								<label><input type="radio" name="olicg_price_type" value="<?php echo esc_attr( $key ); ?>" <?php checked( $settings['price_type'], $key ); ?>> <?php echo esc_html( $label ); ?></label>
							<?php endforeach; ?>
							<p class="description"><?php esc_html_e( 'End-user price = the lowest of the regular (list) and sale (MAP) prices. Dealer price = imported dealer cost. Cost, List & MAP = all three on each product (cost = dealer cost, list = regular price, MAP = sale price).', 'oli-catalog-generator' ); ?></p>
						</fieldset>

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
							<label><?php esc_html_e( 'Products per row (grids)', 'oli-catalog-generator' ); ?>
								<select name="olicg_columns">
									<?php foreach ( array( 2, 3, 4, 5, 6 ) as $cols ) : ?>
										<option value="<?php echo esc_attr( $cols ); ?>" <?php selected( (int) $settings['columns'], $cols ); ?>><?php echo esc_html( $cols ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<label><input type="checkbox" name="olicg_section_new_page" value="1" <?php checked( $settings['section_new_page'] ); ?>> <?php esc_html_e( 'Start each category on a new page', 'oli-catalog-generator' ); ?></label>
							<label><input type="checkbox" name="olicg_show_sku" value="1" <?php checked( $settings['show_sku'] ); ?>> <?php esc_html_e( 'Show SKU', 'oli-catalog-generator' ); ?></label>
							<label><input type="checkbox" name="olicg_show_brand" value="1" <?php checked( $settings['show_brand'] ); ?>> <?php esc_html_e( 'Show brand', 'oli-catalog-generator' ); ?></label>
							<label><input type="checkbox" name="olicg_hide_no_price" value="1" <?php checked( $settings['hide_no_price'] ); ?>> <?php esc_html_e( 'Hide products without a price for the chosen edition', 'oli-catalog-generator' ); ?></label>
							<label><input type="checkbox" name="olicg_hide_out_of_stock" value="1" <?php checked( $settings['hide_out_of_stock'] ); ?>> <?php esc_html_e( 'Hide out-of-stock products', 'oli-catalog-generator' ); ?></label>
						</fieldset>

						<p>
							<label for="olicg_logo_url"><strong><?php esc_html_e( 'Logo URL (optional)', 'oli-catalog-generator' ); ?></strong></label><br>
							<input type="url" id="olicg_logo_url" name="olicg_logo_url" class="large-text" value="<?php echo esc_attr( $settings['logo_url'] ); ?>" placeholder="<?php echo esc_attr( OLICG_Catalog::logo_url( array_merge( $settings, array( 'logo_url' => '' ) ) ) ); ?>">
							<span class="description"><?php esc_html_e( 'Leave empty to use the site logo.', 'oli-catalog-generator' ); ?></span>
						</p>

						<div class="olicg-actions">
							<button type="submit" class="button button-secondary" name="olicg_save" value="1"><?php esc_html_e( 'Save selection', 'oli-catalog-generator' ); ?></button>
							<button type="submit" class="button button-primary" name="olicg_generate" value="1" formtarget="_blank"><?php esc_html_e( 'Save & generate catalogue', 'oli-catalog-generator' ); ?></button>
						</div>

						<p class="olicg-quick"><strong><?php esc_html_e( 'Quick generate (saved selection):', 'oli-catalog-generator' ); ?></strong><br>
							<?php foreach ( $regions as $rkey => $region ) : ?>
								<?php foreach ( $types as $tkey => $label ) : ?>
									<a class="button button-small" target="_blank" href="<?php echo esc_url( self::render_url( $rkey, $tkey ) ); ?>"><?php echo esc_html( $region['currency'] . ' · ' . $label ); ?></a>
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
							<?php foreach ( self::group_rows( $rows, $order ) as $section => $section_rows ) : ?>
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

	private static function product_rows( array $ids, array $selected, array $cat_ids, array $added, array $excluded ) {
		$selected = array_map( 'intval', $selected );
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

	private static function group_rows( array $rows, array $order ) {
		$grouped = array();
		foreach ( $rows as $row ) {
			$grouped[ $row['section'] ][] = $row;
		}
		ksort( $grouped, SORT_NATURAL | SORT_FLAG_CASE );
		foreach ( $grouped as &$section_rows ) {
			OLICG_Catalog::sort_items( $section_rows, $order );
		}
		unset( $section_rows );
		return $grouped;
	}
}
