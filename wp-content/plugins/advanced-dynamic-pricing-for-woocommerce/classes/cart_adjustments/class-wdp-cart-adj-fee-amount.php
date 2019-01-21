<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WDP_Cart_Adjustment_Fee_Amount implements WDP_Cart_Adjustment {
	private $data;

	public function __construct( $data ) {
		$this->data = $data;
	}

	public function apply_to_cart( $cart, $rule_id ) {
		$options = $this->data['options'];

		$tax_class = ! empty( $options[2] ) ? $options[2] : "";

		$cart->add_fee_amount( $options[1], (float) $options[0], $rule_id, $tax_class );

		return true;
	}
}