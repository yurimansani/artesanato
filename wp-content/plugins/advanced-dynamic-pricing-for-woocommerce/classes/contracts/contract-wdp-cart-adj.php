<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

interface WDP_Cart_Adjustment {

	/**
	 * @param array $data
	 */
	public function __construct( $data );

	/**
	 * @param WDP_Cart $cart
	 * @param int      $rule_id
	 *
	 * @return bool
	 */
	public function apply_to_cart( $cart, $rule_id );
}