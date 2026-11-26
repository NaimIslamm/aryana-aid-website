<?php
namespace Jet_Engine\Macros;

/**
 * Return current user meta data.
 */
class Current_User_Meta extends \Jet_Engine_Base_Macros {

	/**
	 * @inheritDoc
	 */
	public function macros_tag() {
		return 'current_user_meta';
	}

	/**
	 * @inheritDoc
	 */
	public function macros_name() {
		return esc_html__( 'Current user meta', 'jet-engine' );
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
	public function macros_callback( $args = array() ) {

		$meta_key = ! empty( $args['meta_key'] ) ? $args['meta_key'] : null;
		$user_id  = get_current_user_id();

		$object = $this->get_macros_object();

		if ( $object && 'WP_User' === get_class( $object ) ) {
			$user_id = $object->ID;
		}

		if ( ! $user_id || ! $meta_key ) {
			return null;
		}

		$value  = get_user_meta( $user_id, $meta_key, true );
		$macros = jet_engine()->listings->macros;

		if ( $macros->is_esc_output() ) {
			return $macros->escape_user_meta_output( $value, $meta_key, $user_id, $this->macros_tag() );
		}

		return $value;
	}
}
