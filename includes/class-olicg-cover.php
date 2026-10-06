<?php
/**
 * Catalogue cover page: designed cover (editable texts, logo, colours, background)
 * or one full-page image.
 *
 * @package OliCatalogGenerator
 */

defined( 'ABSPATH' ) || exit;

class OLICG_Cover {

	const OPTION = 'olicg_cover';

	public static function defaults() {
		return array(
			'mode'         => 'design',
			'image_url'    => '',
			'image_fit'    => 'cover',
			'show_logo'    => 1,
			'logo_url'     => '',
			'logo_acf'     => 'site_logo',
			'logo_height'  => 54,
			'bg_color'     => '',
			'bg_image_url' => '',
			'bg_overlay'   => 0,
			'text_color'   => '',
			'muted_color'  => '',
			'title_font'   => '',
			'title_size'   => 58,
			'show_meta'    => 1,
			'show_eyebrow' => 1,
			'show_band'    => 1,
			'show_note'    => 1,
			'page_numbers' => 1,
			'page_position' => 'right',
			'count_cover'  => 1,
			'footer_show'  => 1,
			'footer_size'  => 7,
			'closing_show' => 1,
			'texts'        => array(),
		);
	}

	public static function page_positions() {
		return array(
			'right'  => __( 'Bottom right', 'oli-catalog-generator' ),
			'center' => __( 'Bottom centre', 'oli-catalog-generator' ),
			'left'   => __( 'Bottom left', 'oli-catalog-generator' ),
		);
	}

	public static function modes() {
		return array(
			'design' => __( 'Designed cover — logo, title, information band (all editable below)', 'oli-catalog-generator' ),
			'image'  => __( 'Full-page image — your own cover artwork replaces the whole cover', 'oli-catalog-generator' ),
			'none'   => __( 'No cover — the catalogue starts with the first category', 'oli-catalog-generator' ),
		);
	}

	/**
	 * Editable cover texts. Empty = the automatic text shown as placeholder.
	 *
	 * @return array key => { label, multiline }
	 */
	public static function text_fields() {
		return array(
			'wordmark'     => array( 'label' => __( 'Company name (shown when there is no logo)', 'oli-catalog-generator' ), 'multiline' => false ),
			'meta1'        => array( 'label' => __( 'Top right — line 1', 'oli-catalog-generator' ), 'multiline' => false ),
			'meta2'        => array( 'label' => __( 'Top right — line 2', 'oli-catalog-generator' ), 'multiline' => false ),
			'eyebrow'      => array( 'label' => __( 'Small line above the title', 'oli-catalog-generator' ), 'multiline' => false ),
			'title'        => array( 'label' => __( 'Title', 'oli-catalog-generator' ), 'multiline' => false ),
			'subtitle'     => array( 'label' => __( 'Second title line', 'oli-catalog-generator' ), 'multiline' => false ),
			'band1_label'  => array( 'label' => __( 'Band — box 1 label', 'oli-catalog-generator' ), 'multiline' => false ),
			'band1_value'  => array( 'label' => __( 'Band — box 1 value', 'oli-catalog-generator' ), 'multiline' => false ),
			'band2_label'  => array( 'label' => __( 'Band — box 2 label', 'oli-catalog-generator' ), 'multiline' => false ),
			'band2_value'  => array( 'label' => __( 'Band — box 2 value', 'oli-catalog-generator' ), 'multiline' => false ),
			'band3_label'  => array( 'label' => __( 'Band — box 3 label', 'oli-catalog-generator' ), 'multiline' => false ),
			'band3_value'  => array( 'label' => __( 'Band — box 3 value', 'oli-catalog-generator' ), 'multiline' => false ),
			'note'         => array( 'label' => __( 'Note under the band', 'oli-catalog-generator' ), 'multiline' => true ),
			'footer'       => array( 'label' => __( 'Footer text (bottom of every page)', 'oli-catalog-generator' ), 'multiline' => false, 'group' => 'pages' ),
			'page'         => array( 'label' => __( 'Page number', 'oli-catalog-generator' ), 'multiline' => false, 'group' => 'pages' ),
			'closing'      => array( 'label' => __( 'Closing text (end of the catalogue)', 'oli-catalog-generator' ), 'multiline' => true, 'group' => 'pages' ),
		);
	}

	/**
	 * Automatic texts, as shown in the admin's language. {tokens} are filled when the catalogue renders.
	 */
	public static function default_texts() {
		return array(
			'wordmark'    => '{site}',
			'meta1'       => '{domain}',
			'meta2'       => '{date}',
			'eyebrow'     => '{edition_label} · {market}',
			'title'       => '{title}',
			'subtitle'    => '{year}',
			'band1_label' => __( 'Market', 'oli-catalog-generator' ),
			'band1_value' => '{market}',
			'band2_label' => __( 'Prices', 'oli-catalog-generator' ),
			'band2_value' => '{prices} · {currency}',
			'band3_label' => __( 'Products', 'oli-catalog-generator' ),
			'band3_value' => '{count}',
			'note'        => '{note}',
			'footer'      => '{site} — {title} {year} · {market} · {currency}',
			'page'        => '{page}',
			'closing'     => '{closing}',
		);
	}

	public static function tokens() {
		return array(
			'{title}'         => __( 'catalogue title (Catalog tab)', 'oli-catalog-generator' ),
			'{year}'          => __( 'current year', 'oli-catalog-generator' ),
			'{date}'          => __( 'today’s date', 'oli-catalog-generator' ),
			'{site}'          => __( 'site name', 'oli-catalog-generator' ),
			'{domain}'        => __( 'site address', 'oli-catalog-generator' ),
			'{market}'        => __( 'Canada / United States', 'oli-catalog-generator' ),
			'{currency}'      => __( 'CAD / USD', 'oli-catalog-generator' ),
			'{prices}'        => __( 'price labels shown, e.g. Cost · List · MAP', 'oli-catalog-generator' ),
			'{edition_label}' => __( 'Dealer price list / Suggested retail prices / Product catalogue', 'oli-catalog-generator' ),
			'{count}'         => __( 'number of products', 'oli-catalog-generator' ),
			'{note}'          => __( 'automatic note (confidential dealer pricing…)', 'oli-catalog-generator' ),
			'{closing}'       => __( 'automatic closing text (© year, prices current as of…)', 'oli-catalog-generator' ),
			'{page}'          => __( 'page number (footer and page number only)', 'oli-catalog-generator' ),
			'{pages}'         => __( 'total number of pages, cover included (footer and page number only)', 'oli-catalog-generator' ),
		);
	}

	public static function get_settings() {
		$saved = get_option( self::OPTION, array() );
		$saved = is_array( $saved ) ? $saved : array();
		if ( ! isset( $saved['logo_url'] ) ) {
			// The logo used to be set in the Catalog tab.
			$catalog           = get_option( OLICG_Catalog::OPTION, array() );
			$saved['logo_url'] = is_array( $catalog ) && ! empty( $catalog['logo_url'] ) ? $catalog['logo_url'] : '';
		}
		$s = wp_parse_args( $saved, self::defaults() );
		$s['texts'] = is_array( $s['texts'] ) ? array_intersect_key( $s['texts'], self::text_fields() ) : array();
		return $s;
	}

	/**
	 * Cover texts in the language being rendered ('' = current language). Tokens stay unfilled.
	 *
	 * @return string[] key => text
	 */
	public static function texts( array $s, $lang = '' ) {
		$texts  = self::default_texts();
		$custom = array_filter( array_map( 'strval', $s['texts'] ), 'strlen' );
		foreach ( $custom as $key => $value ) {
			$custom[ $key ] = OLICG_I18n::translate_string( 'Cover: ' . $key, $value, '' !== $lang ? $lang : null );
		}
		if ( $custom && '' !== $lang ) {
			$custom = array_combine( array_keys( $custom ), OLICG_I18n::translate_strings( array_values( $custom ), $lang ) );
		}
		return array_merge( $texts, $custom );
	}

	/**
	 * Escaped HTML with {tokens} filled. {count} stays live (updated when products are removed).
	 */
	public static function fill( $text, array $values ) {
		if ( '-' === trim( $text ) ) {
			return '';
		}
		$html = nl2br( esc_html( $text ) );
		foreach ( $values as $token => $value ) {
			$html = str_replace( esc_html( $token ), '{count}' === $token ? '<span class="js-total">' . esc_html( $value ) . '</span>' : esc_html( $value ), $html );
		}
		return $html;
	}

	/**
	 * Plain text with {tokens} filled ({page} / {pages} are left for css_content()).
	 */
	public static function fill_plain( $text, array $values ) {
		if ( '-' === trim( $text ) ) {
			return '';
		}
		$text = strtr( $text, array_map( 'strval', $values ) );
		return trim( preg_replace( '/\s+/', ' ', html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES, 'UTF-8' ) ) );
	}

	/**
	 * CSS `content` value for a page margin box: quoted text plus page counters.
	 */
	public static function css_content( $text ) {
		$parts = preg_split( '/(\{pages?\})/', (string) $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
		$out   = array();
		foreach ( $parts as $part ) {
			if ( '{page}' === $part ) {
				$out[] = 'counter(page)';
			} elseif ( '{pages}' === $part ) {
				$out[] = 'counter(pages)';
			} else {
				$out[] = '"' . str_replace( array( '\\', '"', '<', '>' ), array( '\\\\', '\\"', '', '' ), $part ) . '"';
			}
		}
		return $out ? implode( ' ', $out ) : 'none';
	}

	public static function register_strings() {
		foreach ( self::get_settings()['texts'] as $key => $value ) {
			OLICG_I18n::register_string( 'Cover: ' . $key, $value );
		}
	}

	/**
	 * CSS custom properties for the cover element.
	 */
	public static function style( array $s ) {
		$vars = array();
		if ( $s['bg_color'] ) {
			$vars[] = '--cover-bg: ' . $s['bg_color'];
		}
		if ( $s['text_color'] ) {
			$vars[] = '--cover-text: ' . $s['text_color'];
		}
		if ( $s['muted_color'] ) {
			$vars[] = '--cover-muted: ' . $s['muted_color'];
		}
		if ( $s['title_font'] ) {
			$vars[] = '--cover-title-font: ' . OLICG_Design::stack( $s['title_font'], 'var(--serif)' );
		}
		$vars[] = '--cover-title-size: ' . (int) $s['title_size'] . 'pt';
		$vars[] = '--cover-logo-h: ' . (int) $s['logo_height'] . 'px';
		$vars[] = '--cover-overlay: ' . round( (int) $s['bg_overlay'] / 100, 2 );
		if ( $s['bg_image_url'] && 'design' === $s['mode'] ) {
			$vars[] = "background-image: url('" . str_replace( array( "'", '(', ')', ' ' ), array( '%27', '%28', '%29', '%20' ), esc_url_raw( $s['bg_image_url'] ) ) . "')";
		}
		return implode( '; ', $vars ) . ';';
	}

	/**
	 * Full-bleed cover (no page margins): an image, a background colour or a background image.
	 */
	public static function is_bleed( array $s ) {
		return 'image' === $s['mode'] || $s['bg_color'] || $s['bg_image_url'];
	}

	public static function handle_save() {
		if ( ! current_user_can( OLICG_Admin::cap() ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'oli-catalog-generator' ), 403 );
		}
		check_admin_referer( 'olicg_save_cover' );

		if ( isset( $_POST['olicg_cover_reset'] ) ) {
			delete_option( self::OPTION );
		} else {
			$post = static function ( $key ) {
				return isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing -- sanitized per field below; nonce checked above.
			};
			$mode     = sanitize_key( $post( 'olicg_cover_mode' ) );
			$fit      = sanitize_key( $post( 'olicg_cover_image_fit' ) );
			$position = sanitize_key( $post( 'olicg_cover_page_position' ) );
			$defaults = self::default_texts();
			$texts    = array();
			$posted   = isset( $_POST['olicg_cover_texts'] ) && is_array( $_POST['olicg_cover_texts'] ) ? wp_unslash( $_POST['olicg_cover_texts'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized below.
			foreach ( self::text_fields() as $key => $field ) {
				$value = isset( $posted[ $key ] ) && is_scalar( $posted[ $key ] ) ? (string) $posted[ $key ] : '';
				$value = trim( $field['multiline'] ? sanitize_textarea_field( $value ) : sanitize_text_field( $value ) );
				if ( '' !== $value && $value !== $defaults[ $key ] ) {
					$texts[ $key ] = $value;
				}
			}

			update_option( self::OPTION, array(
				'mode'         => isset( self::modes()[ $mode ] ) ? $mode : 'design',
				'image_url'    => esc_url_raw( $post( 'olicg_cover_image_url' ) ),
				'image_fit'    => 'contain' === $fit ? 'contain' : 'cover',
				'show_logo'    => '' === $post( 'olicg_cover_show_logo' ) ? 0 : 1,
				'logo_url'     => esc_url_raw( $post( 'olicg_cover_logo_url' ) ),
				'logo_acf'     => OLICG_Acf::sanitize_name( $post( 'olicg_cover_logo_acf' ) ),
				'logo_height'  => max( 20, min( 200, absint( $post( 'olicg_cover_logo_height' ) ) ) ),
				'bg_color'     => (string) sanitize_hex_color( $post( 'olicg_cover_bg_color' ) ),
				'bg_image_url' => esc_url_raw( $post( 'olicg_cover_bg_image_url' ) ),
				'bg_overlay'   => max( 0, min( 90, absint( $post( 'olicg_cover_bg_overlay' ) ) ) ),
				'text_color'   => (string) sanitize_hex_color( $post( 'olicg_cover_text_color' ) ),
				'muted_color'  => (string) sanitize_hex_color( $post( 'olicg_cover_muted_color' ) ),
				'title_font'   => OLICG_Design::clean_family( sanitize_text_field( $post( 'olicg_cover_title_font' ) ) ),
				'title_size'   => max( 16, min( 120, absint( $post( 'olicg_cover_title_size' ) ) ) ),
				'show_meta'    => '' === $post( 'olicg_cover_show_meta' ) ? 0 : 1,
				'show_eyebrow' => '' === $post( 'olicg_cover_show_eyebrow' ) ? 0 : 1,
				'show_band'    => '' === $post( 'olicg_cover_show_band' ) ? 0 : 1,
				'show_note'    => '' === $post( 'olicg_cover_show_note' ) ? 0 : 1,
				'page_numbers' => '' === $post( 'olicg_cover_page_numbers' ) ? 0 : 1,
				'page_position' => isset( self::page_positions()[ $position ] ) ? $position : 'right',
				'count_cover'  => '' === $post( 'olicg_cover_count_cover' ) ? 0 : 1,
				'footer_show'  => '' === $post( 'olicg_cover_footer_show' ) ? 0 : 1,
				'footer_size'  => max( 5, min( 14, round( (float) $post( 'olicg_cover_footer_size' ) * 2 ) / 2 ) ),
				'closing_show' => '' === $post( 'olicg_cover_closing_show' ) ? 0 : 1,
				'texts'        => $texts,
			), false );
			self::register_strings();
		}

		wp_safe_redirect( add_query_arg( array( 'page' => OLICG_Admin::SLUG, 'tab' => 'cover', 'saved' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	private static function media_field( $name, $value, $label, $description = '' ) {
		?>
		<div class="olicg-media">
			<label for="<?php echo esc_attr( $name ); ?>"><strong><?php echo esc_html( $label ); ?></strong></label>
			<div class="olicg-media-row">
				<input type="url" id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" class="large-text olicg-media-url" value="<?php echo esc_attr( $value ); ?>">
				<button type="button" class="button olicg-media-pick"><?php esc_html_e( 'Choose image', 'oli-catalog-generator' ); ?></button>
				<button type="button" class="button-link olicg-media-clear"><?php esc_html_e( 'Remove', 'oli-catalog-generator' ); ?></button>
			</div>
			<img class="olicg-media-preview" src="<?php echo esc_url( $value ); ?>" alt=""<?php echo $value ? '' : ' hidden'; ?>>
			<?php if ( $description ) : ?>
				<p class="description"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Site logo source when no logo is chosen above: an ACF options field, else the theme's custom logo.
	 */
	private static function logo_source_field( array $s ) {
		$fields = OLICG_Acf::option_image_fields();
		if ( '' !== $s['logo_acf'] && ! isset( $fields[ $s['logo_acf'] ] ) ) {
			$fields[ $s['logo_acf'] ] = $s['logo_acf'];
		}
		?>
		<p>
			<label for="olicg_cover_logo_acf"><strong><?php esc_html_e( 'Site logo from ACF', 'oli-catalog-generator' ); ?></strong></label><br>
			<select id="olicg_cover_logo_acf" name="olicg_cover_logo_acf">
				<option value="" <?php selected( $s['logo_acf'], '' ); ?>><?php esc_html_e( 'None — use the theme logo (Customizer / Site Editor)', 'oli-catalog-generator' ); ?></option>
				<?php foreach ( $fields as $name => $label ) : ?>
					<option value="<?php echo esc_attr( $name ); ?>" <?php selected( $s['logo_acf'], $name ); ?>><?php echo esc_html( $label === $name ? $name : $label . ' (' . $name . ')' ); ?></option>
				<?php endforeach; ?>
			</select>
			<br><span class="description">
				<?php
				echo esc_html(
					OLICG_Acf::is_active()
						? __( 'Image or URL field of an ACF options page, used when the Logo field above is empty (also for the product PDF sheet). Falls back to the theme logo.', 'oli-catalog-generator' )
						: __( 'Advanced Custom Fields is not active: the theme logo is used. With ACF, pick the logo field of your options page here.', 'oli-catalog-generator' )
				);
				?>
			</span>
		</p>
		<?php
	}

	private static function text_inputs( array $s, $group ) {
		$defaults = self::default_texts();
		foreach ( self::text_fields() as $key => $field ) {
			if ( ( isset( $field['group'] ) ? $field['group'] : 'cover' ) !== $group ) {
				continue;
			}
			$value = isset( $s['texts'][ $key ] ) ? $s['texts'][ $key ] : '';
			?>
			<p>
				<label for="olicg_cover_text_<?php echo esc_attr( $key ); ?>"><strong><?php echo esc_html( $field['label'] ); ?></strong></label><br>
				<?php if ( $field['multiline'] ) : ?>
					<textarea id="olicg_cover_text_<?php echo esc_attr( $key ); ?>" name="olicg_cover_texts[<?php echo esc_attr( $key ); ?>]" class="large-text" rows="3" placeholder="<?php echo esc_attr( $defaults[ $key ] ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
				<?php else : ?>
					<input type="text" id="olicg_cover_text_<?php echo esc_attr( $key ); ?>" name="olicg_cover_texts[<?php echo esc_attr( $key ); ?>]" class="large-text" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $defaults[ $key ] ); ?>">
				<?php endif; ?>
			</p>
			<?php
		}
	}

	public static function render_settings() {
		$s        = self::get_settings();
		$design   = OLICG_Design::get_settings();
		$catalog  = OLICG_Catalog::get_settings();
		?>
		<p class="olicg-intro"><?php esc_html_e( 'Customise the first page of the catalogue: every text, the logo, fonts, colours and background — or replace the whole cover with your own image. Fonts and colours used inside the catalogue are in the Design tab.', 'oli-catalog-generator' ); ?></p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="olicg_save_cover">
			<?php wp_nonce_field( 'olicg_save_cover' ); ?>

			<div class="olicg-grid">
				<div class="olicg-card">
					<h2><?php esc_html_e( 'Cover type', 'oli-catalog-generator' ); ?></h2>
					<fieldset class="olicg-choice">
						<?php foreach ( self::modes() as $key => $label ) : ?>
							<label><input type="radio" name="olicg_cover_mode" value="<?php echo esc_attr( $key ); ?>" <?php checked( $s['mode'], $key ); ?>> <?php echo esc_html( $label ); ?></label>
						<?php endforeach; ?>
					</fieldset>

					<h3><?php esc_html_e( 'Full-page image', 'oli-catalog-generator' ); ?></h3>
					<?php self::media_field( 'olicg_cover_image_url', $s['image_url'], __( 'Cover image', 'oli-catalog-generator' ), __( 'Used with “Full-page image”. Best size: 2550 × 3300 px (Letter) or 2480 × 3508 px (A4), portrait. Printed edge to edge.', 'oli-catalog-generator' ) ); ?>
					<p>
						<label><?php esc_html_e( 'Fit', 'oli-catalog-generator' ); ?>
							<select name="olicg_cover_image_fit">
								<option value="cover" <?php selected( $s['image_fit'], 'cover' ); ?>><?php esc_html_e( 'Fill the page (crops edges if the proportions differ)', 'oli-catalog-generator' ); ?></option>
								<option value="contain" <?php selected( $s['image_fit'], 'contain' ); ?>><?php esc_html_e( 'Show the whole image (background colour around it)', 'oli-catalog-generator' ); ?></option>
							</select>
						</label>
					</p>

					<h3><?php esc_html_e( 'Logo', 'oli-catalog-generator' ); ?></h3>
					<p><label><input type="checkbox" name="olicg_cover_show_logo" value="1" <?php checked( $s['show_logo'] ); ?>> <?php esc_html_e( 'Show the logo (or the company name when there is no logo)', 'oli-catalog-generator' ); ?></label></p>
					<?php $olicg_site_logo = OLICG_Catalog::logo_url( false ); ?>
					<?php self::media_field( 'olicg_cover_logo_url', $s['logo_url'], __( 'Logo', 'oli-catalog-generator' ), sprintf( /* translators: %s: logo URL used when empty */ __( 'Leave empty to use the site logo (%s).', 'oli-catalog-generator' ), $olicg_site_logo ? $olicg_site_logo : __( 'none found', 'oli-catalog-generator' ) ) ); ?>
					<?php self::logo_source_field( $s ); ?>
					<p>
						<label><?php esc_html_e( 'Logo height (px)', 'oli-catalog-generator' ); ?>
							<input type="number" name="olicg_cover_logo_height" min="20" max="200" step="1" class="small-text" value="<?php echo esc_attr( $s['logo_height'] ); ?>">
						</label>
					</p>

					<h3><?php esc_html_e( 'Background & colours', 'oli-catalog-generator' ); ?></h3>
					<div class="olicg-colors">
						<p>
							<label for="olicg_cover_bg_color"><strong><?php esc_html_e( 'Background colour', 'oli-catalog-generator' ); ?></strong></label><br>
							<input type="text" id="olicg_cover_bg_color" name="olicg_cover_bg_color" class="olicg-color" value="<?php echo esc_attr( $s['bg_color'] ); ?>">
						</p>
						<p>
							<label for="olicg_cover_text_color"><strong><?php esc_html_e( 'Text colour', 'oli-catalog-generator' ); ?></strong></label><br>
							<input type="text" id="olicg_cover_text_color" name="olicg_cover_text_color" class="olicg-color" value="<?php echo esc_attr( $s['text_color'] ); ?>">
						</p>
						<p>
							<label for="olicg_cover_muted_color"><strong><?php esc_html_e( 'Secondary text colour', 'oli-catalog-generator' ); ?></strong></label><br>
							<input type="text" id="olicg_cover_muted_color" name="olicg_cover_muted_color" class="olicg-color" value="<?php echo esc_attr( $s['muted_color'] ); ?>">
						</p>
					</div>
					<p class="description"><?php esc_html_e( 'Empty colours follow the Design tab (white page, Text and Secondary text colours). A background colour or image prints edge to edge. The information band uses the Design tab’s “Cover band” colours.', 'oli-catalog-generator' ); ?></p>
					<?php self::media_field( 'olicg_cover_bg_image_url', $s['bg_image_url'], __( 'Background image (behind the texts)', 'oli-catalog-generator' ) ); ?>
					<p>
						<label><?php esc_html_e( 'Background colour over the image (%) — makes texts easier to read', 'oli-catalog-generator' ); ?>
							<input type="number" name="olicg_cover_bg_overlay" min="0" max="90" step="5" class="small-text" value="<?php echo esc_attr( $s['bg_overlay'] ); ?>">
						</label>
					</p>

					<h3><?php esc_html_e( 'Title font', 'oli-catalog-generator' ); ?></h3>
					<p>
						<input type="text" name="olicg_cover_title_font" class="regular-text" value="<?php echo esc_attr( $s['title_font'] ); ?>" placeholder="<?php echo esc_attr( $design['font_heading'] ); ?>" aria-label="<?php esc_attr_e( 'Title font', 'oli-catalog-generator' ); ?>">
						<label><?php esc_html_e( 'Size (pt)', 'oli-catalog-generator' ); ?>
							<input type="number" name="olicg_cover_title_size" min="16" max="120" step="1" class="small-text" value="<?php echo esc_attr( $s['title_size'] ); ?>">
						</label>
					</p>
					<p class="description"><?php esc_html_e( 'Empty = the headings font from the Design tab. Loaded the same way as the other fonts (Design → Load fonts from).', 'oli-catalog-generator' ); ?></p>
				</div>

				<div class="olicg-card">
					<h2><?php esc_html_e( 'Cover texts', 'oli-catalog-generator' ); ?></h2>
					<fieldset class="olicg-choice">
						<legend><strong><?php esc_html_e( 'Show', 'oli-catalog-generator' ); ?></strong></legend>
						<label><input type="checkbox" name="olicg_cover_show_meta" value="1" <?php checked( $s['show_meta'] ); ?>> <?php esc_html_e( 'Top right lines', 'oli-catalog-generator' ); ?></label>
						<label><input type="checkbox" name="olicg_cover_show_eyebrow" value="1" <?php checked( $s['show_eyebrow'] ); ?>> <?php esc_html_e( 'Small line above the title', 'oli-catalog-generator' ); ?></label>
						<label><input type="checkbox" name="olicg_cover_show_band" value="1" <?php checked( $s['show_band'] ); ?>> <?php esc_html_e( 'Information band', 'oli-catalog-generator' ); ?></label>
						<label><input type="checkbox" name="olicg_cover_show_note" value="1" <?php checked( $s['show_note'] ); ?>> <?php esc_html_e( 'Note under the band', 'oli-catalog-generator' ); ?></label>
					</fieldset>

					<?php self::text_inputs( $s, 'cover' ); ?>

					<h2 class="olicg-subhead"><?php esc_html_e( 'Pages, footer & numbering', 'oli-catalog-generator' ); ?></h2>
					<fieldset class="olicg-choice">
						<label><input type="checkbox" name="olicg_cover_footer_show" value="1" <?php checked( $s['footer_show'] ); ?>> <?php esc_html_e( 'Footer text on every page', 'oli-catalog-generator' ); ?></label>
						<label><input type="checkbox" name="olicg_cover_page_numbers" value="1" <?php checked( $s['page_numbers'] ); ?>> <?php esc_html_e( 'Page numbers', 'oli-catalog-generator' ); ?></label>
						<label><?php esc_html_e( 'Page number position', 'oli-catalog-generator' ); ?>
							<select name="olicg_cover_page_position">
								<?php foreach ( self::page_positions() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $s['page_position'], $key ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<label><input type="checkbox" name="olicg_cover_count_cover" value="1" <?php checked( $s['count_cover'] ); ?>> <?php esc_html_e( 'Count the cover as page 1 (untick to start at 1 on the first product page)', 'oli-catalog-generator' ); ?></label>
						<label><?php esc_html_e( 'Footer size (pt)', 'oli-catalog-generator' ); ?>
							<input type="number" name="olicg_cover_footer_size" min="5" max="14" step="0.5" class="small-text" value="<?php echo esc_attr( $s['footer_size'] ); ?>">
						</label>
						<label><input type="checkbox" name="olicg_cover_closing_show" value="1" <?php checked( $s['closing_show'] ); ?>> <?php esc_html_e( 'Closing text at the end of the catalogue', 'oli-catalog-generator' ); ?></label>
					</fieldset>
					<?php self::text_inputs( $s, 'pages' ); ?>
					<p class="description"><?php esc_html_e( 'Examples for the page number: “Page {page}”, “{page} / {pages}”, “Page {page} of {pages}”. The footer sits on the opposite side of the page number (left when the number is centred). The cover never shows the footer. {pages} always counts the cover, so with “Count the cover” unticked prefer “Page {page}”.', 'oli-catalog-generator' ); ?></p>

					<p class="description"><?php esc_html_e( 'Empty fields use the automatic text shown in grey; type a single dash (-) to print nothing. You can mix your own words with these placeholders:', 'oli-catalog-generator' ); ?></p>
					<ul class="olicg-tokens">
						<?php foreach ( self::tokens() as $token => $label ) : ?>
							<li><code><?php echo esc_html( $token ); ?></code> <?php echo esc_html( $label ); ?></li>
						<?php endforeach; ?>
					</ul>
					<p class="description"><?php esc_html_e( 'Custom texts can be translated with WPML String Translation / Polylang (names “Cover: …”) or TranslatePress.', 'oli-catalog-generator' ); ?></p>

					<div class="olicg-actions">
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Save cover', 'oli-catalog-generator' ); ?></button>
						<a class="button" href="<?php echo esc_url( OLICG_Admin::render_url( $catalog['region'], null, $catalog['language'] ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Preview catalogue', 'oli-catalog-generator' ); ?></a>
						<button type="submit" class="button-link" name="olicg_cover_reset" value="1" data-olicg-confirm="<?php esc_attr_e( 'Reset the cover to the defaults?', 'oli-catalog-generator' ); ?>"><?php esc_html_e( 'Reset to defaults', 'oli-catalog-generator' ); ?></button>
					</div>
				</div>
			</div>
		</form>
		<?php
	}
}
