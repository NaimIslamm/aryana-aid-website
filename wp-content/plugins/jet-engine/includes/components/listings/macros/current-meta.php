<?php
namespace Jet_Engine\Macros;

/**
 * Returns values of passed mata key for current post/term.
 */
class Current_Meta extends \Jet_Engine_Base_Macros {

	/**
	 * @inheritDoc
	 */
	public function macros_tag() {
		return 'current_meta';
	}

	/**
	 * @inheritDoc
	 */
	public function macros_name() {
		return esc_html__( 'Current meta value', 'jet-engine' );
	}

	/**
	 * @inheritDoc
	 */
	public function macros_args() {
		return array(
			'meta_key' => array(
				'label'   => __( 'Meta field', 'jet-engine' ),
				'type'    => 'text',
				'default' => '',
			),
		);
	}

	/**
	 * @inheritDoc
	 */
	public function macros_callback( $args = array(), $field_value = null ) {

		$meta_key = ! empty( $args['meta_key'] ) ? $args['meta_key'] : null;

		$macros = jet_engine()->listings->macros;
		$value  = $macros->get_current_meta( $field_value, $meta_key );

		if ( $macros->is_esc_output() ) {
			$object = $macros->get_macros_object();

			if ( $object && 'WP_User' === get_class( $object ) ) {
				$meta_key = $meta_key ? $meta_key : $field_value;
				return $macros->escape_user_meta_output( $value, $meta_key, $object->ID, $this->macros_tag() );
			}
		}

		return $value;
	}
}
