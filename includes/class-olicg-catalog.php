<?php
/**
 * Catalogue selection (saved settings) and product collection.
 */

defined( 'ABSPATH' ) || exit;

class OLICG_Catalog {

	const OPTION = 'olicg_settings';

	public static function defaults() {
		return array(
			'title'             => __( 'Accessories Catalogue', 'oli-catalog-generator' ),
			'categories'        => array(),
			'excluded'          => array(),
			'added'             => array(),
			'order'             => array(),
			'region'            => 'ca',
			'price_type'        => 'retail',
			'layout'            => 'compact',
			'columns'           => 6,
			'paper'             => 'letter',
			'hide_no_price'     => 1,
			'hide_out_of_stock' => 0,
			'show_sku'          => 1,
			'show_brand'        => 1,
			'section_new_page'  => 0,
			'logo_url'          => '',
		);
	}

	public static function layouts() {
		return array(
			'compact' => __( 'Compact grid — small images, about 30–36 products per page', 'oli-catalog-generator' ),
			'list'    => __( 'List — thumbnails in two columns, about 34 products per page', 'oli-catalog-generator' ),
			'grid'    => __( 'Large cards — big images, 9 products per page', 'oli-catalog-generator' ),
		);
	}

	public static function get_settings() {
		$saved = get_option( self::OPTION, array() );
		$saved = is_array( $saved ) ? $saved : array();
		// Selections saved before layouts existed used large 3-column cards.
		if ( $saved && ! isset( $saved['layout'] ) ) {
			unset( $saved['columns'], $saved['section_new_page'] );
		}
		return wp_parse_args( $saved, self::defaults() );
	}

	public static function image_size( array $settings ) {
		switch ( $settings['layout'] ) {
			case 'list':
				return 'thumbnail';
			case 'compact':
				return 'woocommerce_thumbnail';
			default:
				return 'woocommerce_single';
		}
	}

	public static function save_settings( array $settings ) {
		update_option( self::OPTION, $settings, false );
	}

	/**
	 * Published products in the selected categories (children included).
	 */
	public static function get_category_product_ids( array $category_ids ) {
		$category_ids = array_filter( array_map( 'absint', $category_ids ) );
		if ( ! $category_ids ) {
			return array();
		}

		$query = new WP_Query( array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'tax_query'              => array(
				array(
					'taxonomy'         => 'product_cat',
					'field'            => 'term_id',
					'terms'            => $category_ids,
					'include_children' => true,
				),
			),
		) );

		return array_map( 'intval', $query->posts );
	}

	/**
	 * Final product IDs: category products minus removed ones, plus manual additions.
	 */
	public static function get_product_ids( array $settings ) {
		$ids = array_diff( self::get_category_product_ids( $settings['categories'] ), array_map( 'intval', $settings['excluded'] ) );
		$ids = array_merge( $ids, array_map( 'intval', $settings['added'] ) );
		return array_values( array_unique( array_filter( $ids ) ) );
	}

	/**
	 * Products grouped into sections (deepest category within the selection).
	 *
	 * @return array[] Each: title, eyebrow, items[] (product, name, sku, image, price).
	 */
	public static function get_sections( array $settings, $region, $price_type ) {
		$selected = array_map( 'intval', $settings['categories'] );
		$sections = array();

		foreach ( self::get_product_ids( $settings ) as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product || 'publish' !== $product->get_status() ) {
				continue;
			}
			if ( ! empty( $settings['hide_out_of_stock'] ) && ! $product->is_in_stock() ) {
				continue;
			}

			$prices = OLICG_Pricing::get_prices( $product, $region, $price_type );
			if ( ! array_filter( $prices ) && ! empty( $settings['hide_no_price'] ) ) {
				continue;
			}

			$term = self::section_term( $product_id, $selected );
			$key  = $term ? self::term_path( $term ) : '~';

			if ( ! isset( $sections[ $key ] ) ) {
				$sections[ $key ] = array(
					'title'   => $term ? $term->name : __( 'Other products', 'oli-catalog-generator' ),
					'eyebrow' => $term ? self::term_parent_path( $term ) : '',
					'items'   => array(),
				);
			}

			$sections[ $key ]['items'][] = array(
				'id'      => $product_id,
				'product' => $product,
				'name'    => $product->get_name(),
				'sku'     => $product->get_sku(),
				'image'   => self::image_url( $product, self::image_size( $settings ) ),
				'brand'   => self::brand_name( $product ),
				'prices'  => $prices,
			);
		}

		ksort( $sections, SORT_NATURAL | SORT_FLAG_CASE );
		foreach ( $sections as &$section ) {
			self::sort_items( $section['items'], $settings['order'] );
		}
		unset( $section );

		return array_values( $sections );
	}

	/**
	 * Manually arranged products first (in their saved order), then the rest by name.
	 *
	 * @param array[] $items Each with 'id' and 'name'.
	 */
	public static function sort_items( array &$items, array $order ) {
		$position = array_flip( array_map( 'intval', $order ) );
		usort( $items, static function ( $a, $b ) use ( $position ) {
			$pa = isset( $position[ $a['id'] ] ) ? $position[ $a['id'] ] : null;
			$pb = isset( $position[ $b['id'] ] ) ? $position[ $b['id'] ] : null;
			if ( null !== $pa && null !== $pb ) {
				return $pa - $pb;
			}
			if ( null !== $pa || null !== $pb ) {
				return null !== $pa ? -1 : 1;
			}
			return strnatcasecmp( $a['name'], $b['name'] );
		} );
	}

	/**
	 * New order: the given IDs first, then previously ordered IDs not in the list.
	 */
	public static function merge_order( array $ids, array $old ) {
		$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
		return array_values( array_merge( $ids, array_diff( array_map( 'intval', $old ), $ids ) ) );
	}

	/**
	 * @return bool Whether the product had been added manually.
	 */
	public static function remove_product( $product_id ) {
		$settings  = self::get_settings();
		$added     = array_map( 'intval', $settings['added'] );
		$was_added = in_array( $product_id, $added, true );

		$settings['added']    = array_values( array_diff( $added, array( $product_id ) ) );
		$settings['excluded'] = array_values( array_unique( array_merge( array_map( 'intval', $settings['excluded'] ), array( $product_id ) ) ) );
		self::save_settings( $settings );

		return $was_added;
	}

	public static function restore_product( $product_id, $was_added ) {
		$settings             = self::get_settings();
		$settings['excluded'] = array_values( array_diff( array_map( 'intval', $settings['excluded'] ), array( $product_id ) ) );
		if ( $was_added ) {
			$settings['added'] = array_values( array_unique( array_merge( array_map( 'intval', $settings['added'] ), array( $product_id ) ) ) );
		}
		self::save_settings( $settings );
	}

	/**
	 * Prefer a category inside the selection, then the deepest one.
	 */
	public static function section_term( $product_id, array $selected ) {
		$terms = get_the_terms( $product_id, 'product_cat' );
		if ( ! $terms || is_wp_error( $terms ) ) {
			return null;
		}

		$best       = null;
		$best_score = array( -1, -1 );
		foreach ( $terms as $term ) {
			$ancestors = get_ancestors( $term->term_id, 'product_cat', 'taxonomy' );
			$in        = in_array( $term->term_id, $selected, true ) || array_intersect( $ancestors, $selected );
			$score     = array( $in ? 1 : 0, count( $ancestors ) );
			if ( $score > $best_score ) {
				$best       = $term;
				$best_score = $score;
			}
		}
		return $best;
	}

	private static function term_path( WP_Term $term ) {
		$parent = self::term_parent_path( $term );
		return ( '' !== $parent ? $parent . ' › ' : '' ) . $term->name;
	}

	private static function term_parent_path( WP_Term $term ) {
		$names = array();
		foreach ( array_reverse( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) ) as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, 'product_cat' );
			if ( $ancestor && ! is_wp_error( $ancestor ) ) {
				$names[] = $ancestor->name;
			}
		}
		return implode( ' › ', $names );
	}

	/**
	 * Brand from the product_brand taxonomy, falling back to a "brand" attribute.
	 */
	public static function brand_name( WC_Product $product ) {
		$product_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
		foreach ( array( 'product_brand', 'pwb-brand', 'pa_brand' ) as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}
			$terms = get_the_terms( $product_id, $taxonomy );
			if ( $terms && ! is_wp_error( $terms ) ) {
				return html_entity_decode( implode( ', ', wp_list_pluck( $terms, 'name' ) ), ENT_QUOTES, 'UTF-8' );
			}
		}
		$attribute = $product->get_attribute( 'brand' );
		return is_string( $attribute ) ? $attribute : '';
	}

	public static function image_url( WC_Product $product, $size = 'woocommerce_single' ) {
		$url = $product->get_image_id() ? wp_get_attachment_image_url( $product->get_image_id(), $size ) : '';
		return $url ? $url : wc_placeholder_img_src( $size );
	}

	public static function logo_url( array $settings ) {
		if ( ! empty( $settings['logo_url'] ) ) {
			return $settings['logo_url'];
		}
		if ( function_exists( 'get_field' ) ) {
			$logo = get_field( 'site_logo', 'option' );
			if ( is_array( $logo ) && ! empty( $logo['url'] ) ) {
				return $logo['url'];
			}
			if ( is_string( $logo ) && '' !== $logo ) {
				return $logo;
			}
		}
		return '';
	}
}
