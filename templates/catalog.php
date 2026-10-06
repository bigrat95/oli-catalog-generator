<?php
/**
 * Printable catalogue.
 *
 * Available: $settings, $sections, $region, $prices (price components), $currency, $logo_url, $regions, $lang, $html_lang.
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
$olicg_count     = array_sum( array_map( static function ( $section ) { return count( $section['items'] ); }, $sections ) );
$olicg_market    = $regions[ $region ]['label'];
if ( $olicg_is_dealer ) {
	$olicg_price_lbl = __( 'Dealer price list', 'oli-catalog-generator' );
} elseif ( $olicg_components ) {
	$olicg_price_lbl = __( 'Suggested retail prices', 'oli-catalog-generator' );
} else {
	$olicg_price_lbl = __( 'Product catalogue', 'oli-catalog-generator' );
}
$olicg_edition   = $olicg_market . ( $olicg_components ? ' · ' . $olicg_prices_lbl : '' );
$olicg_footer    = sprintf( '%s — %s %s · %s · %s', get_bloginfo( 'name' ), $settings['title'], $olicg_year, $olicg_market, $currency );
$olicg_footer_css = str_replace( array( '\\', '"', '<', '>', "\n", "\r" ), array( '\\\\', '\\"', '', '', ' ', ' ' ), wp_strip_all_tags( html_entity_decode( $olicg_footer, ENT_QUOTES, 'UTF-8' ) ) );
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
$olicg_cover_text = static function ( $key ) use ( $olicg_cover_texts, $olicg_cover_values ) {
	return OLICG_Cover::fill( $olicg_cover_texts[ $key ], $olicg_cover_values );
};
$olicg_band = array();
foreach ( array( 1, 2, 3 ) as $olicg_n ) {
	// Without prices the automatic "Prices" box has nothing to say.
	if ( 2 === $olicg_n && ! $olicg_components && empty( $olicg_cover['texts']['band2_value'] ) ) {
		continue;
	}
	$olicg_box = array( $olicg_cover_text( 'band' . $olicg_n . '_label' ), $olicg_cover_text( 'band' . $olicg_n . '_value' ) );
	if ( '' !== $olicg_box[0] . $olicg_box[1] ) {
		$olicg_band[] = $olicg_box;
	}
}

/**
 * Zoomable image box (catalogue cards and price list pictures).
 */
$olicg_image_box = static function ( array $item ) use ( $settings, $olicg_layout ) {
	$fit    = OLICG_Catalog::image_fit( $settings, $olicg_layout, $item['id'] );
	$zoomed = 1.0 !== $fit['s'] || $fit['x'] || $fit['y'];
	?>
	<div class="card-img" data-s="<?php echo esc_attr( sprintf( '%.3F', $fit['s'] ) ); ?>" data-x="<?php echo esc_attr( sprintf( '%.2F', $fit['x'] ) ); ?>" data-y="<?php echo esc_attr( sprintf( '%.2F', $fit['y'] ) ); ?>">
		<img src="<?php echo esc_url( $item['image'] ); ?>" alt="<?php echo esc_attr( $item['name'] ); ?>" draggable="false"<?php echo $zoomed ? ' style="' . esc_attr( sprintf( 'transform: translate(%.2F%%, %.2F%%) scale(%.3F);', $fit['x'], $fit['y'], $fit['s'] ) ) . '"' : ''; ?>>
		<span class="img-zoom" title="<?php esc_attr_e( 'Drag to zoom the image · double-click the image to reset', 'oli-catalog-generator' ); ?>" aria-hidden="true"></span>
	</div>
	<?php
};
?><!doctype html>
<html lang="<?php echo esc_attr( $html_lang ); ?>">
<head>
<meta charset="utf-8">
<meta name="robots" content="noindex, nofollow, noarchive">
<meta name="referrer" content="no-referrer">
<title><?php echo esc_html( $settings['title'] . ' ' . $olicg_year . ' — ' . $olicg_edition ); ?></title>
<?php foreach ( OLICG_Design::font_urls( array( 'heading', 'body', 'mono', 'cover' ), array_merge( $olicg_design, array( 'font_cover' => $olicg_has_cover ? $olicg_cover['title_font'] : '' ) ) ) as $olicg_font_url ) : ?>
<link rel="stylesheet" href="<?php echo esc_url( $olicg_font_url ); ?>">
<?php endforeach; ?>
<style>
<?php echo $olicg_fonts_css; // phpcs:ignore WordPress.Security.EscapeOutput -- theme CSS file. ?>

:root {
<?php echo OLICG_Design::css_vars( $olicg_design ); // phpcs:ignore WordPress.Security.EscapeOutput -- sanitized hex colours and font names. ?>
	--cols: <?php echo (int) $olicg_columns; ?>;
}

@page {
	size: <?php echo 'a4' === $olicg_paper ? 'A4' : 'letter'; ?> portrait;
	margin: 0.5in 0.45in 0.6in;
	@bottom-left { content: "<?php echo $olicg_footer_css; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped for a CSS string above. ?>"; font: 7pt <?php echo $olicg_mono_css; // phpcs:ignore WordPress.Security.EscapeOutput ?>; color: <?php echo esc_html( $olicg_design['color_muted'] ); ?>; }
	@bottom-right { content: counter(page); font: 700 8pt <?php echo $olicg_mono_css; // phpcs:ignore WordPress.Security.EscapeOutput ?>; color: <?php echo esc_html( $olicg_design['color_text'] ); ?>; }
}
<?php if ( $olicg_has_cover ) : ?>
@page :first {
	<?php echo $olicg_bleed ? 'margin: 0;' : ''; ?>
	@bottom-left { content: none; }
	@bottom-right { content: none; }
}
<?php endif; ?>

* { box-sizing: border-box; }
[hidden] { display: none !important; }
html, body { margin: 0; padding: 0; }
body {
	font-family: var(--sans);
	color: var(--ink);
	background: #d4d4d8;
	-webkit-print-color-adjust: exact;
	print-color-adjust: exact;
}

/* Screen toolbar */
.toolbar {
	position: sticky; top: 0; z-index: 10;
	display: flex; align-items: center; justify-content: space-between; gap: 16px;
	padding: 12px 24px; background: #000; color: #fff;
	font: 12px var(--mono); letter-spacing: .04em;
}
.toolbar strong { text-transform: var(--label-case); letter-spacing: .12em; }
.toolbar .hint { color: #a1a1aa; }
.toolbar button {
	flex-shrink: 0; white-space: nowrap;
	background: #fff; color: #000; border: 0; padding: 10px 18px; cursor: pointer;
	font: 700 12px var(--mono); text-transform: var(--label-case); letter-spacing: .12em;
}
.toolbar button:hover { background: #e4e4e7; }
.toolbar-actions { display: flex; gap: 8px; flex-shrink: 0; }
.toolbar .undo { background: transparent; color: #fff; border: 1px solid #52525b; }
.toolbar .undo:hover { background: #27272a; }
.toolbar .status { color: #fff; margin-left: 6px; }

/* Arranging (screen only) */
.card { position: relative; }
.card-remove {
	position: absolute; top: 3px; right: 3px; z-index: 3;
	width: 20px; height: 20px; padding: 0; border: 0; border-radius: 50%;
	background: #000; color: #fff; font: 700 14px/20px Arial, sans-serif; text-align: center;
	cursor: pointer; opacity: 0; transition: opacity .12s, transform .12s;
}
.card:hover .card-remove, .card-remove:focus-visible { opacity: 1; }
.card-remove:hover { transform: scale(1.15); background: #dc2626; }
.layout-list .card-remove { top: 50%; right: auto; left: -4px; margin-top: -10px; }
.card-img img { transform-origin: 50% 50%; }
.img-zoom {
	position: absolute; right: 0; bottom: 0; z-index: 2;
	width: 16px; height: 16px; cursor: nwse-resize; opacity: 0; transition: opacity .12s;
	background: linear-gradient(135deg, transparent 0 45%, var(--ink) 45% 55%, transparent 55% 65%, var(--ink) 65% 75%, transparent 75%);
	touch-action: none;
}
.card:hover .img-zoom { opacity: .85; }
.card-img.is-zoomed img { cursor: move; }
.card-img.is-editing { outline: 2px solid var(--ink); outline-offset: -2px; }
@media screen {
	.card { cursor: grab; }
	.card:hover { outline: 1px solid var(--ink); outline-offset: -1px; }
	.card.is-dragging { opacity: .3; outline: 2px dashed var(--ink); }
	.card.is-removing { opacity: 0; transform: scale(.92); transition: opacity .2s, transform .2s; }
}

/* Screen preview: one long sheet at print width */
.doc {
	width: <?php echo 'a4' === $olicg_paper ? 'calc(210mm - 0.9in)' : '7.6in'; ?>;
	margin: 32px auto; background: var(--page); padding: 0.5in 0.45in;
	box-shadow: 0 10px 40px rgba(0,0,0,.15);
}

/* Cover */
.cover {
	position: relative;
	display: flex; flex-direction: column;
	min-height: <?php echo 'a4' === $olicg_paper ? '264mm' : '9.75in'; ?>;
	break-after: page;
	color: var(--cover-text, var(--ink));
	background-color: var(--cover-bg, transparent);
	background-size: cover; background-position: center; background-repeat: no-repeat;
}
.cover.has-bg-image::before { content: ""; position: absolute; inset: 0; background: var(--cover-bg, #fff); opacity: var(--cover-overlay, 0); pointer-events: none; }
.cover > * { position: relative; }
.cover-bleed {
	margin: -0.5in -0.45in 0.5in;
	padding: 0.5in 0.45in 0.6in;
	aspect-ratio: <?php echo 'a4' === $olicg_paper ? '210 / 297' : '8.5 / 11'; ?>;
	min-height: 0;
	overflow: hidden;
}
.cover-image { padding: 0; background-color: var(--cover-bg, var(--page)); }
.cover-image img { display: block; width: 100%; height: 100%; }
.cover-top { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid currentColor; padding-bottom: 18px; }
.cover-logo { height: var(--cover-logo-h, 54px); width: auto; display: block; }
.cover-wordmark { font: 700 28px var(--sans); letter-spacing: .2em; text-transform: var(--label-case); }
.cover-meta-top { text-align: right; font: 9pt var(--mono); color: var(--cover-muted, var(--muted)); text-transform: var(--label-case); letter-spacing: .12em; line-height: 1.7; }
.cover-main { flex: 1; display: flex; flex-direction: column; justify-content: center; padding: 40px 0; }
.eyebrow { font: 700 9pt var(--mono); text-transform: var(--label-case); letter-spacing: .25em; color: var(--muted); }
.cover .eyebrow { color: var(--cover-muted, var(--muted)); }
.cover-title { font: var(--heading-style) var(--heading-weight) var(--cover-title-size, 58pt)/1.02 var(--cover-title-font, var(--serif)); margin: 16px 0 0; letter-spacing: -.01em; }
.cover-year { font: inherit; color: var(--cover-muted, var(--muted)); }
.cover-band {
	background: var(--band); color: var(--band-text); padding: 22px 26px;
	display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px;
}
.cover-band .label { font: 7.5pt var(--mono); text-transform: var(--label-case); letter-spacing: .2em; opacity: .65; }
.cover-band .value { font: 700 12pt var(--mono); margin-top: 6px; text-transform: var(--label-case); letter-spacing: .06em; }
.cover-note { margin-top: 14px; font: 8pt var(--mono); color: var(--cover-muted, var(--muted)); line-height: 1.6; }

/* Sections */
.section + .section { margin-top: 0.35in; }
<?php if ( ! empty( $settings['section_new_page'] ) ) : ?>
.section + .section { margin-top: 0; break-before: page; }
<?php endif; ?>
.section-head {
	display: flex; align-items: flex-end; justify-content: space-between; gap: 16px;
	border-bottom: 2px solid var(--ink); padding-bottom: 8px; margin-bottom: 0.16in;
	break-after: avoid;
}
.section-title { font: var(--heading-style) var(--heading-weight) 24pt/1.05 var(--serif); margin: 2px 0 0; }
.section-count { font: 8pt var(--mono); color: var(--muted); text-transform: var(--label-case); letter-spacing: .15em; white-space: nowrap; }

/* Product grid */
.grid { display: grid; grid-template-columns: repeat(var(--cols), minmax(0, 1fr)); gap: 0.16in; }
.card { border: 1px solid var(--line); background: var(--page); break-inside: avoid; page-break-inside: avoid; display: flex; flex-direction: column; }
.card-img { position: relative; aspect-ratio: 3 / 2; overflow: hidden; background: var(--tile-bg); }
.card-img img { position: absolute; top: 6%; left: 10%; width: 80%; height: 88%; object-fit: contain; display: block; mix-blend-mode: var(--img-blend); filter: var(--img-shadow); }
.card-body { padding: 9px 10px 10px; display: flex; flex-direction: column; flex: 1; border-top: 1px solid var(--line); }
.card-title {
	font: 500 <?php echo 4 === $olicg_columns ? '8pt' : '9pt'; ?>/1.3 var(--sans); margin: 0;
	display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
}
.card-sku { font: 6.5pt var(--mono); color: var(--muted); text-transform: var(--label-case); letter-spacing: .1em; margin-top: 4px; }
.card-price { margin-top: auto; padding-top: 8px; display: flex; align-items: baseline; justify-content: space-between; gap: 6px; }
.card-price .amount { font: 700 <?php echo 4 === $olicg_columns ? '10pt' : '11.5pt'; ?> var(--mono); }
.card-price .currency { font: 6.5pt var(--mono); color: var(--muted); letter-spacing: .12em; }
.card-price .na { font: 7pt var(--mono); color: var(--muted); text-transform: var(--label-case); letter-spacing: .1em; }
.card-brand { font: 700 6.5pt var(--mono); color: var(--muted); text-transform: var(--label-case); letter-spacing: .14em; margin-bottom: 3px; }
.card-prices { margin-top: auto; padding-top: 6px; display: grid; gap: 1px; }
.price-row { display: flex; align-items: baseline; justify-content: space-between; gap: 6px; border-top: 1px dotted var(--line); padding-top: 2px; }
.price-row .lbl { font: 6.5pt var(--mono); color: var(--muted); text-transform: var(--label-case); letter-spacing: .12em; }
.price-row .amt { font: 700 9pt var(--mono); white-space: nowrap; }
.card-price .amount, .price-row .amt { color: var(--price); }

/* Compact grid: small images, dense rows */
.layout-compact .section + .section { margin-top: 0.22in; }
.layout-compact .section-head,
.layout-list .section-head { padding-bottom: 5px; margin-bottom: 0.1in; }
.layout-compact .section-title,
.layout-list .section-title { font-size: 16pt; }
.layout-compact .section-count,
.layout-list .section-count { font-size: 6.5pt; }
.layout-compact .grid { gap: 0.08in; }
.layout-compact .card-img img { top: 5%; left: 8%; width: 84%; height: 90%; }
.layout-compact .card-body { padding: 4px 5px 5px; }
.layout-compact .card-title { font-size: 6.8pt; line-height: 1.2; -webkit-line-clamp: 2; }
.layout-compact .card-sku { font-size: 5.3pt; margin-top: 2px; letter-spacing: .06em; }
.layout-compact .card-price { padding-top: 3px; }
.layout-compact .card-price .amount { font-size: 8pt; }
.layout-compact .card-price .currency,
.layout-compact .card-price .na { font-size: 5pt; }
.layout-compact .card-brand { font-size: 5pt; margin-bottom: 1px; }
.layout-compact .card-prices { padding-top: 3px; gap: 0; }
.layout-compact .price-row { padding-top: 1px; }
.layout-compact .price-row .lbl { font-size: 4.8pt; letter-spacing: .08em; }
.layout-compact .price-row .amt { font-size: 6.8pt; }

/* List: thumbnail rows in two columns */
.layout-list .section + .section { margin-top: 0.22in; }
.layout-list .grid { column-gap: 0.25in; row-gap: 0; }
.layout-list .card { flex-direction: row; align-items: center; border: 0; border-bottom: 1px solid var(--line); padding: 3px 0; }
.layout-list .card-img { flex: none; width: 0.45in; aspect-ratio: 1 / 1; }
.layout-list .card-img img { top: 6%; left: 6%; width: 88%; height: 88%; }
.layout-list .card-body {
	border: 0; padding: 0 0 0 8px; min-width: 0;
	display: grid; grid-template-columns: minmax(0, 1fr) auto; column-gap: 8px; align-content: center;
}
.layout-list .card-title { font-size: 7.5pt; line-height: 1.2; -webkit-line-clamp: 2; grid-column: 1; }
.layout-list .card-sku { font-size: 5.5pt; margin-top: 1px; grid-column: 1; }
.layout-list .card-price { grid-column: 2; grid-row: 1 / span 2; margin: 0; padding: 0; flex-direction: column; align-items: flex-end; justify-content: center; gap: 1px; }
.layout-list .card-price .amount { font-size: 8.5pt; white-space: nowrap; }
.layout-list .card-price .currency,
.layout-list .card-price .na { font-size: 5pt; }
.layout-list .card-brand { font-size: 5pt; margin-bottom: 0; grid-column: 1; }
.layout-list .card-prices { grid-column: 2; grid-row: 1 / span 3; margin: 0; padding: 0; gap: 0; min-width: 0.95in; align-content: center; }
.layout-list .price-row { border-top: 0; padding-top: 0; }
.layout-list .price-row .lbl { font-size: 4.8pt; }
.layout-list .price-row .amt { font-size: 7pt; }
.layout-list .card-price { grid-row: 1 / span 3; }
.layout-list.no-images .card { padding: 4px 0; }
.layout-list.no-images .card-body { padding-left: 0; }
.no-images .card-body { border-top: 0; }

/* Price list: a table per category, chosen pictures below it */
.layout-table .section + .section { margin-top: 0.25in; }
.pl { width: 100%; border-collapse: collapse; font: 7pt/1.25 var(--sans); }
.pl thead { display: table-header-group; }
.pl tr { break-inside: avoid; page-break-inside: avoid; }
.pl-band th { background: var(--band); color: var(--band-text); padding: 5px 8px; text-align: left; }
.pl-title { font: var(--heading-style) 700 10pt var(--sans); text-transform: uppercase; letter-spacing: .04em; }
.pl-eyebrow { font-weight: 400; opacity: .65; }
.pl-edition { float: right; margin-top: 2px; font: 700 8pt var(--mono); text-transform: uppercase; letter-spacing: .12em; }
.pl-cols th {
	background: color-mix(in srgb, var(--band) 70%, #fff); color: var(--band-text);
	padding: 3px 6px; text-align: left; border: 1px solid var(--line);
	font: 700 6.3pt var(--mono); text-transform: var(--label-case); letter-spacing: .1em; white-space: nowrap;
}
.pl td { border: 1px solid var(--line); padding: 2px 6px; vertical-align: middle; }
.pl .c-sku, .pl .c-upc { font: 6.3pt var(--mono); white-space: nowrap; }
.pl td.c-brand { white-space: nowrap; }
.pl td.c-name { position: relative; width: 100%; }
.pl .c-price { width: 0.62in; text-align: right !important; white-space: nowrap; }
.pl td.c-price { font: 700 7pt var(--mono); color: var(--price); }
.row-actions { position: absolute; top: 50%; right: 3px; transform: translateY(-50%); display: flex; gap: 3px; }
.row-actions .card-remove { position: static; width: 16px; height: 16px; font-size: 11px; line-height: 16px; }
.pic-toggle {
	width: 16px; height: 16px; padding: 0; border: 1px solid var(--ink); border-radius: 50%;
	background: var(--ink); color: #fff; font: 9px/14px Arial, sans-serif; text-align: center;
	cursor: pointer; opacity: 0; transition: opacity .12s;
}
.row.no-pic .pic-toggle { background: #fff; color: var(--muted); border-style: dashed; }
.row:hover .card-remove, .row:hover .pic-toggle, .pic-toggle:focus-visible { opacity: 1; }
.pics-bar { margin-top: 4px; text-align: right; font: 7pt var(--mono); color: var(--muted); text-transform: var(--label-case); letter-spacing: .08em; }
.pics-bar button { margin-left: 6px; padding: 2px 8px; border: 1px solid var(--line); background: #fff; color: var(--ink); font: inherit; cursor: pointer; }
.pics-bar button:hover { border-color: var(--ink); }
.pics { display: grid; grid-template-columns: repeat(var(--cols), minmax(0, 1fr)); gap: 0.06in 0.12in; margin-top: 0.1in; }
.pics:not(:has(.pic:not([hidden]))) { display: none; }
.pic { position: relative; margin: 0; break-inside: avoid; page-break-inside: avoid; }
.pic .card-img { aspect-ratio: 4 / 3; }
.pic .card-img img { top: 4%; left: 4%; width: 92%; height: 92%; }
.pic figcaption { margin-top: 2px; font: 5.8pt var(--mono); color: var(--muted); text-align: center; letter-spacing: .06em; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pic-remove {
	position: absolute; top: 3px; right: 3px; z-index: 3;
	width: 18px; height: 18px; padding: 0; border: 0; border-radius: 50%;
	background: #000; color: #fff; font: 700 12px/18px Arial, sans-serif; text-align: center;
	cursor: pointer; opacity: 0; transition: opacity .12s, transform .12s;
}
.pic:hover .pic-remove, .pic:hover .img-zoom, .pic-remove:focus-visible { opacity: 1; }
.pic-remove:hover { transform: scale(1.15); background: #dc2626; }
@media screen {
	.row, .pic { cursor: grab; }
	.row:hover td { background: #f4f4f5; }
	.pic:hover { outline: 1px solid var(--ink); outline-offset: 1px; }
	.row.is-dragging, .pic.is-dragging { opacity: .3; }
	.row.is-removing, .pic.is-removing { opacity: 0; transition: opacity .2s; }
}

.closing { margin-top: 0.4in; padding-top: 12px; border-top: 1px solid var(--line); font: 7.5pt/1.6 var(--mono); color: var(--muted); break-inside: avoid; }
.empty { font: 11pt var(--mono); color: var(--muted); padding: 40px 0; }

@media print {
	body { background: var(--page); }
	.cover-bleed { margin: 0; aspect-ratio: auto; height: <?php echo 'a4' === $olicg_paper ? '297mm' : '11in'; ?>; }
	.toolbar, .card-remove, .img-zoom, .row-actions, .pic-remove, .pics-bar { display: none !important; }
	.row:hover td { background: none; }
	.card-img { outline: 0 !important; }
	.doc { width: auto; margin: 0; padding: 0; box-shadow: none; }
}

<?php echo OLICG_Design::custom_css( $olicg_design ); // phpcs:ignore WordPress.Security.EscapeOutput -- tags stripped. ?>
</style>
</head>
<body class="layout-<?php echo esc_attr( $olicg_layout ); ?><?php echo $olicg_show_img ? '' : ' no-images'; ?>">

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
		<button type="button" onclick="olicgPrint()"><?php esc_html_e( 'Print / Save as PDF', 'oli-catalog-generator' ); ?></button>
	</div>
</div>

<main class="doc">

	<?php if ( 'image' === $olicg_cover_mode ) : ?>
		<section class="cover cover-bleed cover-image" style="<?php echo esc_attr( OLICG_Cover::style( $olicg_cover ) ); ?>">
			<img src="<?php echo esc_url( $olicg_cover['image_url'] ); ?>" alt="<?php echo esc_attr( $settings['title'] ); ?>" style="object-fit: <?php echo 'contain' === $olicg_cover['image_fit'] ? 'contain' : 'cover'; ?>;">
		</section>
	<?php elseif ( 'design' === $olicg_cover_mode ) : ?>
		<section class="cover<?php echo OLICG_Cover::is_bleed( $olicg_cover ) ? ' cover-bleed' : ''; ?><?php echo $olicg_cover['bg_image_url'] ? ' has-bg-image' : ''; ?>" style="<?php echo esc_attr( OLICG_Cover::style( $olicg_cover ) ); ?>">
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
				<div class="cover-band" style="grid-template-columns: repeat(<?php echo (int) count( $olicg_band ); ?>, 1fr);">
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
	<?php foreach ( $sections as $section ) : ?>
		<section class="section">
		<?php if ( $olicg_is_table ) : ?>
			<table class="pl">
				<thead>
					<tr class="pl-band">
						<th colspan="<?php echo (int) ( count( $olicg_cols ) + count( $olicg_components ) ); ?>">
							<span class="pl-title">
								<?php if ( '' !== $section['eyebrow'] ) : ?>
									<span class="pl-eyebrow"><?php echo esc_html( $section['eyebrow'] ); ?> ›</span>
								<?php endif; ?>
								<?php echo esc_html( $section['title'] ); ?>
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
					<?php foreach ( $section['items'] as $item ) : ?>
						<tr class="row<?php echo $item['picture'] ? '' : ' no-pic'; ?>" draggable="true" data-id="<?php echo esc_attr( $item['id'] ); ?>">
							<?php foreach ( $olicg_cols as $olicg_key => $olicg_label ) : ?>
								<td class="c-<?php echo esc_attr( $olicg_key ); ?>">
									<?php if ( 'name' === $olicg_key ) : ?>
										<span class="row-actions">
											<?php if ( $olicg_show_img && $item['has_img'] ) : ?>
												<button type="button" class="pic-toggle" title="<?php esc_attr_e( 'Show or hide this picture below the table', 'oli-catalog-generator' ); ?>" aria-label="<?php esc_attr_e( 'Show or hide this picture below the table', 'oli-catalog-generator' ); ?>">◩</button>
											<?php endif; ?>
											<button type="button" class="card-remove" title="<?php esc_attr_e( 'Remove from catalogue', 'oli-catalog-generator' ); ?>" aria-label="<?php esc_attr_e( 'Remove from catalogue', 'oli-catalog-generator' ); ?>">×</button>
										</span>
									<?php endif; ?>
									<?php echo esc_html( $item[ $olicg_key ] ); ?>
								</td>
							<?php endforeach; ?>
							<?php foreach ( $olicg_components as $olicg_key => $olicg_label ) : ?>
								<td class="c-price"><?php echo $item['prices'][ $olicg_key ] ? esc_html( OLICG_Pricing::format( $item['prices'][ $olicg_key ], $region, false ) ) : '—'; ?></td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ( $olicg_show_img && array_filter( array_column( $section['items'], 'has_img' ) ) ) : ?>
				<div class="pics-bar">
					<?php esc_html_e( 'Pictures:', 'oli-catalog-generator' ); ?>
					<button type="button" data-show="1"><?php esc_html_e( 'Show all', 'oli-catalog-generator' ); ?></button>
					<button type="button" data-show="0"><?php esc_html_e( 'Hide all', 'oli-catalog-generator' ); ?></button>
				</div>
				<div class="pics">
					<?php foreach ( $section['items'] as $item ) : ?>
						<?php if ( $item['has_img'] ) : ?>
							<figure class="pic" draggable="true" data-id="<?php echo esc_attr( $item['id'] ); ?>"<?php echo $item['picture'] ? '' : ' hidden'; ?>>
								<button type="button" class="pic-remove" title="<?php esc_attr_e( 'Hide this picture', 'oli-catalog-generator' ); ?>" aria-label="<?php esc_attr_e( 'Hide this picture', 'oli-catalog-generator' ); ?>">×</button>
								<?php $olicg_image_box( $item ); ?>
								<figcaption><?php echo esc_html( '' !== $item['sku'] ? $item['sku'] : $item['name'] ); ?></figcaption>
							</figure>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		<?php else : ?>
			<header class="section-head">
				<div>
					<?php if ( '' !== $section['eyebrow'] ) : ?>
						<div class="eyebrow"><?php echo esc_html( $section['eyebrow'] ); ?></div>
					<?php endif; ?>
					<h2 class="section-title"><?php echo esc_html( $section['title'] ); ?></h2>
				</div>
				<div class="section-count"><?php /* translators: %d: number of products */ echo esc_html( sprintf( _n( '%d product', '%d products', count( $section['items'] ), 'oli-catalog-generator' ), count( $section['items'] ) ) ); ?></div>
			</header>

			<div class="grid">
				<?php foreach ( $section['items'] as $item ) : ?>
					<article class="card" draggable="true" data-id="<?php echo esc_attr( $item['id'] ); ?>">
						<button type="button" class="card-remove" title="<?php esc_attr_e( 'Remove from catalogue', 'oli-catalog-generator' ); ?>" aria-label="<?php esc_attr_e( 'Remove from catalogue', 'oli-catalog-generator' ); ?>">×</button>
						<?php if ( $olicg_show_img ) : ?>
							<?php $olicg_image_box( $item ); ?>
						<?php endif; ?>
						<div class="card-body">
							<?php if ( ! empty( $settings['show_brand'] ) && '' !== $item['brand'] ) : ?>
								<div class="card-brand"><?php echo esc_html( $item['brand'] ); ?></div>
							<?php endif; ?>
							<h3 class="card-title"><?php echo esc_html( $item['name'] ); ?></h3>
							<?php if ( ! empty( $settings['show_sku'] ) && '' !== $item['sku'] ) : ?>
								<div class="card-sku"><?php echo esc_html( 'SKU ' . $item['sku'] ); ?></div>
							<?php endif; ?>
							<?php if ( ! empty( $settings['show_upc'] ) && '' !== $item['upc'] ) : ?>
								<div class="card-sku card-upc"><?php echo esc_html( 'UPC ' . $item['upc'] ); ?></div>
							<?php endif; ?>
							<?php if ( ! $olicg_components ) : ?>
							<?php elseif ( $olicg_multi ) : ?>
								<div class="card-prices">
									<?php foreach ( $olicg_components as $olicg_key => $olicg_label ) : ?>
										<div class="price-row">
											<span class="lbl"><?php echo esc_html( $olicg_label ); ?></span>
											<span class="amt"><?php echo $item['prices'][ $olicg_key ] ? esc_html( OLICG_Pricing::format( $item['prices'][ $olicg_key ], $region ) ) : '—'; ?></span>
										</div>
									<?php endforeach; ?>
								</div>
							<?php else : ?>
								<?php $olicg_price = reset( $item['prices'] ); ?>
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

	<?php if ( $sections ) : ?>
		<div class="closing">
			<?php
			if ( $olicg_components ) {
				echo esc_html( sprintf(
					/* translators: 1: site name, 2: currency, 3: date, 4: site URL */
					__( '© %1$s. All prices in %2$s, current as of %3$s. Specifications, prices and availability subject to change without notice. %4$s', 'oli-catalog-generator' ),
					$olicg_year . ' ' . get_bloginfo( 'name' ),
					$currency,
					$olicg_date,
					wp_parse_url( home_url(), PHP_URL_HOST )
				) );
			} else {
				echo esc_html( sprintf(
					/* translators: 1: site name, 2: date, 3: site URL */
					__( '© %1$s. Current as of %2$s. Specifications and availability subject to change without notice. %3$s', 'oli-catalog-generator' ),
					$olicg_year . ' ' . get_bloginfo( 'name' ),
					$olicg_date,
					wp_parse_url( home_url(), PHP_URL_HOST )
				) );
			}
			?>
		</div>
	<?php endif; ?>
</main>

<script>
( function () {
	var cfg = <?php echo wp_json_encode( array(
		'url'     => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'olicg_arrange' ),
		'layout'  => $olicg_layout,
		'item'    => $olicg_is_table ? '.row' : '.card',
		/* translators: %d: number of products */
		'one'     => _n( '%d product', '%d products', 1, 'oli-catalog-generator' ),
		/* translators: %d: number of products */
		'many'    => _n( '%d product', '%d products', 2, 'oli-catalog-generator' ),
		'saving'  => __( 'Saving…', 'oli-catalog-generator' ),
		'saved'   => __( 'Saved ✓', 'oli-catalog-generator' ),
		'failed'  => __( 'Could not save — reload and try again.', 'oli-catalog-generator' ),
	) ); ?>;
	var statusEl = document.querySelector( '.toolbar .status' );
	var undoBtn = document.querySelector( '.toolbar .undo' );
	var removed = [];
	var dragging = null;
	var before = '';

	function status( text ) { if ( statusEl ) { statusEl.textContent = text; } }
	function label( n ) { return ( n === 1 ? cfg.one : cfg.many ).replace( '%d', n ); }

	function post( data ) {
		var body = new URLSearchParams();
		body.append( 'action', 'olicg_arrange' );
		body.append( '_ajax_nonce', cfg.nonce );
		Object.keys( data ).forEach( function ( key ) {
			[].concat( data[ key ] ).forEach( function ( value ) {
				body.append( Array.isArray( data[ key ] ) ? key + '[]' : key, value );
			} );
		} );
		status( cfg.saving );
		return fetch( cfg.url, { method: 'POST', credentials: 'same-origin', body: body } )
			.then( function ( res ) { return res.json(); } )
			.then( function ( json ) {
				if ( ! json || ! json.success ) { throw new Error( 'save failed' ); }
				status( cfg.saved );
				return json.data || {};
			} )
			.catch( function ( err ) { status( cfg.failed ); throw err; } );
	}

	function visibleCards( root ) {
		return Array.prototype.filter.call( ( root || document ).querySelectorAll( cfg.item ), function ( card ) { return ! card.hidden; } );
	}
	function orderKey() { return visibleCards().map( function ( card ) { return card.dataset.id; } ).join( ',' ); }

	function updateCounts() {
		document.querySelectorAll( '.section' ).forEach( function ( section ) {
			var n = visibleCards( section ).length;
			section.hidden = n === 0;
			var count = section.querySelector( '.section-count' );
			if ( count ) { count.textContent = label( n ); }
		} );
		var total = visibleCards().length;
		document.querySelectorAll( '.js-total' ).forEach( function ( el ) { el.textContent = total; } );
		document.querySelectorAll( '.js-total-label' ).forEach( function ( el ) { el.textContent = label( total ); } );
		undoBtn.hidden = removed.length === 0;
	}

	function childOf( container, el ) {
		var item = el.closest && el.closest( '[data-id]' );
		return item && item.parentNode === container ? item : null;
	}

	function picOf( item ) {
		var section = item.closest( '.section' );
		return section ? section.querySelector( '.pic[data-id="' + item.dataset.id + '"]' ) : null;
	}

	// Price list: the table rows and the pictures below them share one order.
	function mirror( container ) {
		var section = container.closest( '.section' );
		var rows = section.querySelector( '.rows' );
		var pics = section.querySelector( '.pics' );
		if ( ! rows || ! pics ) { return; }
		if ( container === rows ) {
			Array.prototype.forEach.call( rows.children, function ( row ) {
				var pic = picOf( row );
				if ( pic ) { pics.appendChild( pic ); }
			} );
			return;
		}
		var all = Array.prototype.slice.call( rows.children );
		var picIds = Array.prototype.map.call( pics.children, function ( pic ) { return pic.dataset.id; } );
		var slots = [];
		var byId = {};
		all.forEach( function ( row, i ) {
			byId[ row.dataset.id ] = row;
			if ( picIds.indexOf( row.dataset.id ) !== -1 ) { slots.push( i ); }
		} );
		picIds.forEach( function ( id, k ) { all[ slots[ k ] ] = byId[ id ]; } );
		all.forEach( function ( row ) { rows.appendChild( row ); } );
	}

	document.querySelectorAll( '.grid, .rows, .pics' ).forEach( function ( container ) {
		container.addEventListener( 'dragstart', function ( e ) {
			var item = childOf( container, e.target );
			if ( ! item ) { return; }
			dragging = item;
			before = orderKey();
			item.classList.add( 'is-dragging' );
			e.dataTransfer.effectAllowed = 'move';
			e.dataTransfer.setData( 'text/plain', item.dataset.id );
		} );
		container.addEventListener( 'dragover', function ( e ) {
			if ( ! dragging || dragging.parentNode !== container ) { return; }
			e.preventDefault();
			var over = childOf( container, e.target );
			if ( ! over || over === dragging ) { return; }
			var items = Array.prototype.slice.call( container.children );
			if ( items.indexOf( dragging ) < items.indexOf( over ) ) {
				over.after( dragging );
			} else {
				over.before( dragging );
			}
		} );
		container.addEventListener( 'drop', function ( e ) {
			if ( dragging && dragging.parentNode === container ) {
				e.preventDefault();
				finishDrag();
			}
		} );
		container.addEventListener( 'dragend', finishDrag );
	} );

	function finishDrag() {
		if ( ! dragging ) { return; }
		var container = dragging.parentNode;
		dragging.classList.remove( 'is-dragging' );
		dragging = null;
		mirror( container );
		if ( orderKey() !== before ) {
			post( { op: 'order', ids: orderKey().split( ',' ) } );
		}
	}

	function setPicture( pic, show ) {
		var row = document.querySelector( '.row[data-id="' + pic.dataset.id + '"]' );
		pic.hidden = ! show;
		if ( row ) { row.classList.toggle( 'no-pic', ! show ); }
		return post( { op: 'picture', id: pic.dataset.id, show: show ? 1 : 0 } );
	}

	document.addEventListener( 'click', function ( e ) {
		var bulk = e.target.closest && e.target.closest( '.pics-bar button' );
		if ( bulk ) {
			var show = bulk.dataset.show === '1';
			var section = bulk.closest( '.section' );
			var ids = [];
			section.querySelectorAll( '.pic' ).forEach( function ( pic ) {
				var row = section.querySelector( '.row[data-id="' + pic.dataset.id + '"]' );
				if ( row && row.hidden ) { return; }
				pic.hidden = ! show;
				if ( row ) { row.classList.toggle( 'no-pic', ! show ); }
				ids.push( pic.dataset.id );
			} );
			if ( ids.length ) { post( { op: 'picture', ids: ids, show: show ? 1 : 0 } ); }
			return;
		}
		var toggle = e.target.closest && e.target.closest( '.pic-toggle' );
		if ( toggle ) {
			var togglePic = picOf( toggle.closest( '.row' ) );
			if ( togglePic ) { setPicture( togglePic, togglePic.hidden ); }
			return;
		}
		var hide = e.target.closest && e.target.closest( '.pic-remove' );
		if ( hide ) {
			var pic = hide.closest( '.pic' );
			setPicture( pic, false ).then( function () {
				removed.push( { pic: pic } );
				updateCounts();
			} );
			return;
		}
		var btn = e.target.closest && e.target.closest( '.card-remove' );
		if ( ! btn ) { return; }
		var card = btn.closest( cfg.item );
		var cardPic = picOf( card );
		card.classList.add( 'is-removing' );
		post( { op: 'remove', id: card.dataset.id } ).then( function ( data ) {
			card.hidden = true;
			card.classList.remove( 'is-removing' );
			removed.push( { card: card, wasAdded: data.was_added ? 1 : 0, pic: cardPic, picHidden: cardPic ? cardPic.hidden : true } );
			if ( cardPic ) { cardPic.hidden = true; }
			updateCounts();
		}, function () {
			card.classList.remove( 'is-removing' );
		} );
	} );

	function fitOf( box ) {
		return { s: parseFloat( box.dataset.s ) || 1, x: parseFloat( box.dataset.x ) || 0, y: parseFloat( box.dataset.y ) || 0 };
	}
	function applyFit( box, fit ) {
		box.dataset.s = fit.s;
		box.dataset.x = fit.x;
		box.dataset.y = fit.y;
		box.querySelector( 'img' ).style.transform = ( fit.s === 1 && ! fit.x && ! fit.y ) ? '' : 'translate(' + fit.x + '%, ' + fit.y + '%) scale(' + fit.s + ')';
		box.classList.toggle( 'is-zoomed', fit.s !== 1 || !! fit.x || !! fit.y );
	}
	function saveFit( box ) {
		var fit = fitOf( box );
		post( { op: 'image', id: box.closest( '[data-id]' ).dataset.id, layout: cfg.layout, s: fit.s, x: fit.x, y: fit.y } );
	}
	function clamp( v, min, max ) { return Math.min( max, Math.max( min, v ) ); }

	document.querySelectorAll( '.card-img' ).forEach( function ( box ) {
		var img = box.querySelector( 'img' );
		applyFit( box, fitOf( box ) );

		function track( e, onMove ) {
			var card = box.closest( '[data-id]' );
			var target = e.target;
			e.preventDefault();
			e.stopPropagation();
			card.draggable = false;
			box.classList.add( 'is-editing' );
			try { target.setPointerCapture( e.pointerId ); } catch ( err ) {}
			var startX = e.clientX, startY = e.clientY, start = fitOf( box );
			function move( ev ) { onMove( ev.clientX - startX, ev.clientY - startY, start ); }
			function up() {
				target.removeEventListener( 'pointermove', move );
				target.removeEventListener( 'pointerup', up );
				target.removeEventListener( 'pointercancel', up );
				card.draggable = true;
				box.classList.remove( 'is-editing' );
				var end = fitOf( box );
				if ( end.s !== start.s || end.x !== start.x || end.y !== start.y ) { saveFit( box ); }
			}
			target.addEventListener( 'pointermove', move );
			target.addEventListener( 'pointerup', up );
			target.addEventListener( 'pointercancel', up );
		}

		box.querySelector( '.img-zoom' ).addEventListener( 'pointerdown', function ( e ) {
			var size = Math.max( box.clientWidth, box.clientHeight );
			track( e, function ( dx, dy, start ) {
				var s = clamp( start.s + ( dx + dy ) / size * 1.5, 0.3, 5 );
				applyFit( box, { s: Math.round( s * 1000 ) / 1000, x: start.x, y: start.y } );
			} );
		} );

		img.addEventListener( 'pointerdown', function ( e ) {
			if ( ! box.classList.contains( 'is-zoomed' ) ) { return; }
			track( e, function ( dx, dy, start ) {
				applyFit( box, {
					s: start.s,
					x: Math.round( clamp( start.x + dx / img.offsetWidth * 100, -150, 150 ) * 100 ) / 100,
					y: Math.round( clamp( start.y + dy / img.offsetHeight * 100, -150, 150 ) * 100 ) / 100
				} );
			} );
		} );

		box.addEventListener( 'dblclick', function ( e ) {
			e.preventDefault();
			if ( box.classList.contains( 'is-zoomed' ) ) {
				applyFit( box, { s: 1, x: 0, y: 0 } );
				saveFit( box );
			}
		} );
	} );

	undoBtn.addEventListener( 'click', function () {
		var last = removed.pop();
		if ( ! last ) { return; }
		if ( ! last.card ) {
			setPicture( last.pic, true );
			updateCounts();
			return;
		}
		last.card.hidden = false;
		if ( last.pic ) { last.pic.hidden = last.picHidden; }
		updateCounts();
		post( { op: 'restore', id: last.card.dataset.id, was_added: last.wasAdded } );
	} );
} )();

function olicgPrint() {
	var pending = Array.prototype.filter.call( document.images, function ( img ) { return ! img.complete; } );
	if ( ! pending.length ) { window.print(); return; }
	var left = pending.length;
	pending.forEach( function ( img ) {
		var done = function () { if ( --left === 0 ) { window.print(); } };
		img.addEventListener( 'load', done, { once: true } );
		img.addEventListener( 'error', done, { once: true } );
	} );
}
</script>
</body>
</html>
