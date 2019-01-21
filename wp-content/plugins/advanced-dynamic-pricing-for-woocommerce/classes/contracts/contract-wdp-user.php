<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

interface WDP_User {
	/**
	 * @param WP_User|null $wp_user
	 */
	public function __construct( $wp_user = null );

	/**
	 * @return int|null
	 */
	public function get_id();

	/**
	 * @return bool
	 */
	public function is_logged_in();

	/**
	 * @return array
	 */
	public function get_roles();

	/**
	 * @return int
	 */
	public function get_order_count( $time_range);
	
	/**
	 * @return string|null
	 */
	public function get_shipping_country();

	/**
	 * @param string $country
	 */
	public function set_shipping_country( $country );

	/**
	 * @return string|null
	 */
	public function get_shipping_state();

	/**
	 * @param string $state
	 */
	public function set_shipping_state( $state );

	/**
	 * @return string|null
	 */
	public function get_payment_method();

	/**
	 * @param string $method
	 */
	public function set_payment_method( $method );

	/**
	 * @return string|null
	 */
	public function get_shipping_methods();

	/**
	 * @param string $method
	 */
	public function set_shipping_methods( $method );

	/**
	 * @param string $time
	 */
	public function convert_for_strtotime( $time );
}