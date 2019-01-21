<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

abstract class WDP_Condition_Cart_Items_Abstract extends WDP_Condition_Abstract {
	use WDP_Product_Filtering;

	protected $used_items;
	protected $has_product_dependency = true;
	protected $filter_type = '';

	public function check( $cart ) {
		$this->used_items = array();

		$options           = $this->data['options'];
		$comparison_qty    = (int) $options[0];
		$comparison_method = $options[1];
		$comparison_list   = (array) $options[2];

		if ( empty( $comparison_qty ) ) {
			return true;
		}

		$qty   = 0;
		$items = $cart->get_cart_items();
		foreach ( $items as $item_key => $item ) {
			$checked = $this->check_product_suitability(
				$item['product_id'],
				$this->filter_type,
				$comparison_list,
				$comparison_method
			);

			if ( $checked ) {
				$qty                += $item['quantity'];
				$this->used_items[] = $item_key;

				if ( $qty >= $comparison_qty ) {
					return true;
				}
			}
		}

		return false;
	}
	
	public function get_involved_cart_items() {
		return $this->used_items;
	}

	public function match( $cart ) {
		return true;
	}

	public function get_product_dependency() {
		return array(
			'qty'    => $this->data['options'][0],
			'type'   => $this->filter_type,
			'method' => $this->data['options'][1],
			'value'  => (array) $this->data['options'][2],
		);
	}
}