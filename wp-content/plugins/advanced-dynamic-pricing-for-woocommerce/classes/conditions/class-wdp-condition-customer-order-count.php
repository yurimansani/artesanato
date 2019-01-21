<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WDP_Condition_Customer_Order_Count extends WDP_Condition_Abstract {

	public function check( $cart ) {
		$options = $this->data['options'];
		$time_range = $options[0];
		$comparison_method = $options[1];
		$comparison_value  = (int) $options[2];
		
		$order_count = $cart->get_context()->get_customer_order_count( $time_range );
		return $this->compare_values( $order_count, $comparison_value, $comparison_method );
	}
}