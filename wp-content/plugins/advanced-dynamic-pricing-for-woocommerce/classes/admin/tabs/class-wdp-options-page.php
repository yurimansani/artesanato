<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WDP_Admin_Options_Page extends WDP_Admin_Abstract_Page {
	public $priority = 40;
	protected $tab = 'options';

	public function __construct() {
		$this->title = __( 'Settings', 'advanced-dynamic-pricing-for-woocommerce' );
	}

	public function action() {
		if ( isset( $_POST['save-options'] ) ) {
			$args = $this->get_validate_filters();
			WDP_Helpers::set_settings( filter_input_array( INPUT_POST, $args ) );

			wp_redirect( $_SERVER['HTTP_REFERER'] );
		}
	}

	protected function get_validate_filters() {
		$filters = array(
			'show_matched_bulk_table'          => FILTER_VALIDATE_BOOLEAN,
			'show_category_bulk_table'         => FILTER_VALIDATE_BOOLEAN,
			'show_striked_prices'              => FILTER_VALIDATE_BOOLEAN,
			'show_onsale_badge'                => FILTER_VALIDATE_BOOLEAN,
			'update_price_with_qty'            => FILTER_VALIDATE_BOOLEAN,
			'limit_results_in_autocomplete'    => FILTER_VALIDATE_INT,

			'combine_discounts'                     => FILTER_VALIDATE_BOOLEAN,
			'default_discount_name'                 => FILTER_SANITIZE_STRING,
			'combine_fees'                          => FILTER_VALIDATE_BOOLEAN,
			'default_fee_name'                      => FILTER_SANITIZE_STRING,
			'default_fee_tax_class'                 => FILTER_SANITIZE_STRING,
			'discount_for_onsale'                   => FILTER_SANITIZE_STRING,
			'is_override_cents'                     => FILTER_VALIDATE_BOOLEAN,
			'prices_ends_with'                      => array(
				'filter'  => FILTER_VALIDATE_REGEXP,
				'options' => array(
					'regexp'  => '/^[0-9]{2}$/',
					'default' => 99,
				),
			),
			'is_show_amount_saved_in_mini_cart'     => FILTER_VALIDATE_BOOLEAN,
			'is_show_amount_saved_in_cart'          => FILTER_VALIDATE_BOOLEAN,
			'is_show_amount_saved_in_checkout_cart' => FILTER_VALIDATE_BOOLEAN,

			'uninstall_remove_data'          => FILTER_VALIDATE_BOOLEAN,
			'load_in_backend'                => FILTER_VALIDATE_BOOLEAN,
			'suppress_other_pricing_plugins' => FILTER_VALIDATE_BOOLEAN,
		);

		return $filters;
	}

	protected function get_sections() {
		$sections = array(
			"interface"   => array(
				'title'     => __( "Interface", 'advanced-dynamic-pricing-for-woocommerce' ),
				'templates' => array(
					"show_category_bulk_table",
					"show_matched_bulk_table",
					"show_striked_prices",
					"show_onsale_badge",
					"show_amount_save",
					"limit_results_in_autocomplete",
				),
			),
			"calculation" => array(
				'title'     => __( "Calculation", 'advanced-dynamic-pricing-for-woocommerce' ),
				'templates' => array(
					"apply_discount_for_onsale_products",
					"combine_discounts",
					"default_discount_name",
					"combine_fees",
					"default_fee_name",
					"default_fee_tax_class",
					"override_cents",
				),
			),
			"system"      => array(
				'title'     => __( "System", 'advanced-dynamic-pricing-for-woocommerce' ),
				'templates' => array(
					"uninstall_remove_data",
					"load_in_backend",
					"suppress_other_pricing_plugins",
				),
			),
		);

		return $sections;
	}

	public function render() {
		$options = WDP_Helpers::get_settings();

		$data = compact( 'options' );

		list( $product, $category ) = $this->calculate_customizer_urls();
		$data['product_bulk_table_customizer_url']  = $product;
		$data['category_bulk_table_customizer_url'] = $category;

		$data['sections'] = $this->get_sections();

		$this->render_template( WC_ADP_PLUGIN_PATH . 'views/tabs/options.php', $data );
	}

	protected function render_options_template( $template, $data ) {
		$this->render_template( WC_ADP_PLUGIN_PATH . "views/tabs/options/{$template}.php", $data );
	}

	/**
	 * Making urls for simple redirect to customizer page with expanded panel and opened url with bulk table
	 *
	 */
	private function calculate_customizer_urls() {
		$active_rules = WDP_Rules_Registry::get_instance()->get_active_rules()->with_bulk()->to_array();
		$category_id  = 0;
		$product_id   = 0;

		foreach ( $active_rules as $index => $rule ) {
			$dependencies = $rule->get_product_dependencies();

			foreach ( $dependencies as $dependency ) {
				if ( 'product_categories' === $dependency['type'] && ! $category_id ) {
					$category_id = is_array( $dependency['values'] ) ? reset( $dependency['values'] ) : 0;
				}

				if ( 'products' === $dependency['type'] && ! $product_id ) {
					$product_id = is_array( $dependency['values'] ) ? reset( $dependency['values'] ) : 0;
				}

				if ( $category_id && $product_id ) {
					break;
				}
			}

			if ( $category_id && $product_id ) {
				break;
			}
		}

		return array( $this->make_url( $product_id, 'product' ), $this->make_url( $category_id, 'category' ) );
	}

	private function make_url( $id, $type ) {
		$customizer_url = add_query_arg(
			array(
				'return' => admin_url( 'themes.php' ),
			),
			admin_url( 'customize.php' )
		);


		if ( ! in_array( $type, array( 'product', 'category' ) ) ) {
			return $customizer_url;
		}

		$query_args = array(
			'autofocus[panel]' => "wdp_{$type}_bulk_table",
		);

		if ( $id ) {
			if ( 'product' == $type ) {
				$query_args['url'] = get_permalink( (int) $id );
			} elseif ( 'category' == $type ) {
				$query_args['url'] = get_term_link( (int) $id, 'product_cat' );
			}
		}

		return add_query_arg( $query_args, $customizer_url );
	}
}