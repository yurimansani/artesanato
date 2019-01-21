<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WDP_Admin_Exclusive_Page extends WDP_Admin_Abstract_Page {
	public $priority = 120;
	protected $tab = 'exclusive';

	public function __construct() {
		$this->title = __( 'Exclusive Rules', 'advanced-dynamic-pricing-for-woocommerce' ) . "&#x1f512;";
	}

	public function action() {
	}

	public function render() {
		$this->render_template(
			WC_ADP_PLUGIN_PATH . 'views/tabs/exclusive.php'
		);
	}
}