<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WDP_Condition_Cart_Subtotal extends WDP_Condition_Abstract {

	public function check( $cart ) {
		$subtotal = $cart->get_cart_subtotal();

		$options           = $this->data['options'];
		$comparison_value  = (float) $options[1];
		$comparison_method = $options[0];

		return $this->compare_values( $subtotal, $comparison_value, $comparison_method );
	}

}