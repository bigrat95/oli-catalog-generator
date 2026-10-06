<?php
/**
 * Printable catalogue.
 *
 * Available: $settings, $sections, $region, $prices (price components), $currency, $logo_url, $regions, $lang, $html_lang.
 *
 * @package OliCatalogGenerator
 */

defined( 'ABSPATH' ) || exit;

$olicg_fonts_file = apply_filters( 'olicg_fonts_css_file', get_template_directory() . '/assets/fonts/fonts-local.css' );
$olicg_fonts_css  = is_readable( $olicg_fonts_file )
	? str_replace( '__FONTS__', esc_url( get_template_directory_uri() . '/assets/fonts' ), (string) file_get_contents( $olicg_fonts_file ) )
	: '';

$olicg_design    = OLICG_Design::get_settings();
$olicg_stacks    = OLICG_Design::stacks( $olicg_design );
$olicg_mono_css  = str_replace( array( '<', '>', '{', '}', ';' ), '', $olicg_stacks['mono'] );

$olicg_components = array_intersect_key( OLICG_Catalog::price_labels( $settings, $lang ), OLICG_Pricing::components( $prices ) );
$olicg_is_dealer = isset( $olicg_components['cost'] );
$olicg_multi     = count( $olicg_components ) > 1;
$olicg_prices_lbl = implode( ' · ', $olicg_components );
$olicg_show_img  = ! empty( $settings['show_image'] );
$olicg_year      = wp_date( 'Y' );
/* translators: catalogue date format, see https://www.php.net/manual/datetime.format.php */
$olicg_date      = wp_date( __( 'F j, Y', 'oli-catalog-generator' ) );
$olicg_count     = array_sum( array_map( static function ( $olicg_section ) { return count( $olicg_section['items'] ); }, $sections ) );
$olicg_market    = $regions[ $region ]['label'];
if ( $olicg_is_dealer ) {
	$olicg_price_lbl = __( 'Dealer price list', 'oli-catalog-generator' );
} elseif ( $olicg_components ) {
	$olicg_price_lbl = __( 'Suggested retail prices', 'oli-catalog-generator' );
} else {
	$olicg_price_lbl = __( 'Product catalogue', 'oli-catalog-generator' );
}
$olicg_edition   = $olicg_market . ( $olicg_components ? ' · ' . $olicg_prices_lbl : '' );
$olicg_layout    = isset( OLICG_Catalog::layouts()[ $settings['layout'] ] ) ? $settings['layout'] : 'compact';
$olicg_columns   = 'list' === $olicg_layout ? 2 : max( 2, min( 'grid' === $olicg_layout ? 4 : 6, (int) $settings['columns'] ) );
$olicg_paper     = 'a4' === $settings['paper'] ? 'a4' : 'letter';
$olicg_is_table  = 'table' === $olicg_layout;
$olicg_edition_short = trim( $regions[ $region ]['short'] . ' ' . ( $olicg_is_dealer ? __( 'Dealer', 'oli-catalog-generator' ) : ( $olicg_components ? __( 'Retail', 'oli-catalog-generator' ) : '' ) ) );

if ( $olicg_is_dealer ) {
	$olicg_note = __( 'Confidential dealer pricing — not for public distribution. Prices subject to change without notice.', 'oli-catalog-generator' );
} elseif ( $olicg_components ) {
	$olicg_note = __( 'Suggested retail prices. Prices and availability subject to change without notice.', 'oli-catalog-generator' );
} else {
	$olicg_note = __( 'Specifications and availability subject to change without notice.', 'oli-catalog-generator' );
}

$olicg_cover      = OLICG_Cover::get_settings();
$olicg_cover_mode = 'image' === $olicg_cover['mode'] && '' === $olicg_cover['image_url'] ? 'design' : $olicg_cover['mode'];
$olicg_has_cover  = 'none' !== $olicg_cover_mode;
$olicg_bleed      = $olicg_has_cover && OLICG_Cover::is_bleed( $olicg_cover );
$olicg_cover_texts  = OLICG_Cover::texts( $olicg_cover, $lang );
$olicg_cover_values = array(
	'{title}'         => $settings['title'],
	'{year}'          => $olicg_year,
	'{date}'          => $olicg_date,
	'{site}'          => get_bloginfo( 'name' ),
	'{domain}'        => (string) wp_parse_url( home_url(), PHP_URL_HOST ),
	'{market}'        => $olicg_market,
	'{currency}'      => $currency,
	'{prices}'        => $olicg_prices_lbl,
	'{edition_label}' => $olicg_price_lbl,
	'{count}'         => $olicg_count,
	'{note}'          => $olicg_note,
);
$olicg_closing = $olicg_components
	? sprintf(
		/* translators: 1: site name, 2: currency, 3: date, 4: site URL */
		__( '© %1$s. All prices in %2$s, current as of %3$s. Specifications, prices and availability subject to change without notice. %4$s', 'oli-catalog-generator' ),
		$olicg_year . ' ' . get_bloginfo( 'name' ),
		$currency,
		$olicg_date,
		wp_parse_url( home_url(), PHP_URL_HOST )
	)
	: sprintf(
		/* translators: 1: site name, 2: date, 3: site URL */
		__( '© %1$s. Current as of %2$s. Specifications and availability subject to change without notice. %3$s', 'oli-catalog-generator' ),
		$olicg_year . ' ' . get_bloginfo( 'name' ),
		$olicg_date,
		wp_parse_url( home_url(), PHP_URL_HOST )
	);
$olicg_cover_values['{closing}'] = $olicg_closing;

$olicg_footer_css = $olicg_cover['footer_show'] ? OLICG_Cover::css_content( OLICG_Cover::fill_plain( $olicg_cover_texts['footer'], $olicg_cover_values ) ) : 'none';
$olicg_page_css   = $olicg_cover['page_numbers'] ? OLICG_Cover::css_content( OLICG_Cover::fill_plain( $olicg_cover_texts['page'], $olicg_cover_values ) ) : 'none';
$olicg_page_box   = $olicg_cover['page_position'];
$olicg_footer_box = 'left' === $olicg_page_box ? 'right' : 'left';
$olicg_footer_pt  = (float) $olicg_cover['footer_size'];

$olicg_cover_text = static function ( $key ) use ( $olicg_cover_texts, $olicg_cover_values ) {
	return OLICG_Cover::fill( $olicg_cover_texts[ $key ], $olicg_cover_values );
};
$olicg_band = array();
foreach ( array( 1, 2, 3 ) as $olicg_n ) {
	// Middle band box is optional; skip the old default “Prices · CAD” line (edition is already in the eyebrow).
	if ( 2 === $olicg_n && OLICG_Cover::skip_band2( $olicg_cover ) ) {
		continue;
	}
	$olicg_box = array( $olicg_cover_text( 'band' . $olicg_n . '_label' ), $olicg_cover_text( 'band' . $olicg_n . '_value' ) );
	if ( '' !== $olicg_box[0] . $olicg_box[1] ) {
		$olicg_band[] = $olicg_box;
	}
}

/**
 * Product name, linked to its page when "clickable PDF" is on.
 */
$olicg_name = static function ( array $olicg_item ) use ( $settings ) {
	if ( empty( $settings['link_products'] ) || empty( $olicg_item['url'] ) ) {
		return esc_html( $olicg_item['name'] );
	}
	return '<a class="plink" href="' . esc_url( $olicg_item['url'] ) . '" target="_blank" rel="noopener" draggable="false">' . esc_html( $olicg_item['name'] ) . '</a>';
};

/**
 * Zoomable image box (catalogue cards and price list pictures).
 */
$olicg_image_box = static function ( array $olicg_item ) use ( $settings, $olicg_layout ) {
	$fit    = OLICG_Catalog::image_fit( $settings, $olicg_layout, $olicg_item['id'] );
	$zoomed = 1.0 !== $fit['s'] || $fit['x'] || $fit['y'];
	?>
	<div class="card-img" data-s="<?php echo esc_attr( sprintf( '%.3F', $fit['s'] ) ); ?>" data-x="<?php echo esc_attr( sprintf( '%.2F', $fit['x'] ) ); ?>" data-y="<?php echo esc_attr( sprintf( '%.2F', $fit['y'] ) ); ?>">
		<img src="<?php echo esc_url( $olicg_item['image'] ); ?>" alt="<?php echo esc_attr( $olicg_item['name'] ); ?>" draggable="false"<?php echo $zoomed ? ' style="' . esc_attr( sprintf( 'transform: translate(%.2F%%, %.2F%%) scale(%.3F);', $fit['x'], $fit['y'], $fit['s'] ) ) . '"' : ''; ?>>
		<span class="img-zoom" title="<?php esc_attr_e( 'Drag to zoom the image · double-click the image to reset', 'oli-catalog-generator' ); ?>" aria-hidden="true"></span>
	</div>
	<?php
};

$olicg_font_handles = array();
foreach ( OLICG_Design::font_urls( array( 'heading', 'body', 'mono', 'cover' ), array_merge( $olicg_design, array( 'font_cover' => $olicg_has_cover ? $olicg_cover['title_font'] : '' ) ) ) as $olicg_i => $olicg_font_url ) {
	$olicg_font_handles[] = 'olicg-catalog-font-' . $olicg_i;
	// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- a version query string would change the font service URL.
	wp_register_style( 'olicg-catalog-font-' . $olicg_i, $olicg_font_url, array(), null );
}
wp_register_style( 'olicg-catalog', OLICG_PLUGIN_URL . 'assets/css/catalog.css', $olicg_font_handles, OLICG_VERSION );

$olicg_css  = $olicg_fonts_css . "\n:root {\n" . OLICG_Design::css_vars( $olicg_design ) . "\t--cols: " . (int) $olicg_columns . ";\n}\n";
$olicg_css .= '@page {' . "\n\tsize: " . ( 'a4' === $olicg_paper ? 'A4' : 'letter' ) . " portrait;\n\tmargin: 0.5in 0.45in 0.6in;\n";
$olicg_css .= "\t@bottom-" . $olicg_footer_box . ' { content: ' . $olicg_footer_css . '; font: ' . $olicg_footer_pt . 'pt ' . $olicg_mono_css . '; color: ' . $olicg_design['color_muted'] . "; }\n";
$olicg_css .= "\t@bottom-" . $olicg_page_box . ' { content: ' . $olicg_page_css . '; font: 700 ' . ( $olicg_footer_pt + 1 ) . 'pt ' . $olicg_mono_css . '; color: ' . $olicg_design['color_text'] . "; }\n}\n";
if ( $olicg_has_cover ) {
	$olicg_css .= "@page :first {\n" . ( $olicg_bleed ? "\tmargin: 0;\n" : '' ) . ( $olicg_cover['count_cover'] ? '' : "\tcounter-increment: page 0;\n" );
	$olicg_css .= "\t@bottom-left { content: none; }\n\t@bottom-center { content: none; }\n\t@bottom-right { content: none; }\n}\n";
	$olicg_css .= '.cover { ' . OLICG_Cover::style( $olicg_cover ) . " }\n";
	$olicg_css .= '.cover-band { --band-cols: ' . max( 1, count( $olicg_band ) ) . "; }\n";
}
$olicg_css .= OLICG_Design::custom_css( $olicg_design );
wp_add_inline_style( 'olicg-catalog', $olicg_css );

wp_register_script( 'olicg-catalog', OLICG_PLUGIN_URL . 'assets/js/catalog.js', array(), OLICG_VERSION, true );
wp_localize_script(
	'olicg-catalog',
	'olicgCatalog',
	array(
		'url'    => admin_url( 'admin-ajax.php' ),
		'nonce'  => wp_create_nonce( 'olicg_arrange' ),
		'layout' => $olicg_layout,
		'item'   => $olicg_is_table ? '.row' : '.card',
		/* translators: %d: number of products */
		'one'    => _n( '%d product', '%d products', 1, 'oli-catalog-generator' ),
		/* translators: %d: number of products */
		'many'   => _n( '%d product', '%d products', 2, 'oli-catalog-generator' ),
		'saving' => __( 'Saving…', 'oli-catalog-generator' ),
		'saved'  => __( 'Saved ✓', 'oli-catalog-generator' ),
		'failed' => __( 'Could not save — reload and try again.', 'oli-catalog-generator' ),
	)
);

$olicg_body_class = array( 'layout-' . $olicg_layout, 'a4' === $olicg_paper ? 'paper-a4' : 'paper-letter' );
if ( ! $olicg_show_img ) {
	$olicg_body_class[] = 'no-images';
}
if ( 4 === $olicg_columns ) {
	$olicg_body_class[] = 'cols-4';
}
if ( ! empty( $settings['section_new_page'] ) ) {
	$olicg_body_class[] = 'new-page';
}
?><!doctype html>
<html lang="<?php echo esc_attr( $html_lang ); ?>">
<head>
<meta charset="utf-8">
<meta name="robots" content="noindex, nofollow, noarchive">
<meta name="referrer" content="no-referrer">
<title><?php echo esc_html( $settings['title'] . ' ' . $olicg_year . ' — ' . $olicg_edition ); ?></title>
<?php wp_print_styles( 'olicg-catalog' ); ?>
</head>
<body class="<?php echo esc_attr( implode( ' ', $olicg_body_class ) ); ?>">

<div class="toolbar">
	<div>
		<strong><?php echo esc_html( $settings['title'] ); ?></strong>
		· <?php echo esc_html( $olicg_edition ); ?> · <span class="js-total-label"><?php /* translators: %d: number of products */ echo esc_html( sprintf( _n( '%d product', '%d products', $olicg_count, 'oli-catalog-generator' ), $olicg_count ) ); ?></span>
		<div class="hint">
			<?php
			if ( $olicg_is_table ) {
				esc_html_e( 'Drag rows or pictures to reorder · hover a row: ◩ shows or hides its picture, × removes the product · hover a picture: × hides it, drag its corner to zoom (double-click resets) — changes save automatically.', 'oli-catalog-generator' );
			} else {
				esc_html_e( 'Drag products to reorder · hover and click × to remove · drag an image’s corner to zoom it, then drag the image to position it (double-click resets) — changes save automatically.', 'oli-catalog-generator' );
			}
			?>
			<span class="status" aria-live="polite"></span>
		</div>
		<div class="hint"><?php esc_html_e( 'Chrome / Edge → Print → Save as PDF. Margins: Default · Headers and footers: off · Background graphics: on.', 'oli-catalog-generator' ); ?></div>
	</div>
	<div class="toolbar-actions">
		<button type="button" class="undo" hidden><?php esc_html_e( 'Undo remove', 'oli-catalog-generator' ); ?></button>
		<button type="button" class="js-print"><?php esc_html_e( 'Print / Save as PDF', 'oli-catalog-generator' ); ?></button>
	</div>
</div>

<main class="doc">

	<?php if ( 'image' === $olicg_cover_mode ) : ?>
		<section class="cover cover-bleed cover-image<?php echo 'contain' === $olicg_cover['image_fit'] ? ' fit-contain' : ''; ?>">
			<img src="<?php echo esc_url( $olicg_cover['image_url'] ); ?>" alt="<?php echo esc_attr( $settings['title'] ); ?>">
		</section>
	<?php elseif ( 'design' === $olicg_cover_mode ) : ?>
		<section class="cover<?php echo OLICG_Cover::is_bleed( $olicg_cover ) ? ' cover-bleed' : ''; ?><?php echo $olicg_cover['bg_image_url'] ? ' has-bg-image' : ''; ?>">
			<?php if ( $olicg_cover['show_logo'] || $olicg_cover['show_meta'] ) : ?>
				<div class="cover-top">
					<div>
						<?php if ( $olicg_cover['show_logo'] && $logo_url ) : ?>
							<img class="cover-logo" src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
						<?php elseif ( $olicg_cover['show_logo'] ) : ?>
							<div class="cover-wordmark"><?php echo $olicg_cover_text( 'wordmark' ); // phpcs:ignore WordPress.Security.EscapeOutput -- OLICG_Cover::fill() escapes. ?></div>
						<?php endif; ?>
					</div>
					<?php if ( $olicg_cover['show_meta'] ) : ?>
						<div class="cover-meta-top">
							<?php echo implode( '<br>', array_filter( array( $olicg_cover_text( 'meta1' ), $olicg_cover_text( 'meta2' ) ), 'strlen' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- OLICG_Cover::fill() escapes. ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="cover-main">
				<?php if ( $olicg_cover['show_eyebrow'] && '' !== $olicg_cover_text( 'eyebrow' ) ) : ?>
					<div class="eyebrow"><?php echo $olicg_cover_text( 'eyebrow' ); // phpcs:ignore WordPress.Security.EscapeOutput -- OLICG_Cover::fill() escapes. ?></div>
				<?php endif; ?>
				<h1 class="cover-title">
					<?php echo $olicg_cover_text( 'title' ); // phpcs:ignore WordPress.Security.EscapeOutput -- OLICG_Cover::fill() escapes. ?>
					<?php if ( '' !== $olicg_cover_text( 'subtitle' ) ) : ?>
						<br><span class="cover-year"><?php echo $olicg_cover_text( 'subtitle' ); // phpcs:ignore WordPress.Security.EscapeOutput -- OLICG_Cover::fill() escapes. ?></span>
					<?php endif; ?>
				</h1>
			</div>

			<?php if ( $olicg_cover['show_band'] && $olicg_band ) : ?>
				<div class="cover-band">
					<?php foreach ( $olicg_band as $olicg_box ) : ?>
						<div><div class="label"><?php echo $olicg_box[0]; // phpcs:ignore WordPress.Security.EscapeOutput -- OLICG_Cover::fill() escapes. ?></div><div class="value"><?php echo $olicg_box[1]; // phpcs:ignore WordPress.Security.EscapeOutput -- OLICG_Cover::fill() escapes. ?></div></div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if ( $olicg_cover['show_note'] && '' !== $olicg_cover_text( 'note' ) ) : ?>
				<div class="cover-note"><?php echo $olicg_cover_text( 'note' ); // phpcs:ignore WordPress.Security.EscapeOutput -- OLICG_Cover::fill() escapes. ?></div>
			<?php endif; ?>
		</section>
	<?php endif; ?>

	<?php if ( ! $sections ) : ?>
		<p class="empty"><?php esc_html_e( 'No products match this selection.', 'oli-catalog-generator' ); ?></p>
	<?php endif; ?>

	<?php
	if ( $olicg_is_table ) {
		$olicg_cols = array();
		if ( ! empty( $settings['show_sku'] ) ) {
			$olicg_cols['sku'] = __( 'SKU', 'oli-catalog-generator' );
		}
		if ( ! empty( $settings['show_upc'] ) ) {
			$olicg_cols['upc'] = __( 'UPC', 'oli-catalog-generator' );
		}
		if ( ! empty( $settings['show_brand'] ) ) {
			$olicg_cols['brand'] = __( 'Brand', 'oli-catalog-generator' );
		}
		$olicg_cols['name'] = __( 'Description', 'oli-catalog-generator' );
	}
	?>
	<?php foreach ( $sections as $olicg_section ) : ?>
		<section class="section">
		<?php if ( $olicg_is_table ) : ?>
			<table class="pl">
				<thead>
					<tr class="pl-band">
						<th colspan="<?php echo (int) ( count( $olicg_cols ) + count( $olicg_components ) ); ?>">
							<span class="pl-title">
								<?php if ( '' !== $olicg_section['eyebrow'] ) : ?>
									<span class="pl-eyebrow"><?php echo esc_html( $olicg_section['eyebrow'] ); ?> ›</span>
								<?php endif; ?>
								<?php echo esc_html( $olicg_section['title'] ); ?>
							</span>
							<span class="pl-edition"><?php echo esc_html( $olicg_edition_short ); ?></span>
						</th>
					</tr>
					<tr class="pl-cols">
						<?php foreach ( $olicg_cols as $olicg_key => $olicg_label ) : ?>
							<th class="c-<?php echo esc_attr( $olicg_key ); ?>"><?php echo esc_html( $olicg_label ); ?></th>
						<?php endforeach; ?>
						<?php foreach ( $olicg_components as $olicg_label ) : ?>
							<th class="c-price"><?php echo esc_html( $olicg_label ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody class="rows">
					<?php foreach ( $olicg_section['items'] as $olicg_item ) : ?>
						<tr class="row<?php echo $olicg_item['picture'] ? '' : ' no-pic'; ?>" draggable="true" data-id="<?php echo esc_attr( $olicg_item['id'] ); ?>">
							<?php foreach ( $olicg_cols as $olicg_key => $olicg_label ) : ?>
								<td class="c-<?php echo esc_attr( $olicg_key ); ?>">
									<?php if ( 'name' === $olicg_key ) : ?>
										<span class="row-actions">
											<?php if ( $olicg_show_img && $olicg_item['has_img'] ) : ?>
												<button type="button" class="pic-toggle" title="<?php esc_attr_e( 'Show or hide this picture below the table', 'oli-catalog-generator' ); ?>" aria-label="<?php esc_attr_e( 'Show or hide this picture below the table', 'oli-catalog-generator' ); ?>">◩</button>
											<?php endif; ?>
											<button type="button" class="card-remove" title="<?php esc_attr_e( 'Remove from catalogue', 'oli-catalog-generator' ); ?>" aria-label="<?php esc_attr_e( 'Remove from catalogue', 'oli-catalog-generator' ); ?>">×</button>
										</span>
									<?php endif; ?>
									<?php echo 'name' === $olicg_key ? $olicg_name( $olicg_item ) : esc_html( $olicg_item[ $olicg_key ] ); // phpcs:ignore WordPress.Security.EscapeOutput -- $olicg_name() escapes. ?>
								</td>
							<?php endforeach; ?>
							<?php foreach ( $olicg_components as $olicg_key => $olicg_label ) : ?>
								<td class="c-price"><?php echo $olicg_item['prices'][ $olicg_key ] ? esc_html( OLICG_Pricing::format( $olicg_item['prices'][ $olicg_key ], $region, false ) ) : '—'; ?></td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ( $olicg_show_img && array_filter( array_column( $olicg_section['items'], 'has_img' ) ) ) : ?>
				<div class="pics-bar">
					<?php esc_html_e( 'Pictures:', 'oli-catalog-generator' ); ?>
					<button type="button" data-show="1"><?php esc_html_e( 'Show all', 'oli-catalog-generator' ); ?></button>
					<button type="button" data-show="0"><?php esc_html_e( 'Hide all', 'oli-catalog-generator' ); ?></button>
				</div>
				<div class="pics">
					<?php foreach ( $olicg_section['items'] as $olicg_item ) : ?>
						<?php if ( $olicg_item['has_img'] ) : ?>
							<figure class="pic" draggable="true" data-id="<?php echo esc_attr( $olicg_item['id'] ); ?>"<?php echo $olicg_item['picture'] ? '' : ' hidden'; ?>>
								<button type="button" class="pic-remove" title="<?php esc_attr_e( 'Hide this picture', 'oli-catalog-generator' ); ?>" aria-label="<?php esc_attr_e( 'Hide this picture', 'oli-catalog-generator' ); ?>">×</button>
								<?php $olicg_image_box( $olicg_item ); ?>
								<figcaption><?php echo esc_html( '' !== $olicg_item['sku'] ? $olicg_item['sku'] : $olicg_item['name'] ); ?></figcaption>
							</figure>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		<?php else : ?>
			<header class="section-head">
				<div>
					<?php if ( '' !== $olicg_section['eyebrow'] ) : ?>
						<div class="eyebrow"><?php echo esc_html( $olicg_section['eyebrow'] ); ?></div>
					<?php endif; ?>
					<h2 class="section-title"><?php echo esc_html( $olicg_section['title'] ); ?></h2>
				</div>
				<div class="section-count"><?php /* translators: %d: number of products */ echo esc_html( sprintf( _n( '%d product', '%d products', count( $olicg_section['items'] ), 'oli-catalog-generator' ), count( $olicg_section['items'] ) ) ); ?></div>
			</header>

			<div class="grid">
				<?php foreach ( $olicg_section['items'] as $olicg_item ) : ?>
					<article class="card" draggable="true" data-id="<?php echo esc_attr( $olicg_item['id'] ); ?>">
						<button type="button" class="card-remove" title="<?php esc_attr_e( 'Remove from catalogue', 'oli-catalog-generator' ); ?>" aria-label="<?php esc_attr_e( 'Remove from catalogue', 'oli-catalog-generator' ); ?>">×</button>
						<?php if ( $olicg_show_img ) : ?>
							<?php $olicg_image_box( $olicg_item ); ?>
						<?php endif; ?>
						<div class="card-body">
							<?php if ( ! empty( $settings['show_brand'] ) && '' !== $olicg_item['brand'] ) : ?>
								<div class="card-brand"><?php echo esc_html( $olicg_item['brand'] ); ?></div>
							<?php endif; ?>
							<h3 class="card-title"><?php echo $olicg_name( $olicg_item ); // phpcs:ignore WordPress.Security.EscapeOutput -- $olicg_name() escapes. ?></h3>
							<?php if ( ! empty( $settings['show_sku'] ) && '' !== $olicg_item['sku'] ) : ?>
								<div class="card-sku"><?php echo esc_html( 'SKU ' . $olicg_item['sku'] ); ?></div>
							<?php endif; ?>
							<?php if ( ! empty( $settings['show_upc'] ) && '' !== $olicg_item['upc'] ) : ?>
								<div class="card-sku card-upc"><?php echo esc_html( 'UPC ' . $olicg_item['upc'] ); ?></div>
							<?php endif; ?>
							<?php if ( ! $olicg_components ) : ?>
							<?php elseif ( $olicg_multi ) : ?>
								<div class="card-prices">
									<?php foreach ( $olicg_components as $olicg_key => $olicg_label ) : ?>
										<div class="price-row">
											<span class="lbl"><?php echo esc_html( $olicg_label ); ?></span>
											<span class="amt"><?php echo $olicg_item['prices'][ $olicg_key ] ? esc_html( OLICG_Pricing::format( $olicg_item['prices'][ $olicg_key ], $region ) ) : '—'; ?></span>
										</div>
									<?php endforeach; ?>
								</div>
							<?php else : ?>
								<?php $olicg_price = reset( $olicg_item['prices'] ); ?>
								<div class="card-price">
									<?php if ( $olicg_price ) : ?>
										<span class="amount"><?php echo esc_html( OLICG_Pricing::format( $olicg_price, $region ) ); ?></span>
										<span class="currency"><?php echo esc_html( $currency ); ?></span>
									<?php else : ?>
										<span class="na"><?php esc_html_e( 'Price on request', 'oli-catalog-generator' ); ?></span>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		</section>
	<?php endforeach; ?>

	<?php if ( $sections && $olicg_cover['closing_show'] && '' !== $olicg_cover_text( 'closing' ) ) : ?>
		<div class="closing"><?php echo $olicg_cover_text( 'closing' ); // phpcs:ignore WordPress.Security.EscapeOutput -- OLICG_Cover::fill() escapes. ?></div>
	<?php endif; ?>
</main>

<?php wp_print_scripts( 'olicg-catalog' ); ?>
</body>
</html>
