<?php
/**
 * Optional feature: "Download PDF" product sheet on single product pages.
 *
 * Renders a hidden A4 product sheet (logo, title, SKU, description, image,
 * specs, footer bar) and a button that opens it in a print window.
 */

defined( 'ABSPATH' ) || exit;

class OLICG_Product_PDF {

	const OPTION = 'olicg_pdf_settings';

	private static $rendered = array();

	public static function defaults() {
		return array(
			'enabled'      => 0,
			'placement'    => 'summary',
			'label'        => __( 'Download PDF', 'oli-catalog-generator' ),
			'button_style' => 'dark',
			'logo_url'     => '',
			'brand_logos'  => 1,
			'footer_icon'  => '',
			'footer_line1' => '',
			'footer_line2' => '',
			'footer_line3' => '',
			'disclaimer'   => __( 'All specifications subject to change without notice', 'oli-catalog-generator' ),
		);
	}

	public static function placements() {
		return array(
			'summary'          => __( 'Product page — after the product summary', 'oli-catalog-generator' ),
			'after_cart'       => __( 'Product page — after the Add to cart button', 'oli-catalog-generator' ),
			'shortcode'        => __( 'Shortcode only: [oli_product_pdf]', 'oli-catalog-generator' ),
		);
	}

	public static function button_styles() {
		return array(
			'dark'  => __( 'Dark', 'oli-catalog-generator' ),
			'light' => __( 'Light', 'oli-catalog-generator' ),
			'theme' => __( 'Theme button style', 'oli-catalog-generator' ),
		);
	}

	public static function get_settings() {
		$saved = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
	}

	public static function init() {
		add_action( 'admin_post_olicg_save_pdf', array( __CLASS__, 'handle_save' ) );

		$settings = self::get_settings();
		if ( empty( $settings['enabled'] ) ) {
			return;
		}

		add_shortcode( 'oli_product_pdf', array( __CLASS__, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		add_filter( 'rocket_delay_js_exclusions', array( __CLASS__, 'rocket_exclusions' ) );
		add_filter( 'rocket_exclude_js', array( __CLASS__, 'rocket_exclusions' ) );

		if ( 'summary' === $settings['placement'] ) {
			add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'auto_button' ), 45 );
		} elseif ( 'after_cart' === $settings['placement'] ) {
			add_action( 'woocommerce_after_add_to_cart_form', array( __CLASS__, 'auto_button' ) );
		}
	}

	public static function register_assets() {
		wp_register_style( 'olicg-product-pdf', OLICG_PLUGIN_URL . 'assets/product-pdf.css', array(), OLICG_VERSION );
		wp_register_script( 'olicg-product-pdf', OLICG_PLUGIN_URL . 'assets/product-pdf.js', array(), OLICG_VERSION, true );
		wp_localize_script( 'olicg-product-pdf', 'olicgProductPdf', array(
			'sheetLabel' => __( 'Product Sheet', 'oli-catalog-generator' ),
			'popupBlocked' => __( 'Please allow pop-ups for this site to download the PDF.', 'oli-catalog-generator' ),
		) );
	}

	public static function rocket_exclusions( $list ) {
		$list   = is_array( $list ) ? $list : array();
		$list[] = 'olicg-product-pdf';
		$list[] = 'olicgProductPdf';
		return $list;
	}

	public static function auto_button() {
		global $product;
		if ( $product instanceof WC_Product ) {
			echo self::render( $product ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in render().
		}
	}

	public static function shortcode( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0, 'label' => '' ), $atts, 'oli_product_pdf' );

		$item = $atts['id'] ? wc_get_product( absint( $atts['id'] ) ) : null;
		if ( ! $item ) {
			$item = isset( $GLOBALS['product'] ) && $GLOBALS['product'] instanceof WC_Product ? $GLOBALS['product'] : wc_get_product( get_the_ID() );
		}

		return $item instanceof WC_Product ? self::render( $item, $atts['label'] ) : '';
	}

	public static function render( WC_Product $product, $label = '' ) {
		$product_id = $product->get_id();
		if ( isset( self::$rendered[ $product_id ] ) ) {
			return self::button( $product, $label );
		}
		self::$rendered[ $product_id ] = true;

		if ( ! wp_script_is( 'olicg-product-pdf', 'registered' ) ) {
			self::register_assets();
		}
		wp_enqueue_style( 'olicg-product-pdf' );
		wp_enqueue_script( 'olicg-product-pdf' );

		$settings = self::get_settings();
		ob_start();
		include OLICG_PLUGIN_DIR . 'templates/product-pdf.php';
		return self::button( $product, $label ) . ob_get_clean();
	}

	private static function button( WC_Product $product, $label ) {
		$settings = self::get_settings();
		$label    = '' !== $label ? $label : $settings['label'];
		$classes  = 'olicg-pdf-btn olicg-pdf-btn--' . sanitize_html_class( $settings['button_style'] );
		if ( 'theme' === $settings['button_style'] ) {
			$classes .= ' button';
		}

		ob_start();
		?>
		<button type="button" class="<?php echo esc_attr( $classes ); ?>" data-olicg-pdf="<?php echo esc_attr( $product->get_id() ); ?>" data-olicg-file="<?php echo esc_attr( sanitize_file_name( $product->get_name() ) ); ?>">
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
				<polyline points="7 10 12 15 17 10"></polyline>
				<line x1="12" y1="15" x2="12" y2="3"></line>
			</svg>
			<span><?php echo esc_html( $label ); ?></span>
		</button>
		<?php
		return ob_get_clean();
	}

	/**
	 * Brand logo (WooCommerce Brands term image) → PDF logo setting → catalogue/site logo.
	 */
	public static function logo_for( WC_Product $product, array $settings ) {
		if ( ! empty( $settings['brand_logos'] ) && taxonomy_exists( 'product_brand' ) ) {
			$brands = get_the_terms( $product->get_id(), 'product_brand' );
			if ( $brands && ! is_wp_error( $brands ) ) {
				$thumb_id = (int) get_term_meta( reset( $brands )->term_id, 'thumbnail_id', true );
				$url      = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'medium' ) : '';
				if ( $url ) {
					return $url;
				}
			}
		}
		if ( ! empty( $settings['logo_url'] ) ) {
			return $settings['logo_url'];
		}
		return OLICG_Catalog::logo_url( OLICG_Catalog::get_settings() );
	}

	public static function handle_save() {
		if ( ! current_user_can( OLICG_Admin::CAP ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'oli-catalog-generator' ), 403 );
		}
		check_admin_referer( 'olicg_save_pdf' );

		$text = static function ( $key ) {
			return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
		};
		$url = static function ( $key ) {
			return isset( $_POST[ $key ] ) ? esc_url_raw( wp_unslash( $_POST[ $key ] ) ) : '';
		};

		$placement = sanitize_key( $text( 'olicg_pdf_placement' ) );
		$style     = sanitize_key( $text( 'olicg_pdf_button_style' ) );

		update_option( self::OPTION, array(
			'enabled'      => empty( $_POST['olicg_pdf_enabled'] ) ? 0 : 1,
			'placement'    => isset( self::placements()[ $placement ] ) ? $placement : 'summary',
			'label'        => '' !== $text( 'olicg_pdf_label' ) ? $text( 'olicg_pdf_label' ) : self::defaults()['label'],
			'button_style' => isset( self::button_styles()[ $style ] ) ? $style : 'dark',
			'logo_url'     => $url( 'olicg_pdf_logo_url' ),
			'brand_logos'  => empty( $_POST['olicg_pdf_brand_logos'] ) ? 0 : 1,
			'footer_icon'  => $url( 'olicg_pdf_footer_icon' ),
			'footer_line1' => $text( 'olicg_pdf_footer_line1' ),
			'footer_line2' => $text( 'olicg_pdf_footer_line2' ),
			'footer_line3' => $text( 'olicg_pdf_footer_line3' ),
			'disclaimer'   => $text( 'olicg_pdf_disclaimer' ),
		), false );

		wp_safe_redirect( add_query_arg( array( 'page' => OLICG_Admin::SLUG, 'tab' => 'pdf', 'saved' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function render_settings() {
		$s = self::get_settings();
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="olicg_save_pdf">
			<?php wp_nonce_field( 'olicg_save_pdf' ); ?>

			<div class="olicg-grid">
				<div class="olicg-card">
					<h2><?php esc_html_e( 'Product PDF download', 'oli-catalog-generator' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Adds a “Download PDF” button to product pages. Visitors get an A4 product sheet (logo, title, SKU, description, image and specifications) ready to save as PDF.', 'oli-catalog-generator' ); ?></p>

					<p><label><input type="checkbox" name="olicg_pdf_enabled" value="1" <?php checked( $s['enabled'] ); ?>> <strong><?php esc_html_e( 'Enable the product PDF download button', 'oli-catalog-generator' ); ?></strong></label></p>

					<fieldset class="olicg-choice">
						<legend><strong><?php esc_html_e( 'Button placement', 'oli-catalog-generator' ); ?></strong></legend>
						<?php foreach ( self::placements() as $key => $label ) : ?>
							<label><input type="radio" name="olicg_pdf_placement" value="<?php echo esc_attr( $key ); ?>" <?php checked( $s['placement'], $key ); ?>> <?php echo esc_html( $label ); ?></label>
						<?php endforeach; ?>
						<p class="description"><?php esc_html_e( 'The shortcode always works when the feature is enabled. Use [oli_product_pdf] in a product template, or [oli_product_pdf id="123"] anywhere. Optional: label="…".', 'oli-catalog-generator' ); ?></p>
					</fieldset>

					<p>
						<label for="olicg_pdf_label"><strong><?php esc_html_e( 'Button label', 'oli-catalog-generator' ); ?></strong></label><br>
						<input type="text" id="olicg_pdf_label" name="olicg_pdf_label" class="regular-text" value="<?php echo esc_attr( $s['label'] ); ?>">
					</p>

					<p>
						<label for="olicg_pdf_button_style"><strong><?php esc_html_e( 'Button style', 'oli-catalog-generator' ); ?></strong></label><br>
						<select id="olicg_pdf_button_style" name="olicg_pdf_button_style">
							<?php foreach ( self::button_styles() as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $s['button_style'], $key ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>
				</div>

				<div class="olicg-card">
					<h2><?php esc_html_e( 'Sheet branding', 'oli-catalog-generator' ); ?></h2>

					<p>
						<label for="olicg_pdf_logo_url"><strong><?php esc_html_e( 'Logo URL', 'oli-catalog-generator' ); ?></strong></label><br>
						<input type="url" id="olicg_pdf_logo_url" name="olicg_pdf_logo_url" class="large-text" value="<?php echo esc_attr( $s['logo_url'] ); ?>" placeholder="<?php echo esc_attr( OLICG_Catalog::logo_url( OLICG_Catalog::get_settings() ) ); ?>">
						<span class="description"><?php esc_html_e( 'Leave empty to use the catalogue / site logo.', 'oli-catalog-generator' ); ?></span>
					</p>
					<p><label><input type="checkbox" name="olicg_pdf_brand_logos" value="1" <?php checked( $s['brand_logos'] ); ?>> <?php esc_html_e( 'Use the product’s brand image (Products → Brands) when it has one', 'oli-catalog-generator' ); ?></label></p>

					<fieldset class="olicg-choice">
						<legend><strong><?php esc_html_e( 'Footer bar (left side)', 'oli-catalog-generator' ); ?></strong></legend>
						<label><?php esc_html_e( 'Icon URL', 'oli-catalog-generator' ); ?><br><input type="url" name="olicg_pdf_footer_icon" class="large-text" value="<?php echo esc_attr( $s['footer_icon'] ); ?>" placeholder="https://…/icon.svg"></label>
						<label><?php esc_html_e( 'Line 1 (bold)', 'oli-catalog-generator' ); ?><br><input type="text" name="olicg_pdf_footer_line1" class="regular-text" value="<?php echo esc_attr( $s['footer_line1'] ); ?>" placeholder="MADE IN"></label>
						<label><?php esc_html_e( 'Line 2 (bold)', 'oli-catalog-generator' ); ?><br><input type="text" name="olicg_pdf_footer_line2" class="regular-text" value="<?php echo esc_attr( $s['footer_line2'] ); ?>" placeholder="CANADA"></label>
						<label><?php esc_html_e( 'Line 3 (small)', 'oli-catalog-generator' ); ?><br><input type="text" name="olicg_pdf_footer_line3" class="regular-text" value="<?php echo esc_attr( $s['footer_line3'] ); ?>" placeholder="Since 1972"></label>
						<p class="description"><?php esc_html_e( 'Leave all empty to show the site name.', 'oli-catalog-generator' ); ?></p>
					</fieldset>

					<p>
						<label for="olicg_pdf_disclaimer"><strong><?php esc_html_e( 'Footer disclaimer (right side)', 'oli-catalog-generator' ); ?></strong></label><br>
						<input type="text" id="olicg_pdf_disclaimer" name="olicg_pdf_disclaimer" class="large-text" value="<?php echo esc_attr( $s['disclaimer'] ); ?>">
					</p>

					<div class="olicg-actions">
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Save settings', 'oli-catalog-generator' ); ?></button>
					</div>
				</div>
			</div>
		</form>
		<?php
	}
}
