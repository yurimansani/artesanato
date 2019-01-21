<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WDP_Cart_Context {
	/**
	 * @var WDP_User
	 */
	private $customer;
	/**
	 * @var array
	 */
	private $environment;
	/**
	 * @var array
	 */
	private $settings;

	/**
	 * @param WDP_User $customer
	 * @param array    $environment
	 * @param array    $settings
	 */
	public function __construct( $customer, $environment, $settings ) {
		$this->customer    = $customer;
		$this->environment = $environment;
		$this->settings    = $settings;
	}

	/**
	 * @param string $format
	 *
	 * @return string
	 */
	public function datetime( $format ) {
		return date( $format, $this->environment['timestamp'] );
	}

	/**
	 * @return int
	 */
	public function time() {
		return $this->environment['timestamp'];
	}

	public function get_price_mode() {
		return $this->settings['discount_for_onsale'];
	}

	public function is_show_striked_prices() {
		return $this->settings['show_striked_prices'];
	}

	public function is_combine_multiple_discounts() {
		return $this->settings['combine_discounts'];
	}

	public function get_default_discount_name() {
		return $this->settings['default_discount_name'];
	}

	public function is_combine_multiple_fees() {
		return $this->settings['combine_fees'];
	}

	public function get_default_fee_name() {
		return $this->settings['default_fee_name'];
	}

	public function get_default_fee_tax_class() {
		return $this->settings['default_fee_tax_class'];
	}

	public function get_shipping_country() {
		return $this->customer->get_shipping_country();
	}

	public function get_shipping_state() {
		return $this->customer->get_shipping_state();
	}

	public function get_payment_method() {
		return $this->customer->get_payment_method();
	}

	public function get_shipping_methods() {
		return $this->customer->get_shipping_methods();
	}

	public function is_customer_logged_in() {
		return $this->customer->is_logged_in();
	}

	public function get_customer_id() {
		return $this->customer->get_id();
	}

	public function get_customer_roles() {
		return $this->customer->get_roles();
	}

	public function get_customer_order_count( $time_range ) {
		return $this->customer->get_order_count( $time_range );
	}

	public function convert_for_strtotime( $time ) {
		return $this->customer->convert_for_strtotime( $time );
	}

	public function get_count_of_rule_usages( $rule_id ) {
		return WDP_Database::get_count_of_rule_usages( $rule_id );
	}
}