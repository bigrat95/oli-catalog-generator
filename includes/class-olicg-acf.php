<?php
/**
 * Advanced Custom Fields integration: logo from an ACF options page, product
 * data (dealer costs, UPC, brand) from ACF fields. Everything degrades to plain
 * options / post meta when ACF is not active.
 *
 * @package OliCatalogGenerator
 */

defined( 'ABSPATH' ) || exit;

class OLICG_Acf {

	public static function is_active() {
		return function_exists( 'acf_get_field_groups' ) && function_exists( 'acf_get_fields' ) && function_exists( 'get_field' );
	}

	/**
	 * Field names: letters, digits, underscores and hyphens.
	 */
	public static function sanitize_name( $name ) {
		return is_scalar( $name ) ? substr( preg_replace( '/[^A-Za-z0-9_\-]/', '', (string) $name ), 0, 190 ) : '';
	}

	/**
	 * Image / URL fields of ACF options pages.
	 *
	 * @return string[] name => label
	 */
	public static function option_image_fields() {
		return self::fields(
			static function ( array $rule ) {
				return 'options_page' === $rule['param'];
			},
			array( 'image', 'url', 'file' )
		);
	}

	/**
	 * Scalar fields of field groups shown on products or variations.
	 *
	 * @return string[] name => label
	 */
	public static function product_fields() {
		return self::fields(
			static function ( array $rule ) {
				return 'post_type' === $rule['param'] && in_array( $rule['value'], array( 'product', 'product_variation' ), true );
			},
			array( 'text', 'number', 'range', 'select', 'checkbox', 'radio', 'button_group', 'taxonomy', 'post_object', 'relationship' )
		);
	}

	/**
	 * @param callable $matches Receives each location rule (param, operator, value).
	 * @param string[] $types   ACF field types to keep.
	 * @return string[] name => label
	 */
	private static function fields( callable $matches, array $types ) {
		if ( ! self::is_active() ) {
			return array();
		}
		$fields = array();
		foreach ( (array) acf_get_field_groups() as $group ) {
			if ( empty( $group['active'] ) || ! self::group_matches( $group, $matches ) ) {
				continue;
			}
			foreach ( (array) acf_get_fields( $group ) as $field ) {
				self::collect( $field, $types, $group['title'], '', $fields );
			}
		}
		ksort( $fields, SORT_NATURAL | SORT_FLAG_CASE );
		return $fields;
	}

	private static function group_matches( array $group, callable $matches ) {
		foreach ( isset( $group['location'] ) ? (array) $group['location'] : array() as $and ) {
			foreach ( (array) $and as $rule ) {
				if ( isset( $rule['param'], $rule['operator'], $rule['value'] ) && '==' === $rule['operator'] && $matches( $rule ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Fields inside an ACF "group" field are stored as {group}_{field}.
	 */
	private static function collect( array $field, array $types, $group_title, $prefix, array &$fields ) {
		if ( empty( $field['name'] ) ) {
			return;
		}
		$name = $prefix . $field['name'];
		if ( 'group' === $field['type'] && ! empty( $field['sub_fields'] ) ) {
			foreach ( $field['sub_fields'] as $sub_field ) {
				self::collect( $sub_field, $types, $group_title, $name . '_', $fields );
			}
			return;
		}
		if ( in_array( $field['type'], $types, true ) ) {
			$fields[ $name ] = $group_title . ' → ' . ( '' !== (string) $field['label'] ? $field['label'] : $name );
		}
	}

	/**
	 * Image URL stored in an ACF options field (image array, attachment ID or URL).
	 */
	public static function option_image_url( $name ) {
		$name = self::sanitize_name( $name );
		if ( '' === $name ) {
			return '';
		}
		// Without ACF, options page values are still stored as "options_{name}".
		$value = self::is_active() ? get_field( $name, 'option' ) : get_option( 'options_' . $name );
		return self::image_url( $value );
	}

	public static function image_url( $value ) {
		if ( is_array( $value ) ) {
			$value = isset( $value['url'] ) ? $value['url'] : ( isset( $value['ID'] ) ? $value['ID'] : '' );
		}
		if ( is_numeric( $value ) ) {
			$value = wp_get_attachment_image_url( (int) $value, 'full' );
		}
		return is_string( $value ) && '' !== $value ? esc_url_raw( $value ) : '';
	}

	/**
	 * A product field value as text: post meta first (ACF stores values under the
	 * field name), then ACF's formatted value for choice / relation fields.
	 */
	public static function product_text( $post_id, $name ) {
		$name = self::sanitize_name( $name );
		if ( '' === $name ) {
			return '';
		}
		$value = get_post_meta( $post_id, $name, true );
		if ( ( is_array( $value ) || is_numeric( $value ) ) && self::is_active() && function_exists( 'get_field_object' ) ) {
			// Only relation / choice fields: a numeric UPC must keep its leading zeros.
			$object = get_field_object( $name, $post_id, false, false );
			if ( is_array( $object ) && in_array( $object['type'], array( 'taxonomy', 'post_object', 'relationship', 'select', 'checkbox', 'radio', 'button_group' ), true ) ) {
				$value = get_field( $name, $post_id );
			}
		}
		if ( $value instanceof WP_Term || $value instanceof WP_Post ) {
			$value = $value instanceof WP_Term ? $value->name : get_the_title( $value );
		}
		if ( is_array( $value ) ) {
			$value = isset( $value['label'] ) ? $value['label'] : implode( ', ', array_filter( array_map( static function ( $item ) {
				if ( $item instanceof WP_Term ) {
					return $item->name;
				}
				if ( $item instanceof WP_Post ) {
					return get_the_title( $item );
				}
				return is_scalar( $item ) ? (string) $item : '';
			}, $value ), 'strlen' ) );
		}
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}
}
