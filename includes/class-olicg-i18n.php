<?php
/**
 * Multilingual support: WPML, Polylang, TranslatePress and qTranslate-XT, plus
 * filters for any other translation plugin.
 *
 * Catalogue arrangements (order, removed products, image zoom) stay keyed by the
 * product IDs of the selected categories, so one arrangement serves every language.
 *
 * @package OliCatalogGenerator
 */

defined( 'ABSPATH' ) || exit;

class OLICG_I18n {

	const DOMAIN = 'oli-catalog-generator';

	private static $provider;
	private static $switched = array();
	private static $trp_cache = array();

	/**
	 * Active translation plugin: wpml, polylang, translatepress, qtranslate or ''.
	 */
	public static function provider() {
		if ( null === self::$provider ) {
			if ( defined( 'ICL_SITEPRESS_VERSION' ) && has_filter( 'wpml_active_languages' ) ) {
				self::$provider = 'wpml';
			} elseif ( function_exists( 'pll_languages_list' ) ) {
				self::$provider = 'polylang';
			} elseif ( function_exists( 'trp_get_languages' ) && class_exists( 'TRP_Translate_Press' ) ) {
				self::$provider = 'translatepress';
			} elseif ( function_exists( 'qtranxf_use' ) ) {
				self::$provider = 'qtranslate';
			} else {
				self::$provider = '';
			}
			self::$provider = (string) apply_filters( 'olicg_i18n_provider', self::$provider );
		}
		return self::$provider;
	}

	/**
	 * @return array[] code => { label, locale }
	 */
	public static function languages() {
		$languages = array();

		switch ( self::provider() ) {
			case 'wpml':
				foreach ( (array) apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 0 ) ) as $code => $language ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML API.
					$languages[ $code ] = array(
						'label'  => isset( $language['native_name'] ) ? $language['native_name'] : $code,
						'locale' => ! empty( $language['default_locale'] ) ? $language['default_locale'] : $code,
					);
				}
				break;
			case 'polylang':
				foreach ( (array) pll_languages_list( array( 'fields' => '' ) ) as $language ) {
					$languages[ $language->slug ] = array( 'label' => $language->name, 'locale' => $language->locale );
				}
				break;
			case 'translatepress':
				foreach ( (array) trp_get_languages() as $code => $label ) {
					$languages[ $code ] = array( 'label' => $label, 'locale' => $code );
				}
				break;
			case 'qtranslate':
				global $q_config;
				foreach ( isset( $q_config['enabled_languages'] ) ? (array) $q_config['enabled_languages'] : array() as $code ) {
					$languages[ $code ] = array(
						'label'  => isset( $q_config['language_name'][ $code ] ) ? $q_config['language_name'][ $code ] : $code,
						'locale' => isset( $q_config['locale'][ $code ] ) ? $q_config['locale'][ $code ] : $code,
					);
				}
				break;
		}

		return (array) apply_filters( 'olicg_languages', $languages, self::provider() );
	}

	public static function is_multilingual() {
		return count( self::languages() ) > 1;
	}

	public static function default_language() {
		switch ( self::provider() ) {
			case 'wpml':
				$code = apply_filters( 'wpml_default_language', null ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML API.
				break;
			case 'polylang':
				$code = pll_default_language();
				break;
			case 'translatepress':
				$settings = get_option( 'trp_settings' );
				$code     = isset( $settings['default-language'] ) ? $settings['default-language'] : '';
				break;
			case 'qtranslate':
				global $q_config;
				$code = isset( $q_config['default_language'] ) ? $q_config['default_language'] : '';
				break;
			default:
				$code = '';
		}
		return (string) apply_filters( 'olicg_default_language', (string) $code, self::provider() );
	}

	/**
	 * A language code from the request/settings, or '' when it isn't an active language.
	 */
	public static function sanitize_language( $code ) {
		$code = is_string( $code ) ? sanitize_text_field( $code ) : '';
		return '' !== $code && isset( self::languages()[ $code ] ) ? $code : '';
	}

	public static function locale( $lang ) {
		$languages = self::languages();
		return isset( $languages[ $lang ]['locale'] ) ? $languages[ $lang ]['locale'] : get_locale();
	}

	/**
	 * Post IDs from a WP_Query that isn't limited to the admin's current language.
	 *
	 * @return int[]|WP_Post[]
	 */
	public static function query_all_languages( array $args ) {
		$previous = null;
		if ( 'wpml' === self::provider() ) {
			$previous = apply_filters( 'wpml_current_language', null ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML API.
			do_action( 'wpml_switch_language', 'all' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML API.
		} elseif ( 'polylang' === self::provider() ) {
			$args['lang'] = '';
		}
		$query = new WP_Query( $args );
		if ( null !== $previous ) {
			do_action( 'wpml_switch_language', $previous ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML API.
		}
		return $query->posts;
	}

	/**
	 * The translation of a post in $lang, or the post itself when it has none.
	 */
	public static function post_id( $post_id, $lang, $post_type = 'product' ) {
		$post_id = (int) $post_id;
		if ( '' === $lang ) {
			return $post_id;
		}
		switch ( self::provider() ) {
			case 'wpml':
				$translated = (int) apply_filters( 'wpml_object_id', $post_id, $post_type, true, $lang ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML API.
				break;
			case 'polylang':
				$translated = function_exists( 'pll_get_post' ) ? (int) pll_get_post( $post_id, $lang ) : 0;
				break;
			default:
				$translated = 0;
		}
		return (int) apply_filters( 'olicg_translate_post_id', $translated ? $translated : $post_id, $post_id, $lang, $post_type );
	}

	/**
	 * A page URL in $lang. WPML and Polylang already give translated posts their own permalink.
	 */
	public static function url( $url, $lang ) {
		$translated = $url;
		if ( '' !== $lang && $lang !== self::default_language() ) {
			if ( 'translatepress' === self::provider() && class_exists( 'TRP_Translate_Press' ) ) {
				$converter = TRP_Translate_Press::get_trp_instance()->get_component( 'url_converter' );
				if ( $converter && method_exists( $converter, 'get_url_for_language' ) ) {
					$translated = (string) $converter->get_url_for_language( $lang, $url, '' );
				}
			} elseif ( 'qtranslate' === self::provider() && function_exists( 'qtranxf_convertURL' ) ) {
				$translated = (string) qtranxf_convertURL( $url, $lang, false, true );
			}
		}
		return (string) apply_filters( 'olicg_translate_url', '' !== $translated ? $translated : $url, $url, $lang, self::provider() );
	}

	public static function term_id( $term_id, $lang, $taxonomy = 'product_cat' ) {
		$term_id = (int) $term_id;
		if ( '' === $lang ) {
			return $term_id;
		}
		switch ( self::provider() ) {
			case 'wpml':
				$translated = (int) apply_filters( 'wpml_object_id', $term_id, $taxonomy, true, $lang ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML API.
				break;
			case 'polylang':
				$translated = function_exists( 'pll_get_term' ) ? (int) pll_get_term( $term_id, $lang ) : 0;
				break;
			default:
				$translated = 0;
		}
		return (int) apply_filters( 'olicg_translate_term_id', $translated ? $translated : $term_id, $term_id, $lang, $taxonomy );
	}

	/**
	 * Render in $lang: switches the translation plugin's current language and the
	 * WordPress locale (plugin labels, dates). Undo with restore().
	 */
	public static function switch_to( $lang ) {
		$state = array( 'provider' => self::provider(), 'locale' => false, 'previous' => null, 'gettext' => false );
		if ( '' === $lang ) {
			self::$switched[] = $state;
			return;
		}

		switch ( $state['provider'] ) {
			case 'wpml':
				$state['previous'] = apply_filters( 'wpml_current_language', null ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML API.
				do_action( 'wpml_switch_language', $lang ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML API.
				break;
			case 'polylang':
				if ( function_exists( 'PLL' ) && isset( PLL()->model ) ) {
					$state['previous'] = isset( PLL()->curlang ) ? PLL()->curlang : null;
					PLL()->curlang     = PLL()->model->get_language( $lang );
				}
				break;
		}

		$state['locale'] = switch_to_locale( self::locale( $lang ) );

		// No plugin translation file for this language: let TranslatePress translate the labels.
		if ( 'translatepress' === $state['provider'] && $lang !== self::default_language() && function_exists( 'trp_translate' )
			&& get_translations_for_domain( self::DOMAIN ) instanceof NOOP_Translations ) {
			self::$trp_cache[ $lang ] = isset( self::$trp_cache[ $lang ] ) ? self::$trp_cache[ $lang ] : array();
			$state['gettext']         = static function ( $translation ) use ( $lang ) {
				return self::trp_string( $translation, $lang );
			};
			add_filter( 'gettext_' . self::DOMAIN, $state['gettext'] );
			add_filter( 'ngettext_' . self::DOMAIN, $state['gettext'] );
		}

		do_action( 'olicg_switch_language', $lang, $state['provider'] );
		self::$switched[] = $state;
	}

	public static function restore() {
		$state = array_pop( self::$switched );
		if ( ! $state ) {
			return;
		}
		if ( $state['gettext'] ) {
			remove_filter( 'gettext_' . self::DOMAIN, $state['gettext'] );
			remove_filter( 'ngettext_' . self::DOMAIN, $state['gettext'] );
		}
		if ( $state['locale'] ) {
			restore_previous_locale();
		}
		if ( 'wpml' === $state['provider'] && null !== $state['previous'] ) {
			do_action( 'wpml_switch_language', $state['previous'] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML API.
		} elseif ( 'polylang' === $state['provider'] && function_exists( 'PLL' ) ) {
			PLL()->curlang = $state['previous'];
		}
		do_action( 'olicg_restore_language', $state['provider'] );
	}

	/**
	 * Translate free text that has no translated post behind it (TranslatePress
	 * dictionary, qTranslate language tags, or the olicg_translate_strings filter).
	 *
	 * @param string[] $strings Keys are preserved.
	 */
	public static function translate_strings( array $strings, $lang ) {
		if ( ! $strings || '' === $lang ) {
			return $strings;
		}

		switch ( self::provider() ) {
			case 'translatepress':
				if ( $lang !== self::default_language() && function_exists( 'trp_translate' ) ) {
					$strings = self::trp_batch( $strings, $lang );
				}
				break;
			case 'qtranslate':
				foreach ( $strings as $key => $string ) {
					$strings[ $key ] = qtranxf_use( $lang, $string, false, true );
				}
				break;
		}

		return (array) apply_filters( 'olicg_translate_strings', $strings, $lang, self::provider() );
	}

	/**
	 * Admin-entered text (catalogue title, PDF button label…) through WPML String
	 * Translation or Polylang's Strings translations. Polylang implements this WPML
	 * hook and stores the string permanently (pll_register_string() only lasts one request).
	 */
	public static function register_string( $name, $value ) {
		if ( is_string( $value ) && '' !== $value ) {
			do_action( 'wpml_register_single_string', self::DOMAIN, $name, $value ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML API.
		}
	}

	/**
	 * @param string|null $lang Null = the visitor's current language.
	 */
	public static function translate_string( $name, $value, $lang = null ) {
		if ( ! is_string( $value ) || '' === $value ) {
			return $value;
		}
		switch ( self::provider() ) {
			case 'wpml':
				$value = apply_filters( 'wpml_translate_single_string', $value, self::DOMAIN, $name, $lang ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML API.
				break;
			case 'polylang':
				if ( null === $lang && function_exists( 'pll__' ) ) {
					$value = pll__( $value );
				} elseif ( function_exists( 'pll_translate_string' ) ) {
					$value = pll_translate_string( $value, $lang );
				}
				break;
			case 'qtranslate':
				if ( null === $lang && function_exists( 'qtranxf_getLanguage' ) ) {
					$lang = qtranxf_getLanguage();
				}
				$value = null === $lang ? $value : qtranxf_use( $lang, $value, false, true );
				break;
		}
		return (string) apply_filters( 'olicg_translate_string', $value, $name, $lang, self::provider() );
	}

	/**
	 * Admin text that may still be a built-in default: the default follows the
	 * reader's language (gettext), custom text goes through string translation.
	 */
	public static function setting_text( $name, $value, $source_default, $translated_default, $lang = null ) {
		if ( $value === $source_default || $value === $translated_default ) {
			return $translated_default;
		}
		return self::translate_string( $name, $value, $lang );
	}

	/**
	 * Saving the default shown in the admin's own language keeps it a default.
	 */
	public static function normalize_default( $value, $source_default, $translated_default ) {
		return $value === $translated_default ? $source_default : $value;
	}

	/**
	 * One TranslatePress pass for many strings (one dictionary lookup instead of one per string).
	 */
	private static function trp_batch( array $strings, $lang ) {
		$unique = array_values( array_unique( array_filter( array_map( 'strval', $strings ), 'strlen' ) ) );
		if ( ! $unique ) {
			return $strings;
		}

		$html = '';
		foreach ( $unique as $i => $string ) {
			$html .= '<p data-olicg-i="' . $i . '">' . esc_html( $string ) . '</p>';
		}
		$output = (string) trp_translate( '<div>' . $html . '</div>', $lang, false );

		$map = array();
		if ( preg_match_all( '#<p data-olicg-i="(\d+)"[^>]*>(.*?)</p>#s', $output, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				if ( isset( $unique[ (int) $match[1] ] ) ) {
					$translated = trim( html_entity_decode( wp_strip_all_tags( $match[2] ), ENT_QUOTES, 'UTF-8' ) );
					$map[ $unique[ (int) $match[1] ] ] = '' !== $translated ? $translated : $unique[ (int) $match[1] ];
				}
			}
		}

		foreach ( $strings as $key => $string ) {
			if ( isset( $map[ (string) $string ] ) ) {
				$strings[ $key ] = $map[ (string) $string ];
			}
		}
		return $strings;
	}

	private static function trp_string( $string, $lang ) {
		if ( ! is_string( $string ) || '' === trim( $string ) ) {
			return $string;
		}
		if ( ! isset( self::$trp_cache[ $lang ][ $string ] ) ) {
			$translated = self::trp_batch( array( $string ), $lang );
			// A machine translation that changes the sprintf placeholders would break the output.
			self::$trp_cache[ $lang ][ $string ] = self::placeholders( $translated[0] ) === self::placeholders( $string ) ? $translated[0] : $string;
		}
		return self::$trp_cache[ $lang ][ $string ];
	}

	private static function placeholders( $string ) {
		preg_match_all( '/%(?:\d+\$)?[sdfF]/', $string, $matches );
		sort( $matches[0] );
		return $matches[0];
	}
}
