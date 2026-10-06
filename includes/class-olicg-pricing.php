<?php
/**
 * Catalogue pricing rules.
 *
 * End-user price: lowest of regular / sale (regular = list, sale = MAP; when only
 * one exists, that one). Canada reads the base CAD prices, US reads the Price
 * Based on Countries USD zone. Dealer price: _dealer_cost_cad / _dealer_cost_usd
 * (imported by WP All Import).
 */

defined( 'ABSPATH' ) || exit;

class OLICG_Pricing {

	public static function regions() {
		return array(
			'ca' => array( 'label' => __( 'Canada', 'oli-catalog-generator' ), 'currency' => 'CAD' ),
			'us' => array( 'label' => __( 'United States', 'oli-catalog-generator' ), 'currency' => 'USD' ),
		);
	}

	public static function price_types() {
		return array(
			'retail' => __( 'End-user price', 'oli-catalog-generator' ),
			'dealer' => __( 'Dealer price', 'oli-catalog-generator' ),
		);
	}

	/**
	 * @return array|null { min: float, max: float } or null when the product has no usable price.
	 */
	public static function get_price( WC_Product $product, $region, $type ) {
		if ( ! $product->is_type( 'variable' ) ) {
			$amount = self::get_single_price( $product->get_id(), $region, $type );
			return null === $amount ? null : array( 'min' => $amount, 'max' => $amount );
		}

		$amounts = array();
		foreach ( $product->get_children() as $variation_id ) {
			if ( 'publish' !== get_post_status( $variation_id ) ) {
				continue;
			}
			$amount = self::get_single_price( $variation_id, $region, $type );
			if ( null !== $amount ) {
				$amounts[] = $amount;
			}
		}

		if ( ! $amounts ) {
			// Dealer cost may be set on the parent only.
			$amount = self::get_single_price( $product->get_id(), $region, $type );
			return null === $amount ? null : array( 'min' => $amount, 'max' => $amount );
		}

		return array( 'min' => min( $amounts ), 'max' => max( $amounts ) );
	}

	public static function get_single_price( $post_id, $region, $type ) {
		if ( 'dealer' === $type ) {
			$keys = apply_filters( 'olicg_dealer_meta_keys', array( 'ca' => '_dealer_cost_cad', 'us' => '_dealer_cost_usd' ) );
			return self::lowest( array( get_post_meta( $post_id, $keys[ $region ], true ) ) );
		}

		if ( 'us' === $region ) {
			return self::lowest( array(
				self::us_price( $post_id, '_regular_price' ),
				self::us_price( $post_id, '_sale_price' ),
			) );
		}

		return self::lowest( array(
			get_post_meta( $post_id, '_regular_price', true ),
			get_post_meta( $post_id, '_sale_price', true ),
		) );
	}

	public static function format( $price, $region ) {
		if ( ! $price ) {
			return '';
		}
		$amount = '$' . number_format( $price['min'], 2, '.', ',' );
		return $price['max'] > $price['min']
			/* translators: %s: lowest variation price */
			? sprintf( __( 'From %s', 'oli-catalog-generator' ), $amount )
			: $amount;
	}

	private static function lowest( array $values ) {
		$valid = array();
		foreach ( $values as $value ) {
			if ( is_numeric( $value ) && (float) $value > 0 ) {
				$valid[] = (float) $value;
			}
		}
		return $valid ? min( $valid ) : null;
	}

	private static function us_price( $post_id, $meta_key ) {
		$zone = self::us_zone();
		if ( $zone ) {
			return $zone->get_post_price( $post_id, $meta_key );
		}
		return get_post_meta( $post_id, '_usa' . $meta_key, true );
	}

	private static function us_zone() {
		static $zone = false;
		if ( false !== $zone ) {
			return $zone;
		}

		$zone = null;
		if ( class_exists( 'WCPBC_Pricing_Zones' ) ) {
			$found = WCPBC_Pricing_Zones::get_zone_by_id( apply_filters( 'olicg_us_zone_id', 'usa' ) );
			if ( ! $found ) {
				foreach ( WCPBC_Pricing_Zones::get_zones() as $candidate ) {
					if ( 'USD' === $candidate->get_currency() ) {
						$found = $candidate;
						break;
					}
				}
			}
			$zone = $found ? $found : null;
		}
		return $zone;
	}
}
