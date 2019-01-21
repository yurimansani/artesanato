<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

abstract class WDP_Condition_Abstract {
	use WDP_Comparison;

	protected $data;
	protected $has_product_dependency = false;

	/**
	 * @param array $data
	 */
	public function __construct( $data ) {
		$this->data = $data;
	}

	/**
	 * @param WDP_Cart $cart
	 *
	 * @return bool
	 */
	public function check( $cart ) {
		return false;
	}

	/** @return array|null */
	public function get_involved_cart_items() {
		return null;
	}

	/**
	 * @param WDP_Cart $cart
	 *
	 * @return bool
	 */
	public function match( $cart ) {
		return $this->check( $cart );
	}

	/**
	 * @return bool
	 */
	public function has_product_dependency() {
		return $this->has_product_dependency;
	}

	/**
	 * @return array|false
	 */
	public function get_product_dependency() {
		return false;
	}
}