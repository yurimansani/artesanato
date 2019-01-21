<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WDP_Cart_Adjustment_Free_Shipping implements WDP_Cart_Adjustment {
	private $data;

	public function __construct( $data ) {
		$this->data = $data;
	}

	public function apply_to_cart( $cart, $rule_id ) {
		$cart->add_free_shipping( $rule_id );

		return true;
	}
}