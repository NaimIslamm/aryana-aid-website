<?php
/**
 * Macros manager class.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Engine_Listings_Macros' ) ) {

	/**
	 * Define Jet_Engine_Listings_Macros class
	 */
	class Jet_Engine_Listings_Macros {

		/**
		 * @var \Crocoblock\Macros_Handler
		 */
		public $handler = null;
		private $initialized = false;
		private $esc_output = false;

		public function __construct() {
			$this->handler = new \Crocoblock\Macros_Handler();
		}

		public function init() {

			if ( $this->initialized ) {
				return;
			}

			require_once jet_engine()->plugin_path( 'includes/base/base-macros.php' );
			require_once jet_engine()->plugin_path( 'includes/base/base-macros.php' );

			$this->register_core_macros();

			do_action( 'jet-engine/register-macros' );

			$this->handler->register_macros_list( apply_filters( 'jet-engine/listings/macros-list', array() ) );
			$this->initialized = true;

		}

		/**
		 * Return available macros list.
		 *
		 * @param bool $sorted
		 * @param bool $escape
		 *
		 * @return array
		 */
		public function get_all( $sorted = false, $escape = false ) {

			$this->init();

			if ( $escape ) {
				$macros_list = $this->handler->get_escaped_list();
			} else {
				$macros_list = $this->handler->get_raw_list();
			}

			return $macros_list;

		}

		public function get_macros_for_js() {
			$this->init();
			return $this->handler->get_macros_for_js();
		}

		public function register_core_macros() {

			foreach ( glob( jet_engine()->plugin_path( 'includes/components/listings/macros/' ) . '*.php' ) as $file ) {
				require_once $file;

				$file_name  = basename( $file, '.php' );
				$class_name = ucwords( str_replace( '-', ' ', $file_name ) );
				$class_name = str_replace( ' ', '_', $class_name );
				$class_name = sprintf( 'Jet_Engine\Macros\%s', $class_name );

				if ( class_exists( $class_name ) ) {
					new $class_name;
				}
			}
		}

		public function set_macros_context( $context = null ) {
			$this->handler->set_macros_context( $context );
		}

		public function get_macros_context( $context = null ) {
			return $this->handler->get_macros_context();
		}

		public function set_fallback( $fallback = null ) {
			$this->handler->set_fallback( $fallback );
		}

		public function get_fallback( $fallback = null ) {
			return $this->handler->get_fallback();
		}

		public function set_before( $before = null ) {
			$this->handler->set_before( $before );
		}

		public function get_before() {
			return $this->handler->get_before();
		}

		public function set_after( $after = null ) {
			$this->handler->set_after( $after );
		}

		public function get_after() {
			return $this->handler->get_after();
		}

		public function set_filter( $filter = null ) {
			$this->handler->set_filter( $filter );
		}

		public function get_filter() {
			return $this->handler->get_filter();
		}

		/**
		 * Set whether request-backed macros should escape their frontend output.
		 *
		 * This is transient render state. Callers must restore the previous value
		 * after resolving macros to avoid changing other macro consumers.
		 *
		 * @param bool $enabled Whether output escaping is enabled.
		 * @return bool Previous state.
		 */
		public function set_esc_output( $enabled = false ) {
			$previous         = $this->esc_output;
			$this->esc_output = (bool) $enabled;

			return $previous;
		}

		/**
		 * Check whether request-backed macros should escape their frontend output.
		 *
		 * @return bool
		 */
		public function is_esc_output() {
			return $this->esc_output;
		}

		/**
		 * Prepare user meta for a scoped HTML render without changing raw macro consumers.
		 *
		 * KSES policies are suitable only for HTML text positions. The raw policy
		 * is an explicit opt-out for trusted values.
		 *
		 * @param mixed  $value      User meta value.
		 * @param string $meta_key   Meta key.
		 * @param int    $user_id    User ID.
		 * @param string $macro_name Macro tag.
		 * @return mixed
		 */
		public function escape_user_meta_output( $value, $meta_key, $user_id, $macro_name ) {
			$policy = apply_filters(
				'jet-engine/listings/macros/user-meta-output-policy',
				'esc_html', $meta_key, $user_id, $macro_name
			);

			if ( 'raw' === $policy ) {
				return $value;
			}

			if ( ! in_array( $policy, array( 'esc_html', 'wp_kses_post' ), true ) && ! $this->is_valid_user_meta_kses_policy( $policy ) ) {
				$policy = 'esc_html';
			}

			if ( is_array( $value ) ) {
				foreach ( $value as $key => $item ) {
					$value[ $key ] = $this->escape_user_meta_item( $item, $policy );
				}

				return $value;
			}

			return $this->escape_user_meta_item( $value, $policy );
		}

		/**
		 * Accept only the allowed-tags shape expected by wp_kses().
		 */
		private function is_valid_user_meta_kses_policy( $policy ) {
			if ( ! is_array( $policy ) ) {
				return false;
			}

			foreach ( $policy as $tag => $attributes ) {
				if ( ! is_string( $tag ) || ! preg_match( '/^[a-z][a-z0-9-]*$/i', $tag ) || ! is_array( $attributes ) ) {
					return false;
				}

				foreach ( $attributes as $attribute => $allowed ) {
					if ( ! is_string( $attribute ) || ! preg_match( '/^[a-z][a-z0-9-]*$/i', $attribute ) || ! is_bool( $allowed ) ) {
						return false;
					}
				}
			}

			return true;
		}

		/**
		 * Escape one scalar and prevent later do_shortcode() from parsing it.
		 */
		private function escape_user_meta_item( $value, $policy ) {
			if ( ! is_scalar( $value ) && null !== $value ) {
				return '';
			}

			$value = (string) $value;

			if ( 'wp_kses_post' === $policy ) {
				$value = wp_kses_post( $value );
			} elseif ( is_array( $policy ) ) {
				$value = wp_kses( $value, $policy );
			} else {
				$value = esc_html( $value );
				// Character references stay inside an unquoted attribute value when parsed.
				// Escape its separators too, while preserving the displayed text.
				$value = str_replace(
					array( ' ', "\t", "\n", "\r", "\f", '=', '`' ),
					array( '&#32;', '&#9;', '&#10;', '&#13;', '&#12;', '&#61;', '&#96;' ),
					$value
				);
			}

			return str_replace( array( '[', ']' ), array( '&#91;', '&#93;' ), $value );
		}

		/**
		 * Is $str is array - returns 0, in other cases returns $str
		 *
		 * @param  mixed $str
		 * @return mixed
		 */
		public function to_string( $str = '' ) {
			return $this->handler->to_string( $str );
		}

		/**
		 * Get macros list for options.
		 *
		 * @return array
		 */
		public function get_macros_list_for_options() {
			$this->init();
			return $this->handler->get_macros_list_for_options();
		}

		/**
		 * Return verbosed macros list
		 *
		 * @return string
		 */
		public function verbose_macros_list() {
			$this->init();
			return $this->handler->verbose_macros_list();
		}

		/**
		 * Return current macros object
		 *
		 * @return object|null
		 */
		public function get_macros_object() {
			return $this->handler->get_macros_object();
		}

		/**
		 * Can be used for meta query. Returns values of passed mata key for current post/term.
		 *
		 * !!! Do not delete. Used in the macros classes.
		 *
		 * @param  mixed  $field_value Field value.
		 * @param  string $meta_key    Metafield to get value from.
		 * @return mixed
		 */
		public function get_current_meta( $field_value = null, $meta_key = null ) {

			if ( ! $meta_key && ! empty( $field_value ) ) {
				$meta_key = $field_value;
			}

			if ( ! $meta_key ) {
				return '';
			}

			$object = $this->get_macros_object();

			if ( ! $object ) {
				return '';
			}

			$class  = get_class( $object );
			$result = '';

			switch ( $class ) {

				case 'WP_Post':
					return jet_engine()->listings->data->get_meta( $meta_key, $object );

				case 'WP_Term':
					return get_term_meta( $object->term_id, $meta_key, true );

				case 'WP_User':
					return get_user_meta( $object->ID, $meta_key, true );

				default:
					return apply_filters( 'jet-engine/macros/current-meta', false, $object, $meta_key );

			}

		}

		/**
		 * Call macros callback by macros name and args array
		 *
		 * @param  [type] $macros [description]
		 * @param  array  $args   [description]
		 * @return [type]         [description]
		 */
		public function call_macros_func( $macros, $args = array() ) {
			$this->init();
			return $this->handler->call_macros_func( $macros, $args );

		}

		/**
		 * Do macros inside string
		 *
		 * @param  [type] $string      [description]
		 * @param  [type] $field_value [description]
		 * @return [type]              [description]
		 */
		public function do_macros( $string = '', $field_value = null ) {
			$this->init();
			return $this->handler->do_macros( $string, $field_value );
		}

	}

}
