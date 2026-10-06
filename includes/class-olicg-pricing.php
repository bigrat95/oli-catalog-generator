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
			'ca' => array( 'label' => __( 'Canada', 'oli-catalog-generator' ), 'short' => __( 'CDN', 'oli-catalog-generator' ), 'currency' => 'CAD' ),
			'us' => array( 'label' => __( 'United States', 'oli-catalog-generator' ), 'short' => __( 'USA', 'oli-catalog-generator' ), 'currency' => 'USD' ),
		);
	}

	/**
	 * Price lines a catalogue can print, in print order.
	 *
	 * @return string[] component => short label (printed on each product)
	 */
	public static function all_components() {
		return array(
			'cost'   => __( 'Cost', 'oli-catalog-generator' ),
			'list'   => __( 'List', 'oli-catalog-generator' ),
			'map'    => __( 'MAP', 'oli-catalog-generator' ),
			'retail' => __( 'End-user', 'oli-catalog-generator' ),
		);
	}

	/**
	 * @return string[] component => admin description
	 */
	public static function component_descriptions() {
		return array(
			'cost'   => __( 'Cost — dealer cost', 'oli-catalog-generator' ),
			'list'   => __( 'List — regular price', 'oli-catalog-generator' ),
			'map'    => __( 'MAP — sale price', 'oli-catalog-generator' ),
			'retail' => __( 'End-user — lowest of list and MAP', 'oli-catalog-generator' ),
		);
	}

	/**
	 * Valid components in print order.
	 *
	 * @param string[]|string $selected Array or comma-separated list.
	 * @return string[]
	 */
	public static function sanitize_components( $selected ) {
		$selected = is_string( $selected ) ? explode( ',', $selected ) : (array) $selected;
		$selected = array_map( 'sanitize_key', array_filter( $selected, 'is_scalar' ) );
		return array_values( array_intersect( array_keys( self::all_components() ), $selected ) );
	}

	/**
	 * Price types used before price lines could be picked individually.
	 */
	public static function legacy_components( $type ) {
		switch ( $type ) {
			case 'dealer':
				return array( 'cost' );
			case 'all':
				return array( 'cost', 'list', 'map' );
		}
		return array( 'retail' );
	}

	/**
	 * @return string[] component => label, for the selected components.
	 */
	public static function components( array $selected ) {
		return array_intersect_key( self::all_components(), array_flip( self::sanitize_components( $selected ) ) );
	}

	/**
	 * @return array|null { min: float, max: float } or null when the product has no usable price.
	 */
	public static function get_price( WC_Product $product, $region, $type ) {
		return self::get_component( $product, $region, 'dealer' === $type ? 'cost' : 'retail' );
	}

	/**
	 * @return array component => { min, max } | null
	 */
	public static function get_prices( WC_Product $product, $region, array $components ) {
		$prices = array();
		foreach ( array_keys( self::components( $components ) ) as $component ) {
			$prices[ $component ] = self::get_component( $product, $region, $component );
		}
		return $prices;
	}

	/**
	 * @param string $component retail (lowest of list / MAP), cost, list or map.
	 */
	public static function get_component( WC_Product $product, $region, $component ) {
		if ( ! $product->is_type( 'variable' ) ) {
			$amount = self::get_single_component( $product->get_id(), $region, $component );
			return null === $amount ? null : array( 'min' => $amount, 'max' => $amount );
		}

		$amounts = array();
		foreach ( $product->get_children() as $variation_id ) {
			if ( 'publish' !== get_post_status( $variation_id ) ) {
				continue;
			}
			$amount = self::get_single_component( $variation_id, $region, $component );
			if ( null !== $amount ) {
				$amounts[] = $amount;
			}
		}

		if ( ! $amounts ) {
			// Dealer cost may be set on the parent only.
			$amount = self::get_single_component( $product->get_id(), $region, $component );
			return null === $amount ? null : array( 'min' => $amount, 'max' => $amount );
		}

		return array( 'min' => min( $amounts ), 'max' => max( $amounts ) );
	}

	public static function get_single_price( $post_id, $region, $type ) {
		return self::get_single_component( $post_id, $region, 'dealer' === $type ? 'cost' : 'retail' );
	}

	public static function get_single_component( $post_id, $region, $component ) {
		if ( 'cost' === $component ) {
			$keys = apply_filters( 'olicg_dealer_meta_keys', array( 'ca' => '_dealer_cost_cad', 'us' => '_dealer_cost_usd' ) );
			return self::lowest( array( get_post_meta( $post_id, $keys[ $region ], true ) ) );
		}

		$regular = 'us' === $region ? self::us_price( $post_id, '_regular_price' ) : get_post_meta( $post_id, '_regular_price', true );
		if ( 'list' === $component ) {
			return self::lowest( array( $regular ) );
		}

		$sale = 'us' === $region ? self::us_price( $post_id, '_sale_price' ) : get_post_meta( $post_id, '_sale_price', true );
		if ( 'map' === $component ) {
			return self::lowest( array( $sale ) );
		}

		return self::lowest( array( $regular, $sale ) );
	}

	public static function format( $price, $region, $symbol = true ) {
		if ( ! $price ) {
			return '';
		}
		$amount = ( $symbol ? '$' : '' ) . number_format( $price['min'], 2, '.', ',' );
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
