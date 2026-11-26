<?php

namespace Jet_Engine\Compatibility\Packages\Jet_Engine_Woo_Package\Relations;

use Jet_Engine\Compatibility\Packages\Jet_Engine_Woo_Package\Package;
use Jet_Engine\Relations\Types\Type_Query;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Manager {

	/**
	 * A reference to an instance of this class.
	 *
	 * @access private
	 * @var    object
	 */
	private static $instance = null;

	private $_products = [];

	private $mix_type = 'woocommerce_hpos_orders';

	/**
	 * Class constructor
	 */
	public function __construct() {
		$custom_orders_table_is_enabled = get_option( 'woocommerce_custom_orders_table_enabled' ) === 'yes';

		if ( ! $custom_orders_table_is_enabled ) {
			return false;
		}

		add_filter(
			'jet-engine/relations/types/mix/check-cap/' . $this->mix_type,
			array( $this, 'check_capability' ), 10, 3
		);

		add_filter(
			'jet-engine/relations/types/mix',
			array( $this, 'add_mix_type' )
		);

		add_action(
			'jet-engine/relation/include-controls-class',
			array( $this, 'register_controls_class' )
		);

		add_action(
			'jet-engine/relation/mix-object-controls/' . $this->mix_type,
			array( $this, 'setup_object_controls' ), 10, 2
		);

		add_filter(
			'jet-engine/relations/types/mix/items/' . $this->mix_type,
			array( $this, 'get_items' ), 10, 2
		);

		add_filter(
			'jet-engine/relations/types/mix/item-edit-url/' . $this->mix_type,
			array( $this, 'get_item_url' ), 10, 2
		);

		add_filter(
			'jet-engine/relations/types/mix/get-object-by-id/' . $this->mix_type,
			array( $this, 'get_object_by_id' ), 10, 2
		);

		add_filter(
			'jet-engine/relations/types/mix/query/' . $this->mix_type,
			array( $this, 'query' ), 10, 2
		);

		add_action(
			'jet-engine/relations/types/mix/cleanup-hook/' . $this->mix_type,
			array( $this, 'register_cleanup_hook' ), 10, 2
		);
	}

	/**
	 * Register cleanup hook so that when the order is deleted permanently - related rows are deleted from DB
	 * 
	 * @param callable $callback
	 * @param string   $type_name
	 */
	public function register_cleanup_hook( $callback, $type_name ) {
		add_action( 'woocommerce_before_delete_order', function( $order_id ) use ( $callback, $type_name ) {
			call_user_func( $callback, $type_name, $order_id );
		}, 10, 3 );
	}

	/**
	 * @param array|Type_Query $type_query
	 * @param array            $args
	 */
	public function query( $type_query, $args ) {
		$ids = $args['related_items_ids'] ?? array();

		if ( ! is_array( $ids ) || empty( $ids ) ) {
			return new Type_Query();
		}

		unset( $args['related_items_ids'] );

		$limit = $args['max_items'] ?? 10;

		if ( ! $limit || ! is_numeric( $limit ) ) {
			$limit = -1;
		}

		$wc_orders_query = new \WC_Order_Query(
			array(
				'limit'   => $limit ?? 10,
				'orderby' => 'date',
				'order'   => 'DESC',
			)
		);

		$orders = $wc_orders_query->get_orders();

		return new Type_Query(
			$orders,
			count( $orders ),
			$args
		);
	}

	public function get_object_by_id( $item, $item_id ) {
		return wc_get_order( $item_id );
	}

	public function get_item_url( $url, $item_id ) {
		$order = wc_get_order( $item_id );

		if ( ! $order ) {
			return $url;
		}

		return $order->get_edit_order_url();
	}

	/**
	 * @param array $items
	 * @param \Jet_Engine\Relations\Relation $relation
	 */
	public function get_items( $items, $relation ) {

		if ( ! function_exists( 'wc_get_orders' ) ) {
			return $items;
		}

		$order_ids = wc_get_orders(
			array(
				'limit'   => -1,
				'orderby' => 'ID',
				'order'   => 'DESC',
				'return'  => 'ids',
			)
		);

		if ( empty( $order_ids ) || ! is_array( $order_ids ) ) {
			return array();
		}

		return array_map(
			static function ( $order_id ) {
				$order_id = absint( $order_id );

				return array(
					'value' => strval( $order_id ),
					'label' => strval( $order_id ),
				);
			},
			$order_ids
		);
	}

	public function check_capability( $user_can, $capability, $item_id ) {
		switch ( $capability ) {
			case 'edit':
				return current_user_can( 'manage_woocommerce' );

			case 'delete':
				return false;

			default:
				return true;
		}
	}

	/**
	 * @param array $data
	 * @param \Jet_Engine\Relations\Relation $relation
	 */
	public function setup_object_controls( $data, $relation ) {
		$relation->init_controls_class( '\Jet_Engine\Relations\Controls\WooCommerce_HPOS_Orders', $data );
	}

	/**
	 * @param \Jet_Engine\Relations\Relation $relation
	 */
	public function register_controls_class( $relation ) {
		require_once Package::instance()->package_path( 'relations/controls.php' );
	}

	public function add_mix_type( $types ) {
		$types['woocommerce_hpos_orders'] = array(
			'value'        => $this->mix_type,
			'label'        => __( 'WooCommerce HPOS Orders', 'jet-engine' ),
			'label_single' => __( 'Order', 'jet-engine' ),
		);

		return $types;
	}

	/**
	 * Returns the instance.
	 *
	 * @access public
	 * @return object
	 */
	public static function instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;

	}

}
