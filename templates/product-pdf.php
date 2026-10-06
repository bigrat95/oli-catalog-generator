<?php
/**
 * Hidden A4 product sheet, copied into a print window by assets/product-pdf.js.
 *
 * Available: $product (WC_Product), $settings (product PDF settings).
 */

defined( 'ABSPATH' ) || exit;

$olicg_pdf_image = $product->get_image_id() ? wp_get_attachment_image_url( $product->get_image_id(), 'large' ) : '';
$olicg_pdf_desc  = $product->get_short_description()
	? wp_strip_all_tags( $product->get_short_description() )
	: wp_trim_words( wp_strip_all_tags( $product->get_description() ), 90 );
$olicg_pdf_attrs = array_filter( $product->get_attributes(), static function ( $attribute ) {
	return $attribute->get_visible();
} );
$olicg_pdf_logo  = OLICG_Product_PDF::logo_for( $product, $settings );
$olicg_pdf_lines = array_filter( array( $settings['footer_line1'], $settings['footer_line2'] ) );
$olicg_pdf_d     = OLICG_Design::get_settings();
$olicg_pdf_font  = OLICG_Design::stacks( $olicg_pdf_d )['pdf'];
$olicg_pdf_ink   = $olicg_pdf_d['color_text'];
$olicg_pdf_muted = $olicg_pdf_d['color_muted'];
$olicg_pdf_band  = $olicg_pdf_d['color_band'];
$olicg_pdf_btext = $olicg_pdf_d['color_band_text'];
?>
<div id="olicg-pdf-content-<?php echo esc_attr( $product->get_id() ); ?>" class="olicg-pdf-content" aria-hidden="true" style="position: absolute; left: -9999px; top: 0; font-family: <?php echo esc_attr( $olicg_pdf_font ); ?>; color: <?php echo esc_attr( $olicg_pdf_ink ); ?>; background: #fff; width: 210mm; min-height: 297mm; box-sizing: border-box; display: flex; flex-direction: column;">

	<div style="flex: 1; padding: 40px 50px 30px 50px;">
		<table style="width: 100%; margin-bottom: 30px;">
			<tr>
				<td style="width: 42%; vertical-align: top; padding-right: 35px;">
					<div style="margin-bottom: 20px;">
						<?php if ( $olicg_pdf_logo ) : ?>
							<img src="<?php echo esc_url( $olicg_pdf_logo ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" style="height: 50px; width: auto;">
						<?php else : ?>
							<div style="font-size: 22px; font-weight: 700; letter-spacing: 3px;"><?php echo esc_html( strtoupper( get_bloginfo( 'name' ) ) ); ?></div>
						<?php endif; ?>
					</div>

					<div style="font-size: 24px; font-weight: 700; margin: 0 0 8px 0; color: <?php echo esc_attr( $olicg_pdf_ink ); ?>; line-height: 1.2;"><?php echo esc_html( $product->get_name() ); ?></div>

					<?php if ( $product->get_sku() ) : ?>
						<div style="font-size: 13px; color: <?php echo esc_attr( $olicg_pdf_muted ); ?>; margin-bottom: 20px;"><?php esc_html_e( 'sku:', 'oli-catalog-generator' ); ?> <span class="notranslate" translate="no" data-no-translation><?php echo esc_html( $product->get_sku() ); ?></span></div>
					<?php endif; ?>

					<?php if ( $olicg_pdf_desc ) : ?>
						<div style="font-size: 11px; line-height: 1.6; color: <?php echo esc_attr( $olicg_pdf_muted ); ?>; margin-bottom: 25px;"><?php echo esc_html( $olicg_pdf_desc ); ?></div>
					<?php endif; ?>

					<?php if ( $olicg_pdf_image ) : ?>
						<div style="text-align: center;">
							<img src="<?php echo esc_url( $olicg_pdf_image ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" style="max-width: 100%; max-height: 400px;">
						</div>
					<?php endif; ?>
				</td>

				<td style="width: 58%; vertical-align: top;">
					<?php if ( $olicg_pdf_attrs ) : ?>
						<div style="font-size: 13px; line-height: 2;">
							<?php foreach ( $olicg_pdf_attrs as $olicg_attr ) : ?>
								<?php
								$olicg_vals = $olicg_attr->is_taxonomy()
									? wc_get_product_terms( $product->get_id(), $olicg_attr->get_name(), array( 'fields' => 'names' ) )
									: $olicg_attr->get_options();
								if ( empty( $olicg_vals ) ) {
									continue;
								}
								?>
								<div style="margin-bottom: 4px;">
									<span style="font-weight: 600; color: <?php echo esc_attr( $olicg_pdf_ink ); ?>;"><?php echo esc_html( wc_attribute_label( $olicg_attr->get_name(), $product ) ); ?> :</span>
									<span style="color: <?php echo esc_attr( $olicg_pdf_ink ); ?>; font-weight: 400;">&nbsp;<?php echo esc_html( implode( ', ', $olicg_vals ) ); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</td>
			</tr>
		</table>
	</div>

	<div style="background-color: <?php echo esc_attr( $olicg_pdf_band ); ?>; color: <?php echo esc_attr( $olicg_pdf_btext ); ?>; padding: 25px 50px; margin-top: auto;">
		<table style="width: 100%;">
			<tr>
				<td style="vertical-align: middle; width: 50%;">
					<table>
						<tr>
							<?php if ( $settings['footer_icon'] ) : ?>
								<td style="vertical-align: middle; padding-right: 12px;">
									<img src="<?php echo esc_url( $settings['footer_icon'] ); ?>" alt="" style="width: 40px; height: 40px; <?php echo '#ffffff' === strtolower( $olicg_pdf_btext ) ? 'filter: brightness(0) invert(1);' : ''; ?>">
								</td>
							<?php endif; ?>
							<td style="vertical-align: middle;">
								<?php if ( $olicg_pdf_lines ) : ?>
									<?php foreach ( $olicg_pdf_lines as $olicg_line ) : ?>
										<div style="font-size: 14px; font-weight: 600; letter-spacing: 1px; line-height: 1.2; color: <?php echo esc_attr( $olicg_pdf_btext ); ?>;"><?php echo esc_html( $olicg_line ); ?></div>
									<?php endforeach; ?>
								<?php elseif ( ! $settings['footer_line3'] ) : ?>
									<div style="font-size: 14px; font-weight: 600; letter-spacing: 1px; line-height: 1.2; color: <?php echo esc_attr( $olicg_pdf_btext ); ?>;"><?php echo esc_html( strtoupper( get_bloginfo( 'name' ) ) ); ?></div>
								<?php endif; ?>
								<?php if ( $settings['footer_line3'] ) : ?>
									<div style="font-size: 10px; color: <?php echo esc_attr( $olicg_pdf_btext ); ?>; opacity: .65; margin-top: 3px;"><?php echo esc_html( $settings['footer_line3'] ); ?></div>
								<?php endif; ?>
							</td>
						</tr>
					</table>
				</td>
				<td style="text-align: right; vertical-align: middle; width: 50%;">
					<div style="font-size: 11px; color: <?php echo esc_attr( $olicg_pdf_btext ); ?>; font-weight: 500;" class="notranslate" translate="no" data-no-translation><?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></div>
					<div style="font-size: 9px; color: <?php echo esc_attr( $olicg_pdf_btext ); ?>; opacity: .65; margin-top: 3px;">©<?php echo esc_html( wp_date( 'Y' ) ); ?><?php echo $settings['disclaimer'] ? ' - ' . esc_html( $settings['disclaimer'] ) : ''; ?></div>
				</td>
			</tr>
		</table>
	</div>
</div>
