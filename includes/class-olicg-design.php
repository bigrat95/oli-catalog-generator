<?php
/**
 * Shared look for the catalogue and the product PDF sheet: fonts, colours, custom CSS.
 */

defined( 'ABSPATH' ) || exit;

class OLICG_Design {

	const OPTION = 'olicg_design';

	public static function defaults() {
		return array(
			'font_source'      => 'google',
			'font_css_url'     => '',
			'font_heading'     => 'Playfair Display',
			'font_body'        => 'Poppins',
			'font_mono'        => 'Space Mono',
			'font_pdf'         => 'Space Grotesk',
			'heading_italic'   => 1,
			'heading_weight'   => 400,
			'uppercase_labels' => 1,
			'image_bg'         => 'blend',
			'image_shadow'     => 'none',
			'color_text'       => '#09090b',
			'color_muted'      => '#71717a',
			'color_line'       => '#e4e4e7',
			'color_tile'       => '#f4f4f2',
			'color_price'      => '#09090b',
			'color_band'       => '#09090b',
			'color_band_text'  => '#ffffff',
			'color_page'       => '#ffffff',
			'custom_css'       => '',
		);
	}

	public static function font_sources() {
		return array(
			'google' => __( 'Google Fonts — loaded automatically from the font names', 'oli-catalog-generator' ),
			'url'    => __( 'Stylesheet URL — Adobe Fonts kit, self-hosted @font-face CSS, your theme’s font file…', 'oli-catalog-generator' ),
			'system' => __( 'Installed / system fonts only (nothing is loaded)', 'oli-catalog-generator' ),
		);
	}

	public static function image_backgrounds() {
		return array(
			'blend' => __( 'Blend — the image melts into the background colour (best for photos on white)', 'oli-catalog-generator' ),
			'color' => __( 'Solid background colour behind the image (best for transparent PNGs)', 'oli-catalog-generator' ),
			'none'  => __( 'No background — the image as is', 'oli-catalog-generator' ),
		);
	}

	public static function image_shadows() {
		return array(
			'none'   => __( 'No shadow', 'oli-catalog-generator' ),
			'soft'   => __( 'Soft drop shadow', 'oli-catalog-generator' ),
			'strong' => __( 'Strong drop shadow', 'oli-catalog-generator' ),
		);
	}

	public static function colors() {
		return array(
			'color_text'      => __( 'Text', 'oli-catalog-generator' ),
			'color_muted'     => __( 'Secondary text (SKU, labels)', 'oli-catalog-generator' ),
			'color_line'      => __( 'Borders', 'oli-catalog-generator' ),
			'color_tile'      => __( 'Image background', 'oli-catalog-generator' ),
			'color_price'     => __( 'Prices', 'oli-catalog-generator' ),
			'color_band'      => __( 'Cover band & PDF footer bar', 'oli-catalog-generator' ),
			'color_band_text' => __( 'Cover band & PDF footer text', 'oli-catalog-generator' ),
			'color_page'      => __( 'Catalogue page background', 'oli-catalog-generator' ),
		);
	}

	public static function get_settings() {
		$saved = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
	}

	/**
	 * Font family names: letters, digits, spaces and hyphens only.
	 */
	public static function clean_family( $family ) {
		return trim( preg_replace( '/\s+/', ' ', preg_replace( '/[^\p{L}\p{N} \-]/u', '', (string) $family ) ) );
	}

	public static function stack( $family, $fallback ) {
		$family = self::clean_family( $family );
		return '' !== $family ? "'" . $family . "', " . $fallback : $fallback;
	}

	public static function stacks( ?array $d = null ) {
		$d = $d ? $d : self::get_settings();
		return array(
			'serif' => self::stack( $d['font_heading'], "Georgia, 'Times New Roman', serif" ),
			'sans'  => self::stack( $d['font_body'], "'Helvetica Neue', Arial, sans-serif" ),
			'mono'  => self::stack( $d['font_mono'], 'ui-monospace, Menlo, Consolas, monospace' ),
			'pdf'   => self::stack( $d['font_pdf'], "'Segoe UI', Arial, sans-serif" ),
		);
	}

	/**
	 * Stylesheets to load for the chosen fonts.
	 *
	 * @param string[] $roles Any of heading, body, mono, pdf.
	 */
	public static function font_urls( array $roles, ?array $d = null ) {
		$d = $d ? $d : self::get_settings();

		if ( 'url' === $d['font_source'] ) {
			return $d['font_css_url'] ? array( $d['font_css_url'] ) : array();
		}
		if ( 'google' !== $d['font_source'] ) {
			return array();
		}

		$families = array();
		foreach ( $roles as $role ) {
			$family = self::clean_family( isset( $d[ 'font_' . $role ] ) ? $d[ 'font_' . $role ] : '' );
			if ( '' !== $family ) {
				$families[ strtolower( $family ) ] = str_replace( ' ', '+', $family ) . ':300,400,400i,500,500i,600,600i,700,700i';
			}
		}
		// The v1 API skips weights a family doesn't have; css2 rejects the whole request.
		return $families ? array( 'https://fonts.googleapis.com/css?family=' . implode( '|', $families ) . '&display=swap' ) : array();
	}

	public static function css_vars( ?array $d = null ) {
		$d      = $d ? $d : self::get_settings();
		$stacks = self::stacks( $d );
		$vars   = array(
			'--ink'            => $d['color_text'],
			'--muted'          => $d['color_muted'],
			'--line'           => $d['color_line'],
			'--tile'           => $d['color_tile'],
			'--price'          => $d['color_price'],
			'--band'           => $d['color_band'],
			'--band-text'      => $d['color_band_text'],
			'--page'           => $d['color_page'],
			'--serif'          => $stacks['serif'],
			'--sans'           => $stacks['sans'],
			'--mono'           => $stacks['mono'],
			'--heading-style'  => $d['heading_italic'] ? 'italic' : 'normal',
			'--heading-weight' => (int) $d['heading_weight'],
			'--label-case'     => $d['uppercase_labels'] ? 'uppercase' : 'none',
			'--tile-bg'        => 'none' === $d['image_bg'] ? 'transparent' : $d['color_tile'],
			'--img-blend'      => 'blend' === $d['image_bg'] ? 'multiply' : 'normal',
			'--img-shadow'     => self::shadow_filter( $d['image_shadow'] ),
		);
		$out = '';
		foreach ( $vars as $name => $value ) {
			$out .= "\t" . $name . ': ' . $value . ";\n";
		}
		return $out;
	}

	public static function shadow_filter( $shadow ) {
		switch ( $shadow ) {
			case 'soft':
				return 'drop-shadow(0 3px 4px rgba(0, 0, 0, .22))';
			case 'strong':
				return 'drop-shadow(0 6px 8px rgba(0, 0, 0, .4))';
			default:
				return 'none';
		}
	}

	public static function custom_css( ?array $d = null ) {
		$d = $d ? $d : self::get_settings();
		return str_ireplace( '</style', '', wp_strip_all_tags( (string) $d['custom_css'] ) );
	}

	public static function handle_save() {
		if ( ! current_user_can( OLICG_Admin::cap() ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'oli-catalog-generator' ), 403 );
		}
		check_admin_referer( 'olicg_save_design' );

		$defaults = self::defaults();

		if ( isset( $_POST['olicg_design_reset'] ) ) {
			delete_option( self::OPTION );
			wp_safe_redirect( add_query_arg( array( 'page' => OLICG_Admin::SLUG, 'tab' => 'design', 'saved' => 1 ), admin_url( 'admin.php' ) ) );
			exit;
		}

		$post   = static function ( $key ) {
			return isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized per field below.
		};
		$source = sanitize_key( $post( 'olicg_font_source' ) );
		$weight = absint( $post( 'olicg_heading_weight' ) );
		$img_bg = sanitize_key( $post( 'olicg_image_bg' ) );
		$shadow = sanitize_key( $post( 'olicg_image_shadow' ) );

		$design = array(
			'font_source'      => isset( self::font_sources()[ $source ] ) ? $source : 'google',
			'font_css_url'     => esc_url_raw( $post( 'olicg_font_css_url' ) ),
			'heading_italic'   => '' === $post( 'olicg_heading_italic' ) ? 0 : 1,
			'heading_weight'   => in_array( $weight, array( 300, 400, 500, 600, 700, 800 ), true ) ? $weight : 400,
			'uppercase_labels' => '' === $post( 'olicg_uppercase_labels' ) ? 0 : 1,
			'image_bg'         => isset( self::image_backgrounds()[ $img_bg ] ) ? $img_bg : 'blend',
			'image_shadow'     => isset( self::image_shadows()[ $shadow ] ) ? $shadow : 'none',
			'custom_css'       => wp_strip_all_tags( (string) $post( 'olicg_custom_css' ) ),
		);
		foreach ( array( 'heading', 'body', 'mono', 'pdf' ) as $role ) {
			$design[ 'font_' . $role ] = self::clean_family( sanitize_text_field( $post( 'olicg_font_' . $role ) ) );
		}
		foreach ( array_keys( self::colors() ) as $key ) {
			$color          = sanitize_hex_color( $post( 'olicg_' . $key ) );
			$design[ $key ] = $color ? $color : $defaults[ $key ];
		}

		update_option( self::OPTION, $design, false );

		wp_safe_redirect( add_query_arg( array( 'page' => OLICG_Admin::SLUG, 'tab' => 'design', 'saved' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function render_settings() {
		$d = self::get_settings();
		?>
		<p class="olicg-intro"><?php esc_html_e( 'Match the catalogue and the product PDF sheet to your brand. Use the exact font names from your site (Appearance → Customize / Site Editor, or your theme’s style guide).', 'oli-catalog-generator' ); ?></p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="olicg_save_design">
			<?php wp_nonce_field( 'olicg_save_design' ); ?>

			<div class="olicg-grid">
				<div class="olicg-card">
					<h2><?php esc_html_e( 'Fonts', 'oli-catalog-generator' ); ?></h2>

					<p>
						<label for="olicg_font_heading"><strong><?php esc_html_e( 'Headings (catalogue title, categories)', 'oli-catalog-generator' ); ?></strong></label><br>
						<input type="text" id="olicg_font_heading" name="olicg_font_heading" class="regular-text" value="<?php echo esc_attr( $d['font_heading'] ); ?>" placeholder="Playfair Display">
					</p>
					<p>
						<label for="olicg_font_body"><strong><?php esc_html_e( 'Body (product names)', 'oli-catalog-generator' ); ?></strong></label><br>
						<input type="text" id="olicg_font_body" name="olicg_font_body" class="regular-text" value="<?php echo esc_attr( $d['font_body'] ); ?>" placeholder="Poppins">
					</p>
					<p>
						<label for="olicg_font_mono"><strong><?php esc_html_e( 'Labels & prices (SKU, brand, prices)', 'oli-catalog-generator' ); ?></strong></label><br>
						<input type="text" id="olicg_font_mono" name="olicg_font_mono" class="regular-text" value="<?php echo esc_attr( $d['font_mono'] ); ?>" placeholder="Space Mono">
					</p>
					<p>
						<label for="olicg_font_pdf"><strong><?php esc_html_e( 'Product PDF sheet', 'oli-catalog-generator' ); ?></strong></label><br>
						<input type="text" id="olicg_font_pdf" name="olicg_font_pdf" class="regular-text" value="<?php echo esc_attr( $d['font_pdf'] ); ?>" placeholder="Space Grotesk">
					</p>

					<fieldset class="olicg-choice">
						<legend><strong><?php esc_html_e( 'Load fonts from', 'oli-catalog-generator' ); ?></strong></legend>
						<?php foreach ( self::font_sources() as $key => $label ) : ?>
							<label><input type="radio" name="olicg_font_source" value="<?php echo esc_attr( $key ); ?>" <?php checked( $d['font_source'], $key ); ?>> <?php echo esc_html( $label ); ?></label>
						<?php endforeach; ?>
						<input type="url" name="olicg_font_css_url" class="large-text" value="<?php echo esc_attr( $d['font_css_url'] ); ?>" placeholder="https://use.typekit.net/xxxxxxx.css">
					</fieldset>

					<fieldset class="olicg-choice">
						<legend><strong><?php esc_html_e( 'Style', 'oli-catalog-generator' ); ?></strong></legend>
						<label><input type="checkbox" name="olicg_heading_italic" value="1" <?php checked( $d['heading_italic'] ); ?>> <?php esc_html_e( 'Italic headings', 'oli-catalog-generator' ); ?></label>
						<label><?php esc_html_e( 'Heading weight', 'oli-catalog-generator' ); ?>
							<select name="olicg_heading_weight">
								<?php foreach ( array( 300, 400, 500, 600, 700, 800 ) as $weight ) : ?>
									<option value="<?php echo esc_attr( $weight ); ?>" <?php selected( (int) $d['heading_weight'], $weight ); ?>><?php echo esc_html( $weight ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<label><input type="checkbox" name="olicg_uppercase_labels" value="1" <?php checked( $d['uppercase_labels'] ); ?>> <?php esc_html_e( 'Uppercase labels (SKU, brand, section labels)', 'oli-catalog-generator' ); ?></label>
					</fieldset>
				</div>

				<div class="olicg-card">
					<h2><?php esc_html_e( 'Colours', 'oli-catalog-generator' ); ?></h2>
					<div class="olicg-colors">
						<?php foreach ( self::colors() as $key => $label ) : ?>
							<p>
								<label for="olicg_<?php echo esc_attr( $key ); ?>"><strong><?php echo esc_html( $label ); ?></strong></label><br>
								<input type="text" id="olicg_<?php echo esc_attr( $key ); ?>" name="olicg_<?php echo esc_attr( $key ); ?>" class="olicg-color" value="<?php echo esc_attr( $d[ $key ] ); ?>" data-default-color="<?php echo esc_attr( self::defaults()[ $key ] ); ?>">
							</p>
						<?php endforeach; ?>
					</div>

					<fieldset class="olicg-choice">
						<legend><strong><?php esc_html_e( 'Product images', 'oli-catalog-generator' ); ?></strong></legend>
						<?php foreach ( self::image_backgrounds() as $key => $label ) : ?>
							<label><input type="radio" name="olicg_image_bg" value="<?php echo esc_attr( $key ); ?>" <?php checked( $d['image_bg'], $key ); ?>> <?php echo esc_html( $label ); ?></label>
						<?php endforeach; ?>
						<label><?php esc_html_e( 'Shadow under products', 'oli-catalog-generator' ); ?>
							<select name="olicg_image_shadow">
								<?php foreach ( self::image_shadows() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $d['image_shadow'], $key ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<p class="description"><?php esc_html_e( 'The background colour is the “Image background” colour above. Shadows follow the product’s outline on transparent PNGs; on photos with a white background they outline the whole photo, so use them with Blend off.', 'oli-catalog-generator' ); ?></p>
					</fieldset>

					<p>
						<label for="olicg_custom_css"><strong><?php esc_html_e( 'Custom CSS', 'oli-catalog-generator' ); ?></strong></label><br>
						<textarea id="olicg_custom_css" name="olicg_custom_css" class="large-text code" rows="8" placeholder=".card-title { letter-spacing: .02em; }"><?php echo esc_textarea( $d['custom_css'] ); ?></textarea>
						<span class="description"><?php esc_html_e( 'Added to the catalogue and the product PDF sheet. Catalogue classes: .cover, .section-title, .card, .card-title, .card-brand, .card-sku, .card-price, .price-row. PDF sheet: .olicg-pdf-page.', 'oli-catalog-generator' ); ?></span>
					</p>

					<div class="olicg-actions">
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Save design', 'oli-catalog-generator' ); ?></button>
						<button type="submit" class="button" name="olicg_design_reset" value="1" onclick="return confirm('<?php echo esc_js( __( 'Reset fonts and colours to the defaults?', 'oli-catalog-generator' ) ); ?>');"><?php esc_html_e( 'Reset to defaults', 'oli-catalog-generator' ); ?></button>
					</div>
				</div>
			</div>
		</form>
		<?php
	}
}
