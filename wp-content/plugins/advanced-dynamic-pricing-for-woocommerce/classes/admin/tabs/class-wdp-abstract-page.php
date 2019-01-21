<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

abstract class WDP_Admin_Abstract_Page {
	public $title;
	public $priority;
	protected $tab;

	public function ajax() {
		$method = isset( $_REQUEST['method'] ) ? $_REQUEST['method'] : '';
		$method = "ajax_{$method}";

		if ( method_exists( $this, $method ) ) {
			$this->$method();
		}
	}

	public function action() {
	}

	public function render() {
	}

	protected function render_template( $template, $data = array() ) {
		$tab = $this->tab;
		extract( $data );

		include $template;
	}
}