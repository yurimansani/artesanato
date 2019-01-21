<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

interface WDP_Rule {
	/**
	 * @param array $rule
	 */
	public function __construct( $rule );

	/**
	 * @return int
	 */
	public function get_id();

	/**
	 * @return bool
	 */
	public function is_exclusive();

	/**
	 * @return int
	 */
	public function get_priority();

	/**
	 * @param WDP_Cart $cart
	 *
	 * @return bool
	 */
	public function apply_to_cart( $cart );

	/**
	 * @param WDP_Cart $cart
	 * @param int      $product_id
	 *
	 * @return bool
	 */
	public function is_product_matched( $cart, $product_id );

	/**
	 * @return bool
	 */
	public function has_bulk();

	/**
	 * @return array
	 */
	public function get_bulk_details();

	/**
	 * @return int
	 */
	public function get_count_of_product_dependencies();

	/**
	 * @return array
	 */
	public function get_product_dependencies();
}