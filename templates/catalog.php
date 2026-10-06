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

$olicg_is_dealer = 'dealer' === $price_type;
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
<meta name="robots" content="noindex, nofollow">
<title><?php echo esc_html( $settings['title'] . ' ' . $olicg_year . ' — ' . $olicg_market . ' (' . $types[ $price_type ] . ')' ); ?></title>
<style>
<?php echo $olicg_fonts_css; // phpcs:ignore WordPress.Security.EscapeOutput -- theme CSS file. ?>

:root {
	--ink: #09090b;
	--muted: #71717a;
	--line: #e4e4e7;
	--paper: #fcfcfc;
	--tile: #f4f4f2;
	--serif: 'Playfair Display', Georgia, 'Times New Roman', serif;
	--sans: 'Poppins', 'Helvetica Neue', Arial, sans-serif;
	--mono: 'Space Mono', ui-monospace, Menlo, Consolas, monospace;
	--cols: <?php echo (int) $olicg_columns; ?>;
}

@page {
	size: <?php echo 'a4' === $olicg_paper ? 'A4' : 'letter'; ?> portrait;
	margin: 0.5in 0.45in 0.6in;
	@bottom-left { content: "<?php echo $olicg_footer_css; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped for a CSS string above. ?>"; font: 7pt 'Space Mono', monospace; color: #71717a; }
	@bottom-right { content: counter(page); font: 700 8pt 'Space Mono', monospace; color: #09090b; }
}
@page :first {
	@bottom-left { content: none; }
	@bottom-right { content: none; }
}

* { box-sizing: border-box; }
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
.toolbar strong { text-transform: uppercase; letter-spacing: .12em; }
.toolbar .hint { color: #a1a1aa; }
.toolbar button {
	flex-shrink: 0; white-space: nowrap;
	background: #fff; color: #000; border: 0; padding: 10px 18px; cursor: pointer;
	font: 700 12px var(--mono); text-transform: uppercase; letter-spacing: .12em;
}
.toolbar button:hover { background: #e4e4e7; }

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
.cover-meta-top { text-align: right; font: 9pt var(--mono); color: var(--muted); text-transform: uppercase; letter-spacing: .12em; line-height: 1.7; }
.cover-main { flex: 1; display: flex; flex-direction: column; justify-content: center; padding: 40px 0; }
.eyebrow { font: 700 9pt var(--mono); text-transform: uppercase; letter-spacing: .25em; color: var(--muted); }
.cover-title { font: italic 400 58pt/1.02 var(--serif); margin: 16px 0 0; letter-spacing: -.01em; }
.cover-year { font: italic 400 58pt/1.02 var(--serif); color: var(--muted); }
.cover-band {
	background: var(--ink); color: #fff; padding: 22px 26px;
	display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px;
}
.cover-band .label { font: 7.5pt var(--mono); text-transform: uppercase; letter-spacing: .2em; color: #a1a1aa; }
.cover-band .value { font: 700 12pt var(--mono); margin-top: 6px; text-transform: uppercase; letter-spacing: .06em; }
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
.section-title { font: italic 400 24pt/1.05 var(--serif); margin: 2px 0 0; }
.section-count { font: 8pt var(--mono); color: var(--muted); text-transform: uppercase; letter-spacing: .15em; white-space: nowrap; }

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
.card-sku { font: 6.5pt var(--mono); color: var(--muted); text-transform: uppercase; letter-spacing: .1em; margin-top: 4px; }
.card-price { margin-top: auto; padding-top: 8px; display: flex; align-items: baseline; justify-content: space-between; gap: 6px; }
.card-price .amount { font: 700 <?php echo 4 === $olicg_columns ? '10pt' : '11.5pt'; ?> var(--mono); }
.card-price .currency { font: 6.5pt var(--mono); color: var(--muted); letter-spacing: .12em; }
.card-price .na { font: 7pt var(--mono); color: var(--muted); text-transform: uppercase; letter-spacing: .1em; }

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

.closing { margin-top: 0.4in; padding-top: 12px; border-top: 1px solid var(--line); font: 7.5pt/1.6 var(--mono); color: var(--muted); break-inside: avoid; }
.empty { font: 11pt var(--mono); color: var(--muted); padding: 40px 0; }

@media print {
	body { background: #fff; }
	.toolbar { display: none; }
	.doc { width: auto; margin: 0; padding: 0; box-shadow: none; }
}
</style>
</head>
<body class="layout-<?php echo esc_attr( $olicg_layout ); ?>">

<div class="toolbar">
	<div>
		<strong><?php echo esc_html( $settings['title'] ); ?></strong>
		· <?php echo esc_html( $olicg_market . ' · ' . $types[ $price_type ] . ' · ' . sprintf( _n( '%d product', '%d products', $olicg_count, 'oli-catalog-generator' ), $olicg_count ) ); ?>
		<div class="hint"><?php esc_html_e( 'Chrome / Edge → Print → Save as PDF. Margins: Default · Headers and footers: off · Background graphics: on.', 'oli-catalog-generator' ); ?></div>
	</div>
	<button type="button" onclick="olicgPrint()"><?php esc_html_e( 'Print / Save as PDF', 'oli-catalog-generator' ); ?></button>
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
			<div><div class="label"><?php esc_html_e( 'Prices', 'oli-catalog-generator' ); ?></div><div class="value"><?php echo esc_html( ( $olicg_is_dealer ? __( 'Dealer', 'oli-catalog-generator' ) : __( 'End-user', 'oli-catalog-generator' ) ) . ' · ' . $currency ); ?></div></div>
			<div><div class="label"><?php esc_html_e( 'Products', 'oli-catalog-generator' ); ?></div><div class="value"><?php echo esc_html( $olicg_count ); ?></div></div>
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
					<article class="card">
						<div class="card-img"><img src="<?php echo esc_url( $item['image'] ); ?>" alt="<?php echo esc_attr( $item['name'] ); ?>"></div>
						<div class="card-body">
							<h3 class="card-title"><?php echo esc_html( $item['name'] ); ?></h3>
							<?php if ( ! empty( $settings['show_sku'] ) && '' !== $item['sku'] ) : ?>
								<div class="card-sku"><?php echo esc_html( 'SKU ' . $item['sku'] ); ?></div>
							<?php endif; ?>
							<div class="card-price">
								<?php if ( $item['price'] ) : ?>
									<span class="amount"><?php echo esc_html( OLICG_Pricing::format( $item['price'], $region ) ); ?></span>
									<span class="currency"><?php echo esc_html( $currency ); ?></span>
								<?php else : ?>
									<span class="na"><?php esc_html_e( 'Price on request', 'oli-catalog-generator' ); ?></span>
								<?php endif; ?>
							</div>
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
