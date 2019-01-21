<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WDP_Frontend {
	private $show_bulk_table;
	private $show_deals;
	private $show_adj;
	private $show_get_free;
	private $show_cart;
	private $show_bulk;
	/** @var WDP_Rules_Collection */
	private $matched_rules;

	private $last_product_id = 0;
	private $last_product_cached_result = null;

	private $options;

	public function __construct() {
		//TODO: check if need load our scripts
		WDP_Loader::load_core();

		$options = WDP_Helpers::get_settings();
		$this->options = $options;

		add_action( 'wp_print_styles', array( $this, 'load_frontend_assets' ) );

		// change price if front page or during ajax call in admin area
		if ( ! ( ( is_admin() AND ! wp_doing_ajax() ) OR $this->is_request_to_rest_api() ) ) {
			// for prices in catalog and single product mode
			add_filter( 'woocommerce_get_price_html', array( $this, 'woocommerce_get_price_html' ), 10, 2 );
			//add_filter( 'woocommerce_sale_flash', array($this, 'woocommerce_sale_flash'), 10, 3 );
			if ( $options['show_onsale_badge'] ) {
				add_filter( 'woocommerce_product_is_on_sale', array( $this, 'woocommerce_product_is_on_sale' ), 10, 2 );
			}
			add_filter( 'woocommerce_variable_price_html', array( $this, 'woocommerce_variable_price_html' ), 100, 2 );
		}
		
		if ( $options['show_matched_bulk_table'] ) {
			$product_bulk_table_action = isset( $options['product_bulk_table_action'] ) ? $options['product_bulk_table_action'] : "";
			add_action( 'wp_loaded', function () use ( $product_bulk_table_action ) {
				$product_bulk_table_actions = (array) apply_filters( 'wdp_product_bulk_table_action', $product_bulk_table_action );

				if ( is_array( $product_bulk_table_actions ) && $product_bulk_table_actions ) {
					foreach ( $product_bulk_table_actions as $action ) {
						add_action( $action, array( $this, 'print_table_with_product_bulk_rules' ), 50, 2 );
					}
				}
			} );
		}

		add_action( 'woocommerce_checkout_order_processed', array( $this, 'checkout_order_processed' ), 10, 3 );

		add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'woocommerce_cart_loaded_from_session' ), 100 );
		add_action( 'woocommerce_checkout_update_order_review',
			array( $this, 'woocommerce_checkout_update_order_review' ), 100 );

		// strike prices for items
		if ( $options['show_striked_prices'] ) {
			add_filter( 'woocommerce_cart_item_price', array( $this, 'woocommerce_cart_item_price_and_price_subtotal' ), 10, 3 );
			add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'woocommerce_cart_item_price_and_price_subtotal' ), 10, 3 );
		}

		if ( $options['show_category_bulk_table'] ) {
			$category_bulk_table_action = isset( $options['category_bulk_table_action'] ) ? $options['category_bulk_table_action'] : "";

			add_action( 'wp_loaded', function () use ( $category_bulk_table_action ) {
				$category_bulk_table_action = apply_filters( 'wdp_category_bulk_table_action',
					$category_bulk_table_action );
				if ( $category_bulk_table_action ) {
					add_action( $category_bulk_table_action, array( $this, 'print_table_with_category_bulk_rules' ), 50,
						2 );
				}
			} );
		}

		if ( $options['is_show_amount_saved_in_mini_cart'] ) add_action( 'woocommerce_mini_cart_contents', array( $this, 'output_amount_save' ) );
		if ( $options['is_show_amount_saved_in_cart'] ) add_action( 'woocommerce_cart_totals_before_order_total', array( $this, 'output_amount_save' ) );
		if ( $options['is_show_amount_saved_in_checkout_cart'] ) add_action( 'woocommerce_review_order_after_cart_contents', array( $this, 'output_amount_save' ) );
		
		//SHORTCODES
		add_shortcode( 'adp_product_bulk_rules_table', array( $this, 'print_table_with_product_bulk_rules' ) );
		add_shortcode( 'adp_category_bulk_rules_table', array( $this, 'print_table_with_category_bulk_rules' ) );

		// hooking nopriv ajax methods
		foreach ( self::get_nopriv_ajax_actions() as $ajax_action_name ) {
			add_action( "wp_ajax_nopriv_{$ajax_action_name}", array( $this, "ajax_{$ajax_action_name}" ) );
			add_action( "wp_ajax_{$ajax_action_name}", array( $this, "ajax_{$ajax_action_name}" ) );
		}

		if ( $options['suppress_other_pricing_plugins'] AND !is_admin() ) {
			add_action( "wp_loaded", array( $this, 'remove_hooks_set_by_other_plugins' ) );
		}

		add_filter( 'woocommerce_add_to_cart_sold_individually_found_in_cart', array($this, 'woocommerce_add_to_cart_sold_individually_found_in_cart'), 10 ,5 );

		/** PHONE ORDER HOOKS START */
		add_filter( 'wpo_set_original_price_after_calculation', function ( $price, $cart_item ) {
			return ! empty( $cart_item["wdp_original_price"] ) ? $cart_item["wdp_original_price"] : false;
		}, 10, 2 );

		add_filter( 'wpo_cart_item_is_price_readonly', function ( $is_read_only ) {
			return true;
		} );

		add_filter( 'wpo_must_switch_cart_user', '__return_true' );

		add_filter( 'wpo_skip_add_to_cart_item', function ( $skip, $item ) {
			return ! empty( $item['wdp_gifted'] ) ? (boolean) $item['wdp_gifted'] : $skip;
		}, 10, 2 );
		/** PHONE ORDER HOOKS FINISH */
	}

	public static function is_catalog_view() {
		return is_product_tag() || is_product_category() || is_shop();
	}

	public function is_request_to_rest_api() {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$rest_prefix = trailingslashit( rest_get_url_prefix() );

		// Check if our endpoint.
		$woocommerce = ( false !== strpos( $_SERVER['REQUEST_URI'], $rest_prefix . 'wc/' ) ); // @codingStandardsIgnoreLine

		// Allow third party plugins use our authentication methods.
		$third_party = ( false !== strpos( $_SERVER['REQUEST_URI'], $rest_prefix . 'wc-' ) ); // @codingStandardsIgnoreLine

		return apply_filters( 'woocommerce_rest_is_request_to_rest_api', $woocommerce || $third_party );
	}

	public function woocommerce_product_is_on_sale( $on_sale, $product ) {
		$this->last_product_id = 0;
		// pass empty string as first argument in 'woocommerce_get_price_html' method to check if rule has been applied
		// empty result means that price wasn`t change
		$this->last_product_cached_result = $this->woocommerce_get_price_html('', $product);
		$this->last_product_id = $product->get_id();
		return $on_sale OR $this->last_product_cached_result;
	}

	public function woocommerce_sale_flash($html_sale, $post, $product){
		return null;
	}

	public function woocommerce_variable_price_html( $price_html, $product, $qty = 1 ) {
		$modify = apply_filters('wdp_modify_price_html', true, $price_html, $product, $qty);
		if ( ! $modify ) {
			return $price_html;
		}

		$min_price     = -1;
		$max_price     = -1;
		foreach ( $product->get_children() as $child_id ) {
			$child = wc_get_product($child_id);
			$prices        = $this->get_initial_and_discounted_price( $child, $qty );
			if ( ! isset( $prices['initial_price'] ) || ! isset( $prices['initial_price'] ) ) {
				return $price_html;
			}
			$initial_price = $prices['initial_price'];
			$price         = $prices['price'];

			if ( ! $prices ) {
				$price = $child->get_price();
			}

			$min_price = $min_price > -1 ? min( $min_price, $price ) : $price;
			$max_price = $max_price > -1 ? max( $max_price, $price ) : $price;

		}


		if ( $min_price < $max_price AND ( $min_price OR $max_price ) AND ( $min_price !== floatval( - 1 ) AND $max_price !== floatval( - 1 ) ) ) {
			$price_html = wc_format_price_range( $min_price, $max_price ) . $product->get_price_suffix();
		} elseif ( ( $min_price == $max_price ) AND ( $min_price !== floatval( - 1 ) AND $max_price !== floatval( - 1 ) ) ) {
			if ( isset( $initial_price ) AND isset( $price ) AND ( $initial_price > $price ) ) {
				$price_html = apply_filters(
					'wdp_woocommerce_variable_discounted_price_html',
					wc_format_sale_price( wc_price( $initial_price ), wc_price( $price ) ) . $product->get_price_suffix(),
					$initial_price,
					$price,
					$product
				);
			}
		}
		return $price_html;
	}

	public function woocommerce_get_price_html( $price_html, $product, $qty = 1 ) {
		if ( $product->get_id() == $this->last_product_id AND $qty == 1 ) {
			return $this->last_product_cached_result ? $this->last_product_cached_result : $price_html;
		}

		$modify = apply_filters('wdp_modify_price_html', true, $price_html, $product, $qty);
		if ( ! $modify ) {
			return $price_html;
		}

		if ( is_a( $product, 'WC_Product' ) ) {
			/** @var WC_Product $product */
			if ( $product->is_type( 'variable' ) ) {
				return $price_html;
			}

			$prices        = $this->get_initial_and_discounted_price( $product, $qty );
			if ( ! $prices ) {
				return $price_html;
			}
			
			//var_dump($prices);

			if ( ! isset( $prices['initial_price'] ) || ! isset( $prices['initial_price'] ) ) {
				return $price_html;
			}
			$initial_price = $prices['initial_price'];
			$price         = $prices['price'];

			if ( false == $initial_price ) {
				if( !$price_html )// override price if not empty only! EMPTY means -- detect on sale 
					return $price_html;
				$price_html = wc_price( $price ) . $product->get_price_suffix();
			} elseif ( false !== $price && $initial_price != $price ) {
				$price_html = apply_filters(
					'wdp_woocommerce_discounted_price_html',
					wc_format_sale_price( wc_price( $initial_price ), wc_price( $price ) ) . $product->get_price_suffix(),
					$initial_price,
					$price,
					$product
				);
			}
		}

		return $price_html;
	}
	
	/* this function for future releases!
	public function woocommerce_before_single_product_summary() {
		global $product;

		$settings = WDP_Helpers::get_settings();

		$this->show_bulk_table = ! empty( $settings['show_matched_bulk_table'] );

		$this->show_adj      = ! empty( $settings['show_matched_adjustments'] );
		$this->show_get_free = ! empty( $settings['show_matched_get_products'] );
		$this->show_cart     = ! empty( $settings['show_matched_cart_adjustments'] );
		$this->show_bulk     = ! empty( $settings['show_matched_bulk'] );
		$this->show_deals    = ! empty( $settings['show_matched_deals'] ) &&
		                       ( $this->show_adj || $this->show_get_free || $this->show_cart || $this->show_bulk );
		if ( ! $this->show_bulk_table && ! $this->show_deals ) {
			return;
		}

		$this->matched_rules = $this->get_matched_offers( $product->get_id() );
		if ( $this->matched_rules->is_empty() ) {
			return;
		}

		if ( $this->show_bulk_table ) {
			add_action( 'woocommerce_after_single_product_summary',
				array( $this, 'print_table_with_product_bulk_rules' ), 9 );
		}
	}
	*/

	public function ajax_get_table_with_product_bulk_table() {
		$product_id = ! empty( $_REQUEST['product_id'] ) ? $_REQUEST['product_id'] : false;
		if ( ! $product_id ) {
			wp_send_json_error();
		}

		wp_send_json_success( $this->make_table_with_product_bulk_table( $product_id ) );
	}

	public function ajax_get_price_product_with_bulk_table() {
		$product_id = ! empty( $_REQUEST['product_id'] ) ? $_REQUEST['product_id'] : false;
		$qty        = ! empty( $_REQUEST['qty'] ) ? (int) $_REQUEST['qty'] : false;
		if ( ! $product_id || ! $qty ) {
			wp_send_json_error();
		}
		wp_send_json_success( array( 'price_html' => $this->get_price_html_product_with_bulk_table( $product_id, $qty ) ) );
	}

	private function get_price_html_product_with_bulk_table( $product_id, $qty ) {
		$product = wc_get_product( $product_id );

		remove_filter( 'woocommerce_get_price_html', array( $this, 'woocommerce_get_price_html' ), 10 );
		$price_html = $product->get_price_html();
		add_filter( 'woocommerce_get_price_html', array( $this, 'woocommerce_get_price_html' ), 10, 2 );

		return $product->is_type( 'variable' ) ? $this->woocommerce_variable_price_html( $price_html, $product, $qty ) : $this->woocommerce_get_price_html( $price_html, $product, $qty );
	}

	public function print_table_with_product_bulk_rules() {
		/**
		 * @var $product WC_Product
		 */
		global $product;
		echo '<span class="wdp_bulk_table_content">';
		echo $this->make_table_with_product_bulk_table( $product->get_id() );
		echo '</span>';
	}

	/**
	 * @param $product_id integer
	 *
	 * @return string
	 */
	private function make_table_with_product_bulk_table( $product_id ) {
		if( !isset($this->matched_rules) )//call from shortcode?
			$this->matched_rules = $this->get_matched_offers( $product_id );

		$bulk_rules = $this->matched_rules->with_bulk();
		if ( $bulk_rules->is_empty() ) {
			return "";
		}

		$rule = $bulk_rules->get_first();
		$data = $rule->get_bulk_details();

		$options    = WDP_Helpers::get_settings();
		$price_mode = $options['discount_for_onsale'];
		$product    = wc_get_product( $product_id );
		if ( ! $product OR $product->is_type( 'variable' ) ) {
			return "";
		}
		if ( $product->is_on_sale( 'edit' ) ) {
			if ( 'sale_price' === $price_mode ) {
				$price = $product->get_sale_price( '' );
			} elseif ( 'discount_sale' === $price_mode ) {
				$price = $product->get_sale_price( '' );
			} else {
				$price = $product->get_regular_price();
			}
		} else {
			$price = $product->get_price();
		}

		foreach ( $data['ranges'] as &$line ) {
			if ( 'price__fixed' === $data['discount'] ) {
				$line['discounted_price'] = (float) $price - (float) $line['value'];
				$line['value']            = wc_get_price_to_display( $product, array( 'price' => $line['value'] ) );
			} elseif ( 'discount__amount' === $data['discount'] ) {
				$line['discounted_price'] = (float) $price - (float) $line['value'];
			} elseif ( 'discount__percentage' === $data['discount'] ) {
				$line['discounted_price'] = (float) $price - (float) $price * (float) $line['value'] / 100;
			}
			$line['discounted_price'] = wc_price( wc_get_price_to_display( $product, array( 'price' => $line['discounted_price'] ) ) ) /*. $product->get_price_suffix()*/; 
		}

		if ( empty( $data['table_message'] ) ) {
			$dependencies = $rule->get_product_dependencies();
			foreach ( $dependencies as &$dependency ) {
				foreach ( $dependency['values'] as $id ) {
					$dependency['titles'][] = WDP_Helpers::get_title_by_type($id, $dependency['type']);
					$dependency['links'][]  = WDP_Helpers::get_permalink_by_type($id, $dependency['type']);
				}
			}
			unset( $dependency );
		}

		$content = $this->wdp_get_template(
			'bulk-table.php',
			array(
				'data' => $data,
				'dependencies' => isset($dependencies) ? $dependencies : null,
				'table_type'   => 'product',
			)
		);

		return $content;
	}

	public function print_table_with_product_bulk_rules_old() {
		/**
		 * @var $product WC_Product
		 */
		global $product;

		if( !isset($this->matched_rules) )//call from shortcode?
			$this->matched_rules = $this->get_matched_offers( $product->get_id() );
			
		$bulk_rules = $this->matched_rules->with_bulk();
		if ( $bulk_rules->is_empty() ) {
			return;
		}

		$rule = $bulk_rules->get_first();
		$data = $rule->get_bulk_details();
		if ( empty( $data['table_message'] ) ) {
			$dependencies = $rule->get_product_dependencies();
			foreach ( $dependencies as &$dependency ) {
				foreach ( $dependency['values'] as $id ) {
					$dependency['titles'][] = WDP_Helpers::get_title_by_type($id, $dependency['type']);
					$dependency['links'][]  = WDP_Helpers::get_permalink_by_type($id, $dependency['type']);
				}
			}
			unset( $dependency );
		}

		$content = $this->wdp_get_template(
			'bulk-table.php',
			array(
				'data' => $data,
				'dependencies' => isset($dependencies) ? $dependencies : null,
				)
		);

		echo $content;
	}

	public function print_table_with_category_bulk_rules() {
		if ( is_tax() ) {
			global $wp_query;


			if ( isset( $wp_query->queried_object->term_id ) ) {
				$term_id = $wp_query->queried_object->term_id;
			} else {
				return false;
			}

			$active_rules = WDP_Rules_Registry::get_instance()->get_active_rules()->with_bulk()->to_array();

			foreach ( $active_rules as $index => $rule ) {
				$data = $rule->get_bulk_details();

				$dependencies = $rule->get_product_dependencies();

				$delete = true;
				foreach ( $dependencies as &$dependency ) {
					foreach ( $dependency['values'] as $id ) {
						$dependency['titles'][] = WDP_Helpers::get_title_by_type($id, $dependency['type']);
						$dependency['links'][]  = WDP_Helpers::get_permalink_by_type($id, $dependency['type']);
						if ( 'product_categories' === $dependency['type'] AND $term_id == $id) {
								$delete = false;
						}
					}
				}

				if ( $delete ) {
					unset( $active_rules[ $index ] );
					continue;
				}


				$content = $this->wdp_get_template(
					'bulk-table.php',
					array(
						'data'         => $data,
						'dependencies' => isset( $dependencies ) ? $dependencies : null,
						'table_type'   => 'category',
					)
				);

				echo  '<span class="wdp_bulk_table_content">' . $content . '</span>';
				break;

			}
		}
	}

	/**
	 * @param int $product_id
	 *
	 * @return WDP_Rules_Collection
	 */
	public function get_matched_offers( $product_id ) {
		$calc    = $this->make_wdp_calc_from_wc();
// 		$context = $this->make_wdp_cart_context_from_wc();
// 		$coupons = $this->get_wc_cart_coupons();


// 		$cart = new WDP_Cart( array(), $coupons, $context );
		$cart = $this->make_wdp_cart_from_wc();

		$rules = $calc->find_product_matches( $cart, $product_id );

		return $rules;
	}

	/**
	 * @param WC_Product $product
	 * @param integer $qty
	 *
	 * @return array|boolean
	 */
	private function get_initial_and_discounted_price( $product, $qty = 1 ) {
		$calc = $this->make_wdp_calc_from_wc();
		$cart = $this->make_wdp_cart_from_wc();

		return $calc->get_product_prices_to_display( $cart, $product, $qty );
	}

	/**
	 * Change cart item display price
	 *
	 * @access public
	 *
	 * @param string $price_html
	 * @param array  $cart_item
	 * @param string $cart_item_key
	 *
	 * @return string
	 */
	public function cart_item_price( $price_html, $cart_item, $cart_item_key ) {

		if ( isset( $cart_item['wdp_data']['initial_price'] ) ) {

			/** @var WC_Product $product */
			$product = $cart_item['data'];

			$intial_price    = $cart_item['wdp_data']['initial_price'];
			$processed_price = $product->get_price();

			if ( $intial_price != $processed_price ) {
				$price_html = '<del>' . wc_price( $intial_price ) . '</del>';
				$price_html .= '<ins>' . wc_price( $processed_price ) . '</ins>';
			}
		}

		return $price_html;
	}

	public function woocommerce_cart_loaded_from_session() {
		if ( ! empty( $_GET['wc-ajax'] )  AND $_GET['wc-ajax'] === "update_order_review") 
			return;
		$this->process_cart();
	}	

	public function woocommerce_checkout_update_order_review() {
		add_action( 'woocommerce_before_data_object_save', array( $this, 'process_cart' ), 100 );
	}

	public function process_cart() {
		remove_action( 'woocommerce_before_data_object_save', array( $this, 'process_cart' ), 100 );

		$calc = $this->make_wdp_calc_from_wc();
		$cart = $this->make_wdp_cart_from_wc();

		$newcart = $calc->process_cart( $cart );
		if( $newcart ) {
			$newcart->apply_to_wc_cart( WC()->cart );
		} else {
			//try delete gifted products ?
			$wc_cart_items = WC()->cart->get_cart();
			foreach ( $wc_cart_items as $wc_cart_item_key => $wc_cart_item ) {
				$changed = false;

				if ( isset( $wc_cart_item['wdp_gifted'] ) ) {
					$wdp_gifted = $wc_cart_item['wdp_gifted'];
					unset( $wc_cart_item['wdp_gifted'] );
					$changed = true;
					if ( $wdp_gifted ) {
						WC()->cart->remove_cart_item( $wc_cart_item_key );
						continue;
					}
				}

				if ( isset( $wc_cart_item['wdp_original_price'] ) ) {
					unset( $wc_cart_item['wdp_original_price'] );
					$changed = true;
				}

				if ( isset( $wc_cart_item['wdp_rules'] ) ) {
					unset( $wc_cart_item['wdp_rules'] );
					$changed = true;
				}

				if ( isset( $wc_cart_item['rules'] ) ) {
					unset( $wc_cart_item['rules'] );
					$changed = true;
				}

				if ( $changed ) {
					WC()->cart->remove_cart_item( $wc_cart_item_key );
					WC()->cart->add_to_cart( $wc_cart_item['product_id'], $wc_cart_item['quantity'], $wc_cart_item['variation_id'], $wc_cart_item['variation'] );
				}
			}

			// clear shipping in session for triggering full calculate_shipping to replace 'wdp_free_shipping' when needed
			foreach ( WC()->session->get_session_data() as $key => $value ) {
				if ( preg_match( '/(shipping_for_package_).*/', $key, $matches ) === 1 ) {
					if ( ! isset( $matches[0] ) ) {
						continue;
					}
					$stored_rates = WC()->session->get( $matches[0] );

					if ( ! isset( $stored_rates['rates'] ) ) {
						continue;
					}
					if ( is_array( $stored_rates['rates'] ) ) {
						foreach ( $stored_rates['rates'] as $rate ) {
							if ( isset( $rate->get_meta_data()['wdp_free_shipping'] ) ) {
								unset( WC()->session->$key );
								break;
							}
						}
					}
				}
			}
		}// if no rules
	}

	/**
	 * @return WDP_Cart_Calculator
	 */
	private function make_wdp_calc_from_wc() {
		$rule_collection = WDP_Rules_Registry::get_instance()->get_active_rules();
		$calc            = new WDP_Cart_Calculator( $rule_collection );

		return $calc;
	}

	/**
	 * @return WDP_Cart
	 */
	private function make_wdp_cart_from_wc() {
		$context = $this->make_wdp_cart_context_from_wc();
		$cart    = WDP_Cart::make_from_wc_cart( WC()->cart, $context );

		return $cart;
	}

	/**
	 * @return array()
	 */
	private function get_wc_cart_coupons() {
		$external_coupons = array();
		foreach( WC()->cart->get_coupons() as $coupon ) {
			if( $coupon->get_id() )
				$external_coupons[] = $coupon->get_code();
		}
		return $external_coupons;
	}

	/**
	 * @return WDP_Cart_Context
	 */
	private function make_wdp_cart_context_from_wc() {
		//test code
		$environment = array(
			'timestamp' => current_time( 'timestamp' ),
		);

		$settings = WDP_Helpers::get_settings();

		$customer = new WDP_User_Impl( new WP_User( WC()->customer->get_id() ) );
		$customer->set_shipping_country( WC()->customer->get_shipping_country( '' ) );
		$customer->set_shipping_state( WC()->customer->get_shipping_state( '' ) );
		if ( is_checkout() ) $customer->set_payment_method( WC()->session->get('chosen_payment_method') );
		if ( is_checkout() OR !self::is_catalog_view() ) $customer->set_shipping_methods( WC()->session->get('chosen_shipping_methods') );;
		$context = new WDP_Cart_Context( $customer, $environment, $settings );

		return $context;
	}


	public function checkout_order_processed( $order_id, $posted_data, $order ) {
		list( $order_stats, $product_stats ) = $this->collect_wc_cart_stats( WC() );

		$order_date = current_time( 'mysql' );

		foreach ( $order_stats as $rule_id => $stats_item ) {
			$stats_item = array_merge(
				array(
					'order_id'         => $order_id,
					'rule_id'          => $rule_id,
					'amount'           => 0,
					'extra'            => 0,
					'shipping'         => 0,
					'is_free_shipping' => 0,
					'gifted_amount'    => 0,
					'gifted_qty'       => 0,
					'date'             => $order_date,
				),
				$stats_item
			);
			WDP_Database::add_order_stats( $stats_item );
		}

		foreach ( $product_stats as $product_id => $by_rule ) {
			foreach ( $by_rule as $rule_id => $stats_item ) {
				$stats_item = array_merge( array(
					'order_id'      => $order_id,
					'product_id'    => $product_id,
					'rule_id'       => $rule_id,
					'qty'           => 0,
					'amount'        => 0,
					'gifted_amount' => 0,
					'gifted_qty'    => 0,
					'date'          => $order_date,
				), $stats_item );

				WDP_Database::add_product_stats( $stats_item );
			}
		}
	}

	/**
	 * @param WooCommerce $wc
	 *
	 * @return array
	 */
	private function collect_wc_cart_stats( $wc ) {
		$order_stats   = array();
		$product_stats = array();

		$wc_cart = $wc->cart;

		$cart_items = $wc_cart->get_cart();
		foreach ( $cart_items as $cart_item ) {
			$rules = isset( $cart_item['wdp_rules'] ) ? $cart_item['wdp_rules'] : '';

			if ( empty( $rules ) ) {
				continue;
			}

			$product_id = $cart_item['product_id'];
			foreach ( $rules as $rule_id => $amount ) {
				//add stat rows 
				if( !isset( $order_stats[ $rule_id ] ) ) {
					$order_stats[ $rule_id ] = array( 'amount'=>0, 'qty'=>0, 'gifted_qty'=>0, 'gifted_amount'=>0, 'shipping'=>0, 'is_free_shipping'=>0, 'extra'=>0 );
				}
				if( !isset( $product_stats[ $product_id ][ $rule_id ] ) ) {
					$product_stats[ $product_id ][ $rule_id ] = array( 'amount'=>0, 'qty'=>0, 'gifted_qty'=>0, 'gifted_amount'=>0 );
				}

				$prefix =   !empty( $cart_item['wdp_gifted'] ) ? 'gifted_' : "";
				// order 
				$order_stats[ $rule_id ][$prefix . 'qty'] += $cart_item['quantity'];
				$order_stats[ $rule_id ][$prefix . 'amount'] += $amount;
				// product
				$product_stats[ $product_id ][ $rule_id ][$prefix . 'qty']    += $cart_item['quantity'];
				$product_stats[ $product_id ][ $rule_id ][$prefix . 'amount'] += $amount;
			}
		}

		$this->inject_wc_cart_coupon_stats( $wc_cart, $order_stats );
		$this->inject_wc_cart_fee_stats( $wc_cart, $order_stats );
		$this->inject_wc_cart_shipping_stats( $wc, $order_stats );

		return array( $order_stats, $product_stats );
	}

	/**
	 * @param WC_Cart $wc_cart
	 * @param array   $order_stats
	 */
	private function inject_wc_cart_coupon_stats( $wc_cart, &$order_stats ) {
		$totals      = $wc_cart->get_totals();
		$wdp_coupons = isset( $totals['wdp_coupons'] ) ? $totals['wdp_coupons'] : '';
		if ( empty( $wdp_coupons ) ) {
			return;
		}

		foreach ( $wc_cart->get_coupon_discount_totals() as $coupon_code => $amount ) {
			if ( isset( $wdp_coupons['grouped'][ $coupon_code ] ) ) {
				foreach ( $wdp_coupons['grouped'][ $coupon_code ] as $rule_id => $amount_per_rule ) {
					$order_stats[ $rule_id ]['extra'] += $amount_per_rule;
				}
			} elseif ( isset( $wdp_coupons['single'][ $coupon_code ] ) ) {
				$rule_id = $wdp_coupons['single'][ $coupon_code ];

				$order_stats[ $rule_id ]['extra'] += $amount;
			}
		}
	}

	/**
	 * @param WC_Cart $wc_cart
	 * @param array   $order_stats
	 */
	private function inject_wc_cart_fee_stats( $wc_cart, &$order_stats ) {
		$totals   = $wc_cart->get_totals();
		$wdp_fees = isset( $totals['wdp_fees'] ) ? $totals['wdp_fees'] : '';
		if ( empty( $wdp_fees ) ) {
			return;
		}

		foreach ( $wc_cart->get_fees() as $fee ) {
			$fee_name = $fee->name;
			if ( isset( $wdp_fees[ $fee_name ] ) ) {
				foreach ( $wdp_fees[ $fee_name ] as $rule_id => $fee_amount_per_rule ) {
					$order_stats[ $rule_id ]['extra'] -= $fee_amount_per_rule;
				}
			}
		}
	}

	/**
	 * @param WooCommerce $wc
	 * @param array       $order_stats
	 */
	private function inject_wc_cart_shipping_stats( $wc, &$order_stats ) {
		$shippings = $wc->session->get( 'chosen_shipping_methods' );
		if ( empty( $shippings ) ) {
			return;
		}

		foreach ( $shippings as $package_id => $shipping_rate_key ) {
			$packages = $wc->shipping()->get_packages();
			if ( isset( $packages[ $package_id ]['rates'][ $shipping_rate_key ] ) ) {
				/** @var WC_Shipping_Rate $sh_rate */
				$sh_rate      = $packages[ $package_id ]['rates'][ $shipping_rate_key ];
				$sh_rate_meta = $sh_rate->get_meta_data();
				if ( isset( $sh_rate_meta['wdp_rule'] ) ) {
					$rule_id          = $sh_rate_meta['wdp_rule'];
					$amount           = $sh_rate_meta['wdp_amount'];
					$is_free_shipping = $sh_rate_meta['wdp_free_shipping'];

					$order_stats[ $rule_id ]['shipping']         += $amount;
					$order_stats[ $rule_id ]['is_free_shipping'] = $is_free_shipping;
				}

			}
		}
	}

	private function wdp_get_template( $template_name , $args = array(), $template_path = '' ) {
		if ( ! empty( $args ) && is_array( $args ) ) {
			extract( $args );
		}

		if ( !$template_path ) {
			$template_path = trailingslashit( WC_ADP_PLUGIN_PATH . 'templates' );
		} else {
			$template_path = trailingslashit( WC_ADP_PLUGIN_PATH ) . trailingslashit( $template_path );
		}

		if ( ! ( $full_template_path = locate_template( array( 'advanced-dynamic-pricing-for-woocommerce/' . $template_name ) ) ) ) {
			$full_template_path = $template_path . $template_name;
		}

		ob_start();
		include $full_template_path;
		$template_content = ob_get_clean();

		return $template_content;
	}

	public function load_frontend_assets() {
		$options    = WDP_Helpers::get_settings();
		wp_enqueue_style( 'wdp_pricing-table', WC_ADP_PLUGIN_URL . '/assets/css/pricing-table.css', array(), WC_ADP_VERSION );
		wp_enqueue_style( 'wdp_deals-table', WC_ADP_PLUGIN_URL . '/assets/css/deals-table.css', array(), WC_ADP_VERSION );

		if ( is_product() || woocommerce_product_loop() ) {
			wp_enqueue_script( 'wdp_deals', WC_ADP_PLUGIN_URL . '/assets/js/frontend.js', array(), WC_ADP_VERSION );
		}

		if ( WDP_Database::is_condition_type_active( array( 'customer_shipping_method' ) ) ) {
			wp_enqueue_script( 'wdp_update_cart', WC_ADP_PLUGIN_URL . '/assets/js/update-cart.js' , array( 'wc-cart' ), WC_ADP_VERSION );
		}

		$script_data = array(
			'ajaxurl'               => admin_url( 'admin-ajax.php' ),
			'update_price_with_qty' => wc_string_to_bool( $options['update_price_with_qty'] ),
			'js_init_trigger'       => apply_filters( 'wdp_bulk_table_js_init_trigger', "" ),
		);

		wp_localize_script( 'wdp_deals', 'script_data', $script_data );
	}

	public function output_amount_save() {
		$amount_saved = self::get_amount_save_from_items();
		if ( 0 >= $amount_saved ) {
			return null;
		}

		$templates = array(
			'woocommerce_mini_cart_contents'               => 'mini-cart.php',
			'woocommerce_cart_totals_before_order_total'   => 'cart-totals.php',
			'woocommerce_review_order_after_cart_contents' => 'cart-totals-checkout.php',
		);

		$template_content = '';
		foreach ( $templates as $hook_name => $template_name ) {
			if ( current_action() == $hook_name ) {
				$template_content = $this->wdp_get_template(
					$template_name,
					array(
						'amount_saved' => $amount_saved,
					),
					'templates/amount-saved'
				);
			}
		}

		echo $template_content;
	}

	public static function get_amount_save_from_items() {
		$cart_items   = WC()->cart->cart_contents;
		$amount_saved = 0;

		foreach ( $cart_items as $cart_item_key => $cart_item ) {
			if ( ! isset( $cart_item['rules'] ) AND ! isset( $cart_item['wdp_rules'] ) ) {
				return 0;
			}
			$cart_item_rules = isset( $cart_item['rules'] ) ? $cart_item['rules'] : $cart_item['wdp_rules'];
			foreach ( $cart_item_rules as $id => $amount_saved_by_rule ) {
				$amount_saved += $amount_saved_by_rule;
			}
		}

		return $amount_saved;
	}

	public function woocommerce_cart_item_price_and_price_subtotal ( $price, $cart_item, $cart_item_key ) {
		$product = wc_get_product($cart_item['product_id']);

		$new_price =  (float)$cart_item['data']->get_price();
		$new_price = $this->get_price_cart_item_to_display($product , array('price'=>$new_price));

		$new_price_html = $price;
		if ( !isset($cart_item['wdp_original_price']) ){
			return $price;
		}
		$old_price = $cart_item['wdp_original_price'];
		$old_price = $this->get_price_cart_item_to_display($product , array('price'=>$old_price));

		if ( 'woocommerce_cart_item_subtotal' == current_filter() ) {
			$new_price = $new_price * $cart_item['quantity'];
			$old_price = $old_price * $cart_item['quantity'];
		}

		if ( $new_price !== false AND $old_price !== false ) {
			if ( $new_price !== $old_price ) {
				if ( $new_price < $old_price ) {
					$price_html = wc_format_sale_price(
						$old_price,
						$new_price_html
					);
				} else {
					$price_html = $new_price_html;
				}

			} else {
				$price_html = $new_price_html;
			}
		} else {
			$price_html = $new_price_html;
		}
		return $price_html;
	}

	private function get_price_cart_item_to_display( $product, $args = array() ) {
		$args = wp_parse_args( $args, array(
			'qty'   => 1,
			'price' => $product->get_price(),
		) );

		$price = $args['price'];
		$qty   = $args['qty'];

		return 'incl' === get_option( 'woocommerce_tax_display_cart' ) ? wc_get_price_including_tax( $product, array( 'qty' => $qty, 'price' => $price ) ) : wc_get_price_excluding_tax( $product, array( 'qty' => $qty, 'price' => $price ) );
	}

	function remove_hooks_set_by_other_plugins() {
		global $wp_filter;
		
		$allowed_hooks = array( 
			//Filters
 			"woocommerce_get_price_html"=>array( "WDP_Frontend|woocommerce_get_price_html"),
 			"woocommerce_product_is_on_sale"=>array( "WDP_Frontend|woocommerce_product_is_on_sale"),
 			"woocommerce_variable_price_html"=>array( "WDP_Frontend|woocommerce_variable_price_html"),
			"woocommerce_cart_item_price"=>array( "WDP_Frontend|woocommerce_cart_item_price_and_price_subtotal"),
			"woocommerce_cart_item_subtotal"=>array( "WDP_Frontend|woocommerce_cart_item_price_and_price_subtotal"),
			//Actions
 			"woocommerce_checkout_order_processed"=>array( "WDP_Frontend|checkout_order_processed"),
 			"woocommerce_before_calculate_totals"=>array( ), //nothing allowed!
		);
		
		foreach($wp_filter as $hook_name=>$hook_obj) {
			if( preg_match('#^woocommerce_#',$hook_name) ) {
				if( isset($allowed_hooks[$hook_name]) ) {
					$wp_filter[$hook_name] = $this->remove_wrong_callbacks($hook_obj, $allowed_hooks[$hook_name] );
					//var_dump($hook_name, $wp_filter[$hook_name] );die();
				} else {
					//var_dump($hook_name, $hook_obj );
				}	
			}
		}
	}
	
	function remove_wrong_callbacks($hook_obj, $allowed_hooks) {
		$new_callbacks = array();
		foreach($hook_obj->callbacks as $priority=>$callbacks) {
			$priority_callbacks = array();
			foreach($callbacks as $idx=>$callback_details) {
				if( $this->is_callback_allowed($callback_details,$allowed_hooks) ) 
					$priority_callbacks[$idx] = $callback_details;
			}
			if($priority_callbacks)
				$new_callbacks[$priority] = $priority_callbacks;
		}
		$hook_obj->callbacks = $new_callbacks;
		return $hook_obj;
	}

	//check class + function name!
	function is_callback_allowed($callback_details,$allowed_hooks) {
		$result = false;
		foreach($allowed_hooks as $callback_name) {
			list($class_name,$func_name) = explode("|",$callback_name);
			if( count($callback_details['function']) != 2)
				continue;
			if( $class_name ==  get_class($callback_details['function'][0])  AND $func_name == $callback_details['function'][1]) {
				$result = true;
				break;// done!
			}
		}
		return $result;
	}

	public function woocommerce_add_to_cart_sold_individually_found_in_cart( $found, $product_id, $variation_id, $cart_item_data, $cart_id ) {
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( $cart_item['product_id'] == $product_id ) {
				return true;
			}
		}

		return false;
	}

	private static function get_nopriv_ajax_actions() {
		return array(
			'get_table_with_product_bulk_table',
			'get_price_product_with_bulk_table',
		);
	}

	public static function is_nopriv_ajax_processing() {
		return wp_doing_ajax() && ! empty( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], self::get_nopriv_ajax_actions() );
	}
}