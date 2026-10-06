<?php
/**
 * Printable catalogue.
 *
 * Available: $settings, $sections, $region, $price_type, $currency, $logo_url, $regions, $types.
 */

defined( 'ABSPATH' ) || exit;

$olicg_fonts_file = apply_filters( 'olicg_fonts_css_file', get_template_directory() . '/assets/fonts/fonts-local.css' );
$olicg_fonts_css  = is_readable( $olicg_fonts_file )
	? str_replace( '__FONTS__', esc_url( get_template_directory_uri() . '/assets/fonts' ), (string) file_get_contents( $olicg_fonts_file ) )
	: '';

$olicg_design    = OLICG_Design::get_settings();
$olicg_stacks    = OLICG_Design::stacks( $olicg_design );
$olicg_mono_css  = str_replace( array( '<', '>', '{', '}', ';' ), '', $olicg_stacks['mono'] );

$olicg_is_dealer = in_array( $price_type, array( 'dealer', 'all' ), true );
$olicg_components = OLICG_Pricing::components( $price_type );
$olicg_multi     = count( $olicg_components ) > 1;
$olicg_prices_lbl = $olicg_multi
	? implode( ' · ', $olicg_components )
	: ( 'dealer' === $price_type ? __( 'Dealer', 'oli-catalog-generator' ) : __( 'End-user', 'oli-catalog-generator' ) );
$olicg_year      = wp_date( 'Y' );
$olicg_date      = wp_date( 'F j, Y' );
$olicg_count     = array_sum( array_map( static function ( $section ) { return count( $section['items'] ); }, $sections ) );
$olicg_market    = $regions[ $region ]['label'];
$olicg_price_lbl = $olicg_is_dealer ? __( 'Dealer price list', 'oli-catalog-generator' ) : __( 'Suggested retail prices', 'oli-catalog-generator' );
$olicg_footer    = sprintf( '%s — %s %s · %s · %s', get_bloginfo( 'name' ), $settings['title'], $olicg_year, $olicg_market, $currency );
$olicg_footer_css = str_replace( array( '\\', '"', '<', '>', "\n", "\r" ), array( '\\\\', '\\"', '', '', ' ', ' ' ), wp_strip_all_tags( html_entity_decode( $olicg_footer, ENT_QUOTES, 'UTF-8' ) ) );
$olicg_layout    = isset( OLICG_Catalog::layouts()[ $settings['layout'] ] ) ? $settings['layout'] : 'compact';
$olicg_columns   = 'list' === $olicg_layout ? 2 : max( 2, min( 'grid' === $olicg_layout ? 4 : 6, (int) $settings['columns'] ) );
$olicg_paper     = 'a4' === $settings['paper'] ? 'a4' : 'letter';
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="robots" content="noindex, nofollow, noarchive">
<meta name="referrer" content="no-referrer">
<title><?php echo esc_html( $settings['title'] . ' ' . $olicg_year . ' — ' . $olicg_market . ' (' . $types[ $price_type ] . ')' ); ?></title>
<?php foreach ( OLICG_Design::font_urls( array( 'heading', 'body', 'mono' ), $olicg_design ) as $olicg_font_url ) : ?>
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
@page :first {
	@bottom-left { content: none; }
	@bottom-right { content: none; }
}

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
@media screen {
	.card { cursor: grab; }
	.card:hover { outline: 1px solid var(--ink); outline-offset: -1px; }
	.card.is-dragging { opacity: .3; outline: 2px dashed var(--ink); }
	.card.is-removing { opacity: 0; transform: scale(.92); transition: opacity .2s, transform .2s; }
}

/* Screen preview: one long sheet at print width */
.doc {
	width: <?php echo 'a4' === $olicg_paper ? 'calc(210mm - 0.9in)' : '7.6in'; ?>;
	margin: 32px auto; background: #fff; padding: 0.5in 0.45in;
	box-shadow: 0 10px 40px rgba(0,0,0,.15);
}

/* Cover */
.cover {
	display: flex; flex-direction: column;
	min-height: <?php echo 'a4' === $olicg_paper ? '264mm' : '9.75in'; ?>;
	break-after: page;
}
.cover-top { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid var(--ink); padding-bottom: 18px; }
.cover-logo { height: 54px; width: auto; }
.cover-wordmark { font: 700 28px var(--sans); letter-spacing: .2em; }
.cover-meta-top { text-align: right; font: 9pt var(--mono); color: var(--muted); text-transform: var(--label-case); letter-spacing: .12em; line-height: 1.7; }
.cover-main { flex: 1; display: flex; flex-direction: column; justify-content: center; padding: 40px 0; }
.eyebrow { font: 700 9pt var(--mono); text-transform: var(--label-case); letter-spacing: .25em; color: var(--muted); }
.cover-title { font: var(--heading-style) var(--heading-weight) 58pt/1.02 var(--serif); margin: 16px 0 0; letter-spacing: -.01em; }
.cover-year { font: var(--heading-style) var(--heading-weight) 58pt/1.02 var(--serif); color: var(--muted); }
.cover-band {
	background: var(--band); color: var(--band-text); padding: 22px 26px;
	display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px;
}
.cover-band .label { font: 7.5pt var(--mono); text-transform: var(--label-case); letter-spacing: .2em; opacity: .65; }
.cover-band .value { font: 700 12pt var(--mono); margin-top: 6px; text-transform: var(--label-case); letter-spacing: .06em; }
.cover-note { margin-top: 14px; font: 8pt var(--mono); color: var(--muted); line-height: 1.6; }

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
.card { border: 1px solid var(--line); background: #fff; break-inside: avoid; page-break-inside: avoid; display: flex; flex-direction: column; }
.card-img { position: relative; aspect-ratio: 3 / 2; overflow: hidden; background: var(--tile); }
.card-img img { position: absolute; top: 6%; left: 10%; width: 80%; height: 88%; object-fit: contain; display: block; mix-blend-mode: multiply; }
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

.closing { margin-top: 0.4in; padding-top: 12px; border-top: 1px solid var(--line); font: 7.5pt/1.6 var(--mono); color: var(--muted); break-inside: avoid; }
.empty { font: 11pt var(--mono); color: var(--muted); padding: 40px 0; }

@media print {
	body { background: #fff; }
	.toolbar, .card-remove { display: none !important; }
	.doc { width: auto; margin: 0; padding: 0; box-shadow: none; }
}

<?php echo OLICG_Design::custom_css( $olicg_design ); // phpcs:ignore WordPress.Security.EscapeOutput -- tags stripped. ?>
</style>
</head>
<body class="layout-<?php echo esc_attr( $olicg_layout ); ?>">

<div class="toolbar">
	<div>
		<strong><?php echo esc_html( $settings['title'] ); ?></strong>
		· <?php echo esc_html( $olicg_market . ' · ' . $types[ $price_type ] ); ?> · <span class="js-total-label"><?php echo esc_html( sprintf( _n( '%d product', '%d products', $olicg_count, 'oli-catalog-generator' ), $olicg_count ) ); ?></span>
		<div class="hint"><?php esc_html_e( 'Drag products to reorder · hover and click × to remove — changes save automatically.', 'oli-catalog-generator' ); ?> <span class="status" aria-live="polite"></span></div>
		<div class="hint"><?php esc_html_e( 'Chrome / Edge → Print → Save as PDF. Margins: Default · Headers and footers: off · Background graphics: on.', 'oli-catalog-generator' ); ?></div>
	</div>
	<div class="toolbar-actions">
		<button type="button" class="undo" hidden><?php esc_html_e( 'Undo remove', 'oli-catalog-generator' ); ?></button>
		<button type="button" onclick="olicgPrint()"><?php esc_html_e( 'Print / Save as PDF', 'oli-catalog-generator' ); ?></button>
	</div>
</div>

<main class="doc">

	<section class="cover">
		<div class="cover-top">
			<?php if ( $logo_url ) : ?>
				<img class="cover-logo" src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
			<?php else : ?>
				<div class="cover-wordmark"><?php echo esc_html( strtoupper( get_bloginfo( 'name' ) ) ); ?></div>
			<?php endif; ?>
			<div class="cover-meta-top">
				<?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?><br>
				<?php echo esc_html( $olicg_date ); ?>
			</div>
		</div>

		<div class="cover-main">
			<div class="eyebrow"><?php echo esc_html( $olicg_price_lbl . ' · ' . $olicg_market ); ?></div>
			<h1 class="cover-title"><?php echo esc_html( $settings['title'] ); ?><br><span class="cover-year"><?php echo esc_html( $olicg_year ); ?></span></h1>
		</div>

		<div class="cover-band">
			<div><div class="label"><?php esc_html_e( 'Market', 'oli-catalog-generator' ); ?></div><div class="value"><?php echo esc_html( $olicg_market ); ?></div></div>
			<div><div class="label"><?php esc_html_e( 'Prices', 'oli-catalog-generator' ); ?></div><div class="value"><?php echo esc_html( $olicg_prices_lbl . ' · ' . $currency ); ?></div></div>
			<div><div class="label"><?php esc_html_e( 'Products', 'oli-catalog-generator' ); ?></div><div class="value js-total"><?php echo esc_html( $olicg_count ); ?></div></div>
		</div>
		<div class="cover-note">
			<?php
			echo esc_html( $olicg_is_dealer
				? __( 'Confidential dealer pricing — not for public distribution. Prices subject to change without notice.', 'oli-catalog-generator' )
				: __( 'Suggested retail prices. Prices and availability subject to change without notice.', 'oli-catalog-generator' ) );
			?>
		</div>
	</section>

	<?php if ( ! $sections ) : ?>
		<p class="empty"><?php esc_html_e( 'No products match this selection.', 'oli-catalog-generator' ); ?></p>
	<?php endif; ?>

	<?php foreach ( $sections as $section ) : ?>
		<section class="section">
			<header class="section-head">
				<div>
					<?php if ( '' !== $section['eyebrow'] ) : ?>
						<div class="eyebrow"><?php echo esc_html( $section['eyebrow'] ); ?></div>
					<?php endif; ?>
					<h2 class="section-title"><?php echo esc_html( $section['title'] ); ?></h2>
				</div>
				<div class="section-count"><?php echo esc_html( sprintf( _n( '%d product', '%d products', count( $section['items'] ), 'oli-catalog-generator' ), count( $section['items'] ) ) ); ?></div>
			</header>

			<div class="grid">
				<?php foreach ( $section['items'] as $item ) : ?>
					<article class="card" draggable="true" data-id="<?php echo esc_attr( $item['id'] ); ?>">
						<button type="button" class="card-remove" title="<?php esc_attr_e( 'Remove from catalogue', 'oli-catalog-generator' ); ?>" aria-label="<?php esc_attr_e( 'Remove from catalogue', 'oli-catalog-generator' ); ?>">×</button>
						<div class="card-img"><img src="<?php echo esc_url( $item['image'] ); ?>" alt="<?php echo esc_attr( $item['name'] ); ?>" draggable="false"></div>
						<div class="card-body">
							<?php if ( ! empty( $settings['show_brand'] ) && '' !== $item['brand'] ) : ?>
								<div class="card-brand"><?php echo esc_html( $item['brand'] ); ?></div>
							<?php endif; ?>
							<h3 class="card-title"><?php echo esc_html( $item['name'] ); ?></h3>
							<?php if ( ! empty( $settings['show_sku'] ) && '' !== $item['sku'] ) : ?>
								<div class="card-sku"><?php echo esc_html( 'SKU ' . $item['sku'] ); ?></div>
							<?php endif; ?>
							<?php if ( $olicg_multi ) : ?>
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
		</section>
	<?php endforeach; ?>

	<?php if ( $sections ) : ?>
		<div class="closing">
			<?php
			echo esc_html( sprintf(
				/* translators: 1: site name, 2: currency, 3: date, 4: site URL */
				__( '© %1$s. All prices in %2$s, current as of %3$s. Specifications, prices and availability subject to change without notice. %4$s', 'oli-catalog-generator' ),
				$olicg_year . ' ' . get_bloginfo( 'name' ),
				$currency,
				$olicg_date,
				wp_parse_url( home_url(), PHP_URL_HOST )
			) );
			?>
		</div>
	<?php endif; ?>
</main>

<script>
( function () {
	var cfg = <?php echo wp_json_encode( array(
		'url'     => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'olicg_arrange' ),
		'one'     => __( '%d product', 'oli-catalog-generator' ),
		'many'    => __( '%d products', 'oli-catalog-generator' ),
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
		return Array.prototype.filter.call( ( root || document ).querySelectorAll( '.card' ), function ( card ) { return ! card.hidden; } );
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

	document.querySelectorAll( '.grid' ).forEach( function ( grid ) {
		grid.addEventListener( 'dragstart', function ( e ) {
			var card = e.target.closest && e.target.closest( '.card' );
			if ( ! card ) { return; }
			dragging = card;
			before = orderKey();
			card.classList.add( 'is-dragging' );
			e.dataTransfer.effectAllowed = 'move';
			e.dataTransfer.setData( 'text/plain', card.dataset.id );
		} );
		grid.addEventListener( 'dragover', function ( e ) {
			if ( ! dragging || dragging.parentNode !== grid ) { return; }
			e.preventDefault();
			var over = e.target.closest && e.target.closest( '.card' );
			if ( ! over || over === dragging ) { return; }
			var cards = Array.prototype.slice.call( grid.children );
			if ( cards.indexOf( dragging ) < cards.indexOf( over ) ) {
				over.after( dragging );
			} else {
				over.before( dragging );
			}
		} );
		grid.addEventListener( 'drop', function ( e ) {
			if ( dragging && dragging.parentNode === grid ) {
				e.preventDefault();
				finishDrag();
			}
		} );
		grid.addEventListener( 'dragend', finishDrag );
	} );

	function finishDrag() {
		if ( ! dragging ) { return; }
		dragging.classList.remove( 'is-dragging' );
		dragging = null;
		if ( orderKey() !== before ) {
			post( { op: 'order', ids: orderKey().split( ',' ) } );
		}
	}

	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest && e.target.closest( '.card-remove' );
		if ( ! btn ) { return; }
		var card = btn.closest( '.card' );
		card.classList.add( 'is-removing' );
		post( { op: 'remove', id: card.dataset.id } ).then( function ( data ) {
			card.hidden = true;
			card.classList.remove( 'is-removing' );
			removed.push( { card: card, wasAdded: data.was_added ? 1 : 0 } );
			updateCounts();
		}, function () {
			card.classList.remove( 'is-removing' );
		} );
	} );

	undoBtn.addEventListener( 'click', function () {
		var last = removed.pop();
		if ( ! last ) { return; }
		last.card.hidden = false;
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
