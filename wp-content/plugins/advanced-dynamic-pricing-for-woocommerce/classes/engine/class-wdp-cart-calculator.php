<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WDP_Cart_Calculator {
	/**
	 * @var WDP_Rules_Collection
	 */
	private $rule_collection;
	/**
	 * @var WDP_Cart
	 */
	private $cart;

	/**
	 * @param WDP_Rules_Collection $rule_collection
	 */
	public function __construct( $rule_collection ) {
		$this->rule_collection = $rule_collection;
	}

	/**
	 * @param WDP_Cart $cart
	 *
	 * @return WDP_Cart
	 */
	public function process_cart( $cart ) {
		$this->cart = $cart;
		$this->applied_rules = 0;

		$rule_array = $this->rule_collection->to_array();
		foreach ( $rule_array as $rule ) {
			if( $rule->apply_to_cart( $cart ) )
				$this->applied_rules++;
		}

		return $this->applied_rules ? $cart : false; // no new cart
	}

	/**
	 * @param WDP_Cart $cart
	 * @param int      $product_id
	 *
	 * @return WDP_Rules_Collection
	 */
	public function find_product_matches( $cart, $product_id ) {
		$matched = array();

		$rule_array = $this->rule_collection->to_array();
		foreach ( $rule_array as $rule ) {
			if ( $rule->is_product_matched( $cart, $product_id ) ) {
				$matched[] = $rule;
			}
		}

		return new WDP_Rules_Collection( $matched );
	}

	/**
	 * @param $cart WDP_Cart
	 * @param $product WC_Product|integer
	 * @param $qty
	 *
	 * @return boolean|array
	 *
	 */
	public function get_product_prices_to_display( $cart, $product, $qty ) {
		if ( ! is_a( $product, 'WC_Product' ) ) {
			if ( is_integer( $product ) ) {
				$product = wc_get_product( $product );
			}
		}

		if ( ! $product ) {
			return false;
		}

		$product_id = $product->get_id();
		$uid        = $product_id . current_time( 'timestamp' ) . random_int( 0, 100000 );
		if ( $qty > 1 ) {
			$cart->add_new_cart_item( $product, $qty - 1 );
		}
		
		$cart->add_new_cart_item( $product, 1, $uid );

		$new_cart = $this->process_cart( $cart );

		if ( ! $new_cart ) {
			return false;
		}

		return $cart->get_item_prices_to_display( $uid );
	}
}