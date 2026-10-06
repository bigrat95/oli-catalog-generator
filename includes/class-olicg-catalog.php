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
			'images'            => array(),
			'pictures_hidden'   => array(),
			'region'            => 'ca',
			'prices'            => array( 'retail' ),
			'price_labels'      => array(),
			'language'          => '',
			'layout'            => 'compact',
			'columns'           => 6,
			'paper'             => 'letter',
			'hide_no_price'     => 1,
			'hide_out_of_stock' => 0,
			'show_image'        => 1,
			'show_sku'          => 1,
			'show_upc'          => 0,
			'show_brand'        => 1,
			'section_new_page'  => 0,
			'logo_url'          => '',
		);
	}

	const DEFAULT_TITLE = 'Accessories Catalogue';

	/**
	 * Catalogue title in the language being rendered ('' = current language).
	 */
	public static function title( array $settings, $lang = '' ) {
		$default = __( 'Accessories Catalogue', 'oli-catalog-generator' );
		if ( in_array( $settings['title'], array( '', self::DEFAULT_TITLE, $default ), true ) ) {
			return $default;
		}
		$title = OLICG_I18n::translate_string( 'Catalogue title', (string) $settings['title'], '' !== $lang ? $lang : null );
		return '' !== $lang ? OLICG_I18n::translate_strings( array( $title ), $lang )[0] : $title;
	}

	public static function layouts() {
		return array(
			'compact' => __( 'Compact grid — small images, about 30–36 products per page', 'oli-catalog-generator' ),
			'list'    => __( 'List — thumbnails in two columns, about 34 products per page', 'oli-catalog-generator' ),
			'grid'    => __( 'Large cards — big images, 9 products per page', 'oli-catalog-generator' ),
			'table'   => __( 'Price list — a table per category with the pictures you choose below it', 'oli-catalog-generator' ),
		);
	}

	public static function get_settings() {
		$saved = get_option( self::OPTION, array() );
		$saved = is_array( $saved ) ? $saved : array();
		// Selections saved before layouts existed used large 3-column cards.
		if ( $saved && ! isset( $saved['layout'] ) ) {
			unset( $saved['columns'], $saved['section_new_page'] );
		}
		if ( ! isset( $saved['prices'] ) && isset( $saved['price_type'] ) ) {
			$saved['prices'] = OLICG_Pricing::legacy_components( $saved['price_type'] );
		}
		unset( $saved['price_type'] );
		$settings           = wp_parse_args( $saved, self::defaults() );
		$settings['prices'] = OLICG_Pricing::sanitize_components( $settings['prices'] );
		$settings['price_labels'] = self::sanitize_price_labels( $settings['price_labels'] );
		return $settings;
	}

	/**
	 * @return string[] component => custom label; components left empty use the default label.
	 */
	public static function sanitize_price_labels( $labels ) {
		$labels   = is_array( $labels ) ? $labels : array();
		$defaults = OLICG_Pricing::all_components();
		$clean    = array();
		foreach ( array_keys( $defaults ) as $key ) {
			$value = isset( $labels[ $key ] ) && is_scalar( $labels[ $key ] ) ? trim( sanitize_text_field( (string) $labels[ $key ] ) ) : '';
			if ( '' !== $value && $value !== $defaults[ $key ] ) {
				$clean[ $key ] = $value;
			}
		}
		return $clean;
	}

	/**
	 * Label printed for each price component, in the language being rendered ('' = current language).
	 *
	 * @return string[] component => label, in print order.
	 */
	public static function price_labels( array $settings, $lang = '' ) {
		$labels = OLICG_Pricing::all_components();
		$custom = self::sanitize_price_labels( $settings['price_labels'] );
		foreach ( $custom as $key => $value ) {
			$custom[ $key ] = OLICG_I18n::translate_string( 'Price label: ' . $key, $value, '' !== $lang ? $lang : null );
		}
		if ( $custom && '' !== $lang ) {
			$custom = array_combine( array_keys( $custom ), OLICG_I18n::translate_strings( array_values( $custom ), $lang ) );
		}
		return array_merge( $labels, $custom );
	}

	public static function image_size( array $settings ) {
		switch ( $settings['layout'] ) {
			case 'list':
				return 'thumbnail';
			case 'compact':
				return 'woocommerce_thumbnail';
			case 'table':
				return (int) $settings['columns'] >= 5 ? 'woocommerce_thumbnail' : 'woocommerce_single';
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

		$query = new WP_Query( OLICG_I18n::query_args() + array(
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
	 * In another language ($lang), names, categories and brands come from the
	 * product's translation; prices, stock and the item ID stay on the selected
	 * product so dealer costs and arrangements apply to every language.
	 *
	 * @return array[] Each: title, eyebrow, path[], items[] (id, product, name, sku, image, brand, prices).
	 */
	public static function get_sections( array $settings, $region, array $components, $lang = '' ) {
		$selected = array_map( 'intval', $settings['categories'] );
		if ( '' !== $lang ) {
			$selected = array_map( static function ( $term_id ) use ( $lang ) {
				return OLICG_I18n::term_id( $term_id, $lang );
			}, $selected );
		}
		$sections = array();

		foreach ( self::get_product_ids( $settings ) as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product || 'publish' !== $product->get_status() ) {
				continue;
			}
			if ( ! empty( $settings['hide_out_of_stock'] ) && ! $product->is_in_stock() ) {
				continue;
			}

			$prices = OLICG_Pricing::get_prices( $product, $region, $components );
			if ( $prices && ! array_filter( $prices ) && ! empty( $settings['hide_no_price'] ) ) {
				continue;
			}

			$shown = $product;
			if ( '' !== $lang ) {
				$translated_id = OLICG_I18n::post_id( $product_id, $lang );
				$translated    = $translated_id !== $product_id ? wc_get_product( $translated_id ) : null;
				if ( $translated && 'publish' === $translated->get_status() ) {
					$shown = $translated;
				}
			}

			$term = self::section_term( $shown->get_id(), $selected );
			$key  = $term ? self::term_path( $term ) : '~';

			if ( ! isset( $sections[ $key ] ) ) {
				$path             = $term ? self::term_ancestor_names( $term ) : array();
				$sections[ $key ] = array(
					'title'   => $term ? $term->name : __( 'Other products', 'oli-catalog-generator' ),
					'eyebrow' => implode( ' › ', $path ),
					'path'    => $path,
					'other'   => ! $term,
					'items'   => array(),
				);
			}

			$brand     = self::brand_name( $shown );
			$has_image = $shown->get_image_id() || $product->get_image_id();

			$sections[ $key ]['items'][] = array(
				'id'      => $product_id,
				'product' => $shown,
				'name'    => $shown->get_name(),
				'sku'     => $product->get_sku(),
				'upc'     => self::upc( $product ),
				'image'   => self::image_url( $shown->get_image_id() ? $shown : $product, self::image_size( $settings ) ),
				'picture' => $has_image && ! in_array( $product_id, array_map( 'intval', $settings['pictures_hidden'] ), true ),
				'has_img' => (bool) $has_image,
				'brand'   => '' !== $brand || $shown === $product ? $brand : self::brand_name( $product ),
				'prices'  => $prices,
			);
		}

		ksort( $sections, SORT_NATURAL | SORT_FLAG_CASE );
		$sections = array_values( $sections );

		if ( '' !== $lang ) {
			$sections = self::translate_section_strings( $sections, $lang );
		}

		foreach ( $sections as &$section ) {
			self::sort_items( $section['items'], $settings['order'] );
		}
		unset( $section );

		return $sections;
	}

	/**
	 * Text that isn't stored as a translated post (TranslatePress, qTranslate, filters).
	 */
	private static function translate_section_strings( array $sections, $lang ) {
		$strings = array();
		foreach ( $sections as $s => $section ) {
			$strings[ "s$s" ] = $section['title'];
			foreach ( $section['path'] as $p => $name ) {
				$strings[ "s$s-p$p" ] = $name;
			}
			foreach ( $section['items'] as $i => $item ) {
				$strings[ "s$s-i$i-n" ] = $item['name'];
				$strings[ "s$s-i$i-b" ] = $item['brand'];
			}
		}

		$strings = OLICG_I18n::translate_strings( $strings, $lang );

		foreach ( $sections as $s => &$section ) {
			$section['title'] = $strings[ "s$s" ];
			foreach ( $section['path'] as $p => &$name ) {
				$name = $strings[ "s$s-p$p" ];
			}
			unset( $name );
			$section['eyebrow'] = implode( ' › ', $section['path'] );
			foreach ( $section['items'] as $i => &$item ) {
				$item['name']  = $strings[ "s$s-i$i-n" ];
				$item['brand'] = $strings[ "s$s-i$i-b" ];
			}
			unset( $item );
		}
		unset( $section );

		usort( $sections, static function ( $a, $b ) {
			if ( $a['other'] !== $b['other'] ) {
				return $a['other'] ? 1 : -1;
			}
			return strnatcasecmp(
				remove_accents( implode( ' › ', array_merge( $a['path'], array( $a['title'] ) ) ) ),
				remove_accents( implode( ' › ', array_merge( $b['path'], array( $b['title'] ) ) ) )
			);
		} );

		return $sections;
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
			return strnatcasecmp( remove_accents( $a['name'] ), remove_accents( $b['name'] ) );
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

	/**
	 * Manual image zoom/position, per layout (boxes have different shapes).
	 *
	 * @return array{s: float, x: float, y: float}
	 */
	public static function image_fit( array $settings, $layout, $product_id ) {
		$fit = isset( $settings['images'][ $layout ][ $product_id ] ) ? (array) $settings['images'][ $layout ][ $product_id ] : array();
		return array(
			's' => isset( $fit['s'] ) ? (float) $fit['s'] : 1.0,
			'x' => isset( $fit['x'] ) ? (float) $fit['x'] : 0.0,
			'y' => isset( $fit['y'] ) ? (float) $fit['y'] : 0.0,
		);
	}

	public static function save_image_fit( $layout, $product_id, $scale, $x, $y ) {
		$settings = self::get_settings();
		$scale    = round( max( 0.3, min( 5, (float) $scale ) ), 3 );
		$x        = round( max( -150, min( 150, (float) $x ) ), 2 );
		$y        = round( max( -150, min( 150, (float) $y ) ), 2 );

		if ( 1.0 === $scale && 0.0 === $x && 0.0 === $y ) {
			unset( $settings['images'][ $layout ][ $product_id ] );
		} else {
			$settings['images'][ $layout ][ $product_id ] = array( 's' => $scale, 'x' => $x, 'y' => $y );
		}
		self::save_settings( $settings );
	}

	/**
	 * Price list layout: whether the products' pictures are shown below their table.
	 */
	public static function set_pictures( array $product_ids, $show ) {
		$product_ids = array_filter( array_map( 'absint', $product_ids ) );
		$settings    = self::get_settings();
		$hidden      = array_diff( array_map( 'intval', $settings['pictures_hidden'] ), $product_ids );
		if ( ! $show ) {
			$hidden = array_merge( $hidden, $product_ids );
		}
		$settings['pictures_hidden'] = array_values( array_unique( $hidden ) );
		self::save_settings( $settings );
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
		return implode( ' › ', self::term_ancestor_names( $term ) );
	}

	private static function term_ancestor_names( WP_Term $term ) {
		$names = array();
		foreach ( array_reverse( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) ) as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, 'product_cat' );
			if ( $ancestor && ! is_wp_error( $ancestor ) ) {
				$names[] = $ancestor->name;
			}
		}
		return $names;
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

	/**
	 * UPC / GTIN: WooCommerce's GTIN field, then common barcode meta keys.
	 */
	public static function upc( WC_Product $product ) {
		$upc = method_exists( $product, 'get_global_unique_id' ) ? (string) $product->get_global_unique_id() : '';
		if ( '' === $upc ) {
			foreach ( (array) apply_filters( 'olicg_upc_meta_keys', array( 'quivers_upc', '_quivers_upc', '_upc', 'upc', '_gtin', 'gtin', '_ean' ) ) as $key ) {
				$value = get_post_meta( $product->get_id(), $key, true );
				if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
					$upc = trim( (string) $value );
					break;
				}
			}
		}
		return (string) apply_filters( 'olicg_product_upc', $upc, $product );
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
