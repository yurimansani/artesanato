<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WDP_Cart {
	/** @var WDP_Cart_Context */
	private $context;

	private $original_items;
	private $external_coupons = array();
	private $cart_items;
	private $processed_items;
	private $grouped_processed_items;
	private $sort = 'expensive';

	private $fees = array();
	private $coupons = array();
	private $shipping = array();
	private $free_shipping = false;
	
	private $applied_coupons = array();	
	protected $grouped_coupons = array();
	protected $single_coupons = array();

	
	/**
	 * @see filter_product_items_callback
	 * @var int
	 * */
	private $product_id_to_filter;

	/**
	 * @param array            $items
	 * @param WDP_Cart_Context $context
	 */
	public function __construct( $items, $external_coupons, $context ) {
		$this->context        = $context;
		$this->original_items = $items;
		$this->external_coupons = $external_coupons;
		$this->cart_items     = array();
		uasort( $items, array( $this, 'sort_items' ) );

		foreach ( $items as $item ) {
			$this->insert_cart_item( $item );
		}
	}

	protected function sort_items( $item1, $item2 ) {
		$price1 = $item1['price'];
		$price2 = $item2['price'];

		if ( 'cheap' === $this->sort ) {
			if ( $price1 > $price2 ) {
				return 1;
			}
		} elseif ( 'expensive' === $this->sort ) {
			if ( $price1 < $price2 ) {
				return 1;
			}
		}

		return - 1;
	}

	public function sort_cart_items( $apply_to ) {
		if ( $this->sort !== $apply_to ) {
			$this->cart_items = array_reverse( $this->cart_items );
			$this->sort = $apply_to;
		}
	}

	public function get_context() {
		return $this->context;
	}
	
	public function get_external_coupons() {
		return $this->external_coupons;
	}

	/**
	 * @param array $cart_item
	 *
	 * @return array[]
	 */
	private function split_cart_item_by_qty( $cart_item ) {
		$count = $cart_item['quantity'];

		$cart_item['quantity'] = 1;
		$cart_item             = $this->add_cart_item_total( $cart_item );

		$ret = array_fill( 0, $count, $cart_item );

		return $ret;
	}

	private function add_cart_item_total( $cart_item ) {
	    if ( ! empty($cart_item['price']) ) {
            $cart_item['total'] = $cart_item['price'] * $cart_item['quantity'];
        }
		return $cart_item;
	}

	/**
	 * @param WC_product  $product
	 * @param int         $qty
	 * @param string|null $uid
	 *
	 * @return array|WP_Error
	 */
	public function add_new_cart_item( $product, $qty, $uid = null ) {
		$product_id = $product->get_id();
		$cart_item = array(
			'product_id'        => $product_id,
			'quantity'          => $qty,
			'price'             => $this->get_original_price( $product_id ),
			'added'             => true, // not used
			'uid'               => $uid,
			'sold_individually' => $product->is_sold_individually(),
			//fill fields same way , as in "make_from_wc_cart"
			'price_readonly' => self::_is_readonly_price( $product_id, $this->context->get_price_mode() ),
			'price_mode'     => $this->context->get_price_mode(),
			'woo_sale_price' => $product->get_sale_price( '' ),
			'woo_in_on_sale' => $product->is_on_sale( '' ),
			// don't use  ??
			//'variation'      => isset( $wc_cart_item['variation'] ) ? $wc_cart_item['variation'] : array(),
			//'original_item'  => $original_item,
		);
		
			
		return $this->insert_cart_item( $cart_item );
	}

	/**
	 * @param int $product_id
	 * @param int $qty
	 *
	 * @return array
	 */
	public function add_free( $product_id, $qty ) {
		$cart_item = array(
			'product_id' => $product_id,
			'quantity'   => $qty,
			'price'      => 0,
			'gifted'     => true,
		);

		return $this->insert_cart_item( $cart_item );
	}

	/**
	 * @param array $cart_item
	 *
	 * @return array
	 */
	private function insert_cart_item( $cart_item ) {
		$inserted_keys = array();

		$cart_item = array_merge(
			array(
				'fixed'          => false,
				'price_readonly' => false,
				'gifted'         => false,
				'rules'          => array(),
			),
			$cart_item
		);

		if ( ! empty( $cart_item['sold_individually'] ) ) {
			foreach ( $this->cart_items as $this_cart_item ) {
				if ( $this_cart_item['product_id'] == $cart_item['product_id'] ) {
					return array();
				}
			}
		}

		$new_cart_items = $this->split_cart_item_by_qty( $cart_item );
		foreach ( $new_cart_items as $new_cart_item ) {
			$new_cart_item['price'] = (float) $new_cart_item['price'];
			$this->cart_items[]     = $new_cart_item;
			end( $this->cart_items );
			$inserted_keys[] = key( $this->cart_items );
			reset( $this->cart_items );
		}

		return $inserted_keys;
	}

	public function modify_cart_item_qty( $cart_item_key, $qty ) {
		return $this->modify_cart_item( $cart_item_key, array( 'quantity' => $qty ) );
	}

	public function modify_cart_item( $cart_item_key, $cart_item_changes ) {
		$cart_item = array_merge( $this->cart_items[ $cart_item_key ], $cart_item_changes );
		$cart_item = $this->add_cart_item_total( $cart_item );

		$this->cart_items[ $cart_item_key ] = $cart_item;

		return true;
	}

	public function add_rule_discount_to_cart_item( $cart_item_key, $rule_id, $discount ) {
		$this->cart_items[ $cart_item_key ]['rules'][ $rule_id ] = $discount;

		return true;
	}

	public function fix_cart_item( $cart_item_key ) {
		$this->cart_items[ $cart_item_key ]['fixed'] = true;

		return true;
	}

	public function fix_cart_items( $cart_item_keys ) {
		foreach ( $cart_item_keys as $cart_item_key ) {
			$this->fix_cart_item( $cart_item_key );
		}
	}

	public function get_cart_items() {
		return array_filter( $this->cart_items, array( $this, 'filter_unfixed_items_callback' ) );
	}

	private function filter_unfixed_items_callback( $item ) {
		return empty( $item['fixed'] );
	}

	public function get_cart_subtotal() {
		return array_sum( array_column( $this->cart_items, 'total' ) );
	}

	public function get_cart_qty() {
		return array_sum( array_column( $this->cart_items, 'quantity' ) );
	}
	
	public function get_cart_contents_weight() {
		$weight = 0;
		foreach( $this->cart_items as $item) 
			$weight += (float)self::get_product_weight($item['product_id']) * $item['quantity'];
		return $weight;
	}
	
	public function get_product_weight( $product_id ) {
		$product = wc_get_product( $product_id );
		return $product->get_weight();
	}
	

	public function get_product_quantity( $product_id ) {
		$this->product_id_to_filter = (int) $product_id;

		$cart_items = array_filter( $this->get_cart_items(), array( $this, 'filter_product_items_callback' ) );
		$quantity   = array_sum( array_column( $cart_items, 'quantity' ) );

		$this->product_id_to_filter = null;

		return $quantity;
	}

	/** @see get_item_price */
	private $uid;

	/**
	 * @param $uid
	 *
	 * @return array|bool
	 *
	 */
	public function get_item_prices_to_display( $uid ) {
		$this->uid      = $uid;
		$filtered_items = array_filter( $this->cart_items, array( $this, 'filter_by_uid_callback' ) );
		$this->uid      = null;

		$item = reset( $filtered_items );

		$price = isset( $item['price'] ) ? $item['price'] : 0.0;
		$initial_price = isset( $item['initial_price'] ) ? $item['initial_price'] : 0.0;

		$product_id = ! empty( $item['product_id'] ) ? $item['product_id'] : false;
		if ( ! $product_id ) {
			return false;
		}
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return false;
		}

		$read_only = self::_is_readonly_price( $product_id, $this->context->get_price_mode() );

		if ( $read_only ) {
			//price is empty here
			if ( $initial_price > 0 ) {
				$price = $initial_price;
				$initial_price = false;
			} else {
				$initial_price = false;
			}
		}

		$initial_price = false !== $initial_price ? wc_get_price_to_display( $product, array( 'price' => $initial_price ) ) : $initial_price;
		$price         = wc_get_price_to_display( $product, array( 'price' => $price ) );

		if ( $initial_price < $price ) {
			// do not wc_format_sale_price if rule increase price
			$initial_price = false;
		}

		return array(
			'initial_price' => $initial_price,
			'price'         => $price,
		);
	}

	private function filter_by_uid_callback( $item ) {
		return isset( $item['uid'] ) && $item['uid'] === $this->uid;
	}

	private function filter_product_items_callback( $item ) {
		$product_id = (int) ! empty( $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'];

		return $product_id === $this->product_id_to_filter;
	}

	public function add_fee_amount( $fee_name, $fee_amount, $rule_id, $tax_class = "" ) {
		$this->fees[] = array(
			'type'      => 'amount',
			'value'     => $fee_amount,
			'rule_id'   => $rule_id,
			'name'      => $fee_name,
			'tax_class' => $tax_class,
		);
	}

	public function add_fee_percentage( $fee_name, $fee_percentage, $rule_id, $tax_class = "" ) {
		$this->fees[] = array(
			'type'      => 'percentage',
			'value'     => $fee_percentage,
			'rule_id'   => $rule_id,
			'name'      => $fee_name,
			'tax_class' => $tax_class,
		);
	}

	public function add_coupon_amount( $coupon_amount, $rule_id, $coupon_name = '' ) {
		$this->coupons[] = array(
			'type'    => 'amount',
			'value'   => $coupon_amount,
			'rule_id' => $rule_id,
			'name'    => $coupon_name,
		);
	}

	public function add_coupon_percentage( $coupon_percentage, $rule_id, $coupon_name = '' ) {
		$this->coupons[] = array(
			'type'    => 'percentage',
			'value'   => $coupon_percentage,
			'rule_id' => $rule_id,
			'name'    => $coupon_name,
		);
	}

	public function add_shipping_amount( $shipping_amount, $rule_id ) {
		$this->shipping[] = array(
			'type'    => 'amount',
			'value'   => $shipping_amount,
			'rule_id' => $rule_id,
		);
	}

	public function add_shipping_percentage( $shipping_percentage, $rule_id ) {
		$this->shipping[] = array(
			'type'    => 'percentage',
			'value'   => $shipping_percentage,
			'rule_id' => $rule_id,
		);
	}

	public function add_free_shipping( $rule_id ) {
		if ( empty( $this->free_shipping ) ) {
			$this->free_shipping = array(
				'rule_id' => $rule_id,
			);
		}
	}

	public function calc_totals() {
		$this->process_items();
		$this->group_processed_items();
	}

	private function process_items() {
		$items = $this->cart_items;

		$this->processed_items = $items;
	}

	private function group_processed_items() {
		$grouped_items = array();
		foreach ( $this->processed_items as $cart_item ) {
			$p_id      = $cart_item['product_id'];
			$is_gifted = (int) $cart_item['gifted'];
			$price     = (string) $cart_item['price'];
			$rules     = serialize( $cart_item['rules'] );
			$variation = isset( $cart_item['variation'] ) ? serialize( $cart_item['variation'] ) : serialize(array());

			// store wdp_rules to prevent split items that affected by the same bulk rule
			// fix ticket https://algolplus.freshdesk.com/a/tickets/1837
			if ( isset( $cart_item['original_item']['wdp_rules'] ) ) {
				$wdp_rules = $cart_item['original_item']['wdp_rules'];
				unset( $cart_item['original_item']['wdp_rules'] );
			} else {
				$wdp_rules = false;
			}

			$original_item = isset( $cart_item['original_item'] ) ? serialize( $cart_item['original_item'] ) : serialize(array());
			if ( ! isset( $grouped_items[ $p_id ] ) ) {
				$grouped_items[ $p_id ] = array();
			}
			if ( ! isset( $grouped_items[ $p_id ][ $variation ] ) ) {
				$grouped_items[ $p_id ][ $variation ] = array();
			}
			if ( ! isset( $grouped_items[ $p_id ][ $variation ][ $is_gifted ] ) ) {
				$grouped_items[ $p_id ][ $variation ][ $is_gifted ] = array();
			}
			if ( ! isset( $grouped_items[ $p_id ][ $variation ][ $is_gifted ][ $rules ] ) ) {
				$grouped_items[ $p_id ][ $variation ][ $is_gifted ][ $rules ] = array();
			}
			if ( ! isset( $grouped_items[ $p_id ][ $variation ][ $is_gifted ][ $rules ][ $price ] ) ) {
				$grouped_items[ $p_id ][ $variation ][ $is_gifted ][ $rules ][ $price ] = array();
			}
			if ( ! isset( $grouped_items[ $p_id ][ $variation ][ $is_gifted ][ $rules ][ $price ][ $original_item ] ) ) {
				$grouped_items[ $p_id ][ $variation ][ $is_gifted ][ $rules ][ $price ][ $original_item ] = 0;
			}
			$grouped_items[ $p_id ][ $variation ][ $is_gifted ][ $rules ][ $price ][ $original_item ] += $cart_item['quantity'];
		}



		$this->grouped_processed_items = array();
		foreach ( $grouped_items as $p_id => $grouped_by_type ) {
			foreach ( $grouped_by_type as $variation => $grouped_by_variation ) {
				foreach ( $grouped_by_variation as $is_gifted => $grouped_by_rules ) {
					foreach ( $grouped_by_rules as $rules => $grouped_by_price ) {
						foreach ( $grouped_by_price as $price => $grouped_by_original_item ) {
							foreach ( $grouped_by_original_item as $original_item => $qty ) {
								$temp_price = (float) $price;
								$temp_rules = unserialize( $rules );
								$temp_variation = unserialize( $variation );
								$temp_original_item = unserialize( $original_item );

								// restore wdp_rules
								if ( isset( $wdp_rules ) && $wdp_rules !== false ) {
									$temp_original_item['wdp_rules'] = $wdp_rules;
								}
								foreach ( $temp_rules as &$rule ) {
									$rule = $rule * $qty;
								}

								$this->grouped_processed_items[] = array(
									'product_id' => $p_id,
									'variation' => $temp_variation,
									'price'      => $temp_price,
									'quantity'   => $qty,
									'total'      => $price * $qty,
									'gifted'     => $is_gifted,
									'rules'      => $temp_rules,
									'original_item'=> $temp_original_item,
								);
							}
						}
					}
				}
			}
		}
	}
	
	function get_product_variation($product_id, $variation_id, $cart_item ) {
		if ( isset($cart_item['variation']) ) {
			return $cart_item['variation'];
		}
		$variations = array();
		//gather variation attributes
		$variation_data = wc_get_product_variation_attributes( $variation_id );
		$main_product = wc_get_product( $product_id );
		foreach ( $main_product->get_attributes() as $attribute ) {
			if ( ! $attribute['is_variation'] ) {
				continue;
			}
			// Get valid value from variation data.
			$taxonomy    = 'attribute_' . sanitize_title( $attribute['name'] );
			$valid_value = isset( $variation_data[ $taxonomy ] ) ? $variation_data[ $taxonomy ] : '';				
			if( $valid_value  )
				$variations[ $taxonomy ] = $valid_value;
		}	
		return $variations;
	}	
	
	
	/**
	 * @param WC_Cart $wc_cart
	 *
	 * @return bool
	 */
	public function apply_to_wc_cart( $wc_cart ) {
		/** Store removed_cart_contents to enable undo deleted items */
		$removed_cart_contents = $wc_cart->get_removed_cart_contents();
		$wc_cart->empty_cart();
		$wc_cart->set_removed_cart_contents($removed_cart_contents);

		$this->calc_totals();

		// Suppress total recalculation until finished.
		remove_action( 'woocommerce_add_to_cart', array( WC()->cart, 'calculate_totals' ), 20, 0 );

		$original_prices = array();
		foreach ( $this->grouped_processed_items as $cart_item_key => $cart_item ) {
			//TODO: convert wdp cart items to wc cart items +
			$p_id    = $cart_item['product_id'];
			$product = wc_get_product( $p_id );
			$product->set_price( $cart_item['price'] );

			if ( $product instanceof WC_Product_Variation ) {
				/** @var WC_Product_Variation $product */
				$product_id   = $product->get_parent_id();
				$variation_id = $p_id;
				$variation = $this->get_product_variation($product_id, $variation_id, $cart_item );
			} else {
				$product_id   = $p_id;
				$variation_id = null;
				$variation = array();
			}

			$cart_item_data = array(
// 				'line_total'    => $cart_item['total'],
// 				'line_subtotal' => $cart_item['total'],
				'wdp_gifted'    => $cart_item['gifted'],
				'wdp_rules'     => $cart_item['rules'],
//				'data'     		=> $product,
			);

			$original_cart_item_data = array_diff_key( $cart_item['original_item'], $cart_item_data );
			$cart_item_data = array_merge($cart_item_data, $original_cart_item_data);

			//show old price?
			if ( $this->context->is_show_striked_prices() ) {
				//if ( ! isset( $original_prices[ $p_id ] ) ) {
				//	$original_prices[ $p_id ] = $this->get_original_price( $p_id , $cart_item_data );
				//}
				$cart_item_data['wdp_original_price'] = $this->get_original_price( $p_id , $cart_item_data ) ;
			}

			// unset filter to prevent delete external plugin cart item data
			global $wp_filter;
			if ( isset( $wp_filter['woocommerce_add_cart_item_data'] ) ) {
				$stored_actions = $wp_filter['woocommerce_add_cart_item_data'];
				unset( $wp_filter['woocommerce_add_cart_item_data'] );
			} else {
				$stored_actions = array();
			}
			//done, can add
			$cart_item_key = $wc_cart->add_to_cart( $product_id, $cart_item['quantity'], $variation_id, $variation,
				$cart_item_data );
			// restore hook
			if ( ! empty( $stored_actions ) ) {
				$wp_filter['woocommerce_add_cart_item_data'] = $stored_actions;
			}

			//Must  replace the product in the cart!
			if( $cart_item_key ) {
				$wc_cart->cart_contents[ $cart_item_key ] [ 'data' ] =  $product;
				// restore cart item data after rules applied
				foreach ( $original_cart_item_data as $key => $value ) {
					$wc_cart->cart_contents[ $cart_item_key ][ $key ] = $value;
				}
			}
		}

		add_action( 'woocommerce_add_to_cart', array( WC()->cart, 'calculate_totals' ), 20, 0 );

		//TODO: add fee to WC_Cart +
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'woocommerce_cart_calculate_fees' ), 10, 1 );

		//TODO: add coupons to WC_Cart +
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'woocommerce_cart_calculate_coupons' ), 10, 1 );
		add_filter( 'woocommerce_get_shop_coupon_data', array( $this, 'woocommerce_get_shop_coupon_data' ), 10, 2 );

		$this->apply_coupons_to_wc_cart( $wc_cart );

		//TODO: add free shipping to WC_Cart +
		add_filter( 'woocommerce_package_rates', array( $this, 'woocommerce_package_rates' ), 10, 2 );
		add_filter( 'woocommerce_cart_shipping_method_full_label',
			array( $this, 'woocommerce_cart_shipping_method_full_label' ), 10, 2 );

		// delete [Remove] for coupouns
		if ( $this->applied_coupons ) {
			add_filter( 'woocommerce_cart_totals_coupon_html', array( $this, 'woocommerce_cart_totals_coupon_html' ), 10, 3 );
		}

		// To apply shipping we have to clear stored packages in session to allow 'woocommerce_package_rates' filter run
		foreach ( WC()->session->get_session_data() as $key => $value ) {
			if ( preg_match( '/(shipping_for_package_).*/', $key ) ) {
				unset(WC()->session->$key);
			}
		}

		$wc_cart->calculate_totals();
		return true;
	}
	
	// Hide [Remove] link
	public function woocommerce_cart_totals_coupon_html( $coupon_html, $coupon, $discount_amount_html ) {
		if( in_array($coupon->get_code(), $this->applied_coupons) ) {
			$coupon_html = $discount_amount_html;
		}
		return $coupon_html;
	}

	/**
	 * @param string           $label
	 * @param WC_Shipping_Rate $method
	 *
	 * @return mixed
	 */
	public function woocommerce_cart_shipping_method_full_label( $label, $method ) {
		if ( false !== strpos( $label, 'wdp-amount' ) ) {
			return $label;
		}

		$meta_data = $method->get_meta_data();
		if ( ! isset( $meta_data['wdp_initial_cost'] ) ) {
			return $label;
		}

		$initial_cost      = $meta_data['wdp_initial_cost'];
		$initial_cost_html = '<del>' . wc_price( $initial_cost ) . '</del>';
		$initial_cost_html = preg_replace( '/\samount/is', 'wdp-amount', $initial_cost_html );

//		if ( $method->get_cost() > 0 ) {
		$label = preg_replace( '/(<span[^>]*>)/is', $initial_cost_html . ' $1', $label, 1 );
//		} else {
//			$label .= ': ' . $initial_cost_html . ' ' . wc_price( 0 );
//		}

		return $label;
	}

	/**
	 * @param WC_Shipping_Rate[] $rates
	 * @param array              $package
	 *
	 * @return WC_Shipping_Rate[]
	 */
	public function woocommerce_package_rates( $rates, $package ) {
		if ( ! empty( $this->free_shipping ) ) {
			foreach ( $rates as &$rate ) {
				$meta_data = $rate->get_meta_data();
				if ( isset( $meta_data['wdp_initial_cost'] ) ) {
					$cost = $meta_data['wdp_initial_cost'];
				} else {
					$cost = $rate->get_cost();
				}

				$rate->add_meta_data( 'wdp_initial_cost', $cost );
				$rate->add_meta_data( 'wdp_rule', $this->free_shipping['rule_id'] );
				$rate->add_meta_data( 'wdp_amount', $cost );
				$rate->add_meta_data( 'wdp_free_shipping', true );
				$rate->set_cost( 0 );
				$rate->set_taxes( array() ); // no taxes
			}
		} else {
			$applied_shipping = array();
			foreach ( $rates as &$rate ) {
				$rate_id   = $rate->get_id();
				$meta_data = $rate->get_meta_data();
				if ( isset( $meta_data['wdp_initial_cost'] ) ) {
					$cost = $meta_data['wdp_initial_cost'];
				} else {
					$cost = $rate->get_cost();
				}
				foreach ( $this->shipping as $i => $item ) {
					$amount = 0;
					if ( 'amount' === $item['type'] ) {
						$amount = $item['value'];
					} elseif ( 'percentage' === $item['type'] ) {
						$amount =  $cost * $item['value'] / 100;
					}

					if ( empty( $amount ) ) {
						continue;
					}
					if ( ! isset( $applied_shipping[ $rate_id ] ) || $amount > $applied_shipping[ $rate_id ]['amount'] ) {
						$applied_shipping[ $rate_id ] = array(
							'shipping_id' => $i,
							'amount'      => $amount,
						);
					}
				}
				if ( ! empty( $applied_shipping[ $rate_id ] ) ) {
					$shipping = $this->shipping[ $applied_shipping[ $rate_id ]['shipping_id'] ];
					$amount   = $applied_shipping[ $rate_id ]['amount'];

					$rate->add_meta_data( 'wdp_initial_cost', $cost );
					$rate->add_meta_data( 'wdp_rule', $shipping['rule_id'] );
					$rate->add_meta_data( 'wdp_amount', $amount );
					$rate->add_meta_data( 'wdp_free_shipping', false );
					$newcost = $cost - $amount;
					if( $newcost<0 )
						$newcost  = 0;
					$rate->set_cost( $newcost );
					
					// recalc taxes
					if( $cost>0 ) {
						$perc = $newcost/$cost;
						$taxes = $rate->get_taxes();
						foreach( $taxes  as $k=>$v )
							$taxes[$k] = $v*$perc;
						$rate->set_taxes( $taxes );	
					}		
					//
				}
			}//each not free shipping!
		}

		return $rates;
	}

	/**
	 * @param WC_Cart $wc_cart
	 */
	public function woocommerce_cart_calculate_coupons( $wc_cart ) {
		$applied_grouped_coupons = array();
		foreach ( $this->grouped_coupons as $coupon_name => $coupon_ids ) {
			if ( ! isset( $applied_grouped_coupons[ $coupon_name ] ) ) {
				$applied_grouped_coupons[ $coupon_name ] = array();
			}
			foreach ( $coupon_ids as $coupon_id ) {
				$coupon  = $this->coupons[ $coupon_id ];
				$rule_id = $coupon['rule_id'];
				$amount  = $coupon['value'];

				if ( ! isset( $applied_grouped_coupons[ $coupon_name ][ $rule_id ] ) ) {
					$applied_grouped_coupons[ $coupon_name ][ $rule_id ] = 0;
				}
				$applied_grouped_coupons[ $coupon_name ][ $rule_id ] += $amount;
			}
		}

		$applied_single_coupons = array();
		foreach ( $this->single_coupons as $coupon_name => $coupon_id ) {
			$coupon  = $this->coupons[ $coupon_id ];
			$rule_id = $coupon['rule_id'];

			$applied_single_coupons[ $coupon_name ] = $rule_id;
		}

		$applied_coupons = array(
			'grouped' => $applied_grouped_coupons,
			'single'  => $applied_single_coupons,
		);

		//TODO: fix this for WC 3.2 +
		$totals                = $wc_cart->get_totals();
		$totals['wdp_coupons'] = $applied_coupons;
		$wc_cart->set_totals( $totals );
	}

	/**
	 * @param WC_Cart $wc_cart
	 */
	private function apply_coupons_to_wc_cart( $wc_cart ) {

		$this->grouped_coupons = array();
		$this->single_coupons  = array();
		foreach ( $this->coupons as $coupon_id => $coupon ) {
			if( empty($coupon['value']) ) // skip zero coupons?
				continue;
				
			if ( 'amount' === $coupon['type'] ) {
				if ( $this->context->is_combine_multiple_discounts() || empty( $coupon['name'] ) ) {
					$coupon_name = $this->context->get_default_discount_name();
				} else {
					$coupon_name = $coupon['name'];
				}
				$coupon_name = strtolower( $coupon_name );

				if ( ! isset( $this->grouped_coupons[ $coupon_name ] ) ) {
					$this->grouped_coupons[ $coupon_name ] = array();
				}
				$this->grouped_coupons[ $coupon_name ][] = $coupon_id;
			} else {
				$template = ! empty( $coupon['name'] ) ? $coupon['name'] : $this->context->get_default_discount_name();
				$template = strtolower( $template );

				$count = 1;
				do {
					$coupon_name = "{$template} #{$count}";
					$count ++;
				} while ( isset( $this->single_coupons[ $coupon_name ] ) );

				$this->single_coupons[ $coupon_name ] = $coupon_id;
			}
		}
		
		// remove postfix for single %% discount 
		if( count($this->single_coupons) == 1 ) {
			$keys = array_keys($this->single_coupons);
			$values = array_values($this->single_coupons); 
			$this->single_coupons = array( str_replace(' #1', '', $keys[0]) =>$values[0] );
		}	
		
		$this->applied_coupons = array();
		add_filter('woocommerce_coupon_message', array( $this, 'suppress_add_coupon_msg'), 10, 3 );
		
		foreach ( $this->external_coupons as $coupon_name ) {
			$wc_cart->apply_coupon( $coupon_name );
		}
		
		foreach ( array_keys( $this->grouped_coupons ) as $coupon_name ) {
			$this->applied_coupons[] = $coupon_name;
			$wc_cart->apply_coupon( $coupon_name );
		}

		foreach ( array_keys( $this->single_coupons ) as $coupon_name ) {
			$this->applied_coupons[] = $coupon_name;
			$wc_cart->apply_coupon( $coupon_name );
		}
		remove_filter('woocommerce_coupon_message', array( $this, 'suppress_add_coupon_msg'), 10 );
	}
	
	function suppress_add_coupon_msg($msg, $msg_text, $coupon) {
		return '';
	}

	/**
	 * @param mixed $coupon
	 * @param mixed $data
	 *
	 * @return array|mixed
	 */
	public function woocommerce_get_shop_coupon_data( $coupon, $data ) {
		if ( isset( $this->grouped_coupons[ $data ] ) ) {
			$grouped_coupon = array(
				'id'            => rand( 0, 1000 ),
				'discount_type' => 'fixed_cart',
				'amount'        => 0,
			);
			foreach ( $this->grouped_coupons[ $data ] as $coupon_id ) {
				if ( ! empty( $this->coupons[ $coupon_id ] ) ) {
					$grouped_coupon['amount'] += (float) $this->coupons[ $coupon_id ]['value'];
				}
			}
			if ( ! empty( $grouped_coupon['amount'] ) ) {
				$coupon = $grouped_coupon;
			}
		} elseif ( isset( $this->single_coupons[ $data ] ) ) {
			$coupon_id = $this->single_coupons[ $data ];

			if ( isset( $this->coupons[ $coupon_id ] ) ) {
				$coupon_data   = $this->coupons[ $coupon_id ];
				$coupon_type   = ( 'amount' === $coupon_data['type'] ? 'fixed_cart' : 'percent' );
				$coupon_amount = (float) $coupon_data['value'];

				if ( ! empty( $coupon_amount ) ) {
					$coupon = array(
						'id'            => $coupon_id + 1,
						'discount_type' => $coupon_type,
						'amount'        => $coupon_amount,
					);
				}
			}
		}
		
		return $coupon;
	}

	/**
	 * @param WC_Cart $wc_cart
	 */
	public function woocommerce_cart_calculate_fees( $wc_cart ) {
		$applied_fees = array();
		$fees_tax = array();
		$cart_total   = wc_prices_include_tax() ? $wc_cart->get_cart_contents_total() + $wc_cart->get_cart_contents_tax() : $wc_cart->get_cart_contents_total();
		foreach ( $this->fees as $i => $fee ) {
			$fee_amount = 0;
			if ( 'amount' === $fee['type'] ) {
				$fee_amount = $fee['value'];
			} elseif ( 'percentage' === $fee['type'] ) {
				$fee_amount = $cart_total * $fee['value'] / 100;
			}

			if ( ! empty( $fee_amount ) ) {
				//TODO: set fee name +
				if ( $this->context->is_combine_multiple_fees() || empty( $fee['name'] ) ) {
					$fee_name = $this->context->get_default_fee_name();
				} else {
					$fee_name = $fee['name'];
				}

				$tax_class = ! empty( $fee['tax_class'] ) ? $fee['tax_class'] : "";
				$taxable = (boolean)$tax_class;

				$fees_tax[ $fee_name ] = array(
					'taxable'   => $taxable,
					'tax_class' => $tax_class,
				);

				if ( ! isset( $applied_fees[ $fee_name ][ $fee['rule_id'] ] ) ) {
					$applied_fees[ $fee_name ][ $fee['rule_id'] ] = 0;
				}

				$applied_fees[ $fee_name ][ $fee['rule_id'] ] += $fee_amount;
			}
		}

		foreach ( $applied_fees as $fee_name => $amount_per_rule ) {
			$wc_cart->add_fee( $fee_name, array_sum( $amount_per_rule ), $fees_tax[ $fee_name ]['taxable'],
				$fees_tax[ $fee_name ]['tax_class'] );
		}

		$totals             = $wc_cart->get_totals();
		$totals['wdp_fees'] = $applied_fees;
		$wc_cart->set_totals( $totals );
	}

	private $cached_original_price;

	public function get_original_price( $product_id, $item_meta = false ) {
		$original_price = false;
		if ( isset( $this->cached_original_price[ $product_id ] ) ) {
			$original_price = $this->cached_original_price[ $product_id ];
		} else {
			$original_price                             = self::_get_original_price( $product_id, $this->context->get_price_mode(), $item_meta );
			$this->cached_original_price[ $product_id ] = $original_price;
		}

		return $original_price;
	}

	public function is_readonly_price( $product_id ) {
		return self::_is_readonly_price( $product_id, $this->context->get_price_mode() );
	}

	protected static function _get_original_price( $product_id, $price_mode , $item_meta = false ) {
		$product = wc_get_product( $product_id );

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
		return apply_filters("wdp_get_product_price", $price, $product, $price_mode, $item_meta);
	}

	protected static function _is_readonly_price( $product_id, $price_mode ) {
		$product = wc_get_product( $product_id );

		if ( $product->is_on_sale( 'edit' ) ) {
			if ( 'sale_price' === $price_mode ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param WC_Cart          $wc_cart
	 * @param WDP_Cart_Context $context
	 *
	 * @return WDP_Cart
	 */
	public static function make_from_wc_cart( $wc_cart, $context ) {
		$items = array();

		//TODO: delete gifted products +
		$wc_cart_items = $wc_cart->get_cart();
		
		foreach ( $wc_cart_items as $wc_cart_item_key => $wc_cart_item ) {
			if ( ! empty( $wc_cart_item['wdp_gifted'] ) ) {
				continue;
			}
			$product_id = ! empty( $wc_cart_item['variation_id'] ) ? $wc_cart_item['variation_id'] : $wc_cart_item['product_id'];
			$product = wc_get_product( $product_id );

			$original_item  = $wc_cart_item;
			$default_keys = array(
				'key',
				'product_id',
				'variation_id',
				'variation',
				'quantity',
				'data',
				'data_hash',
				'line_tax_data',
				'line_subtotal',
				'line_subtotal_tax',
				'line_total',
				'line_tax',
			);
			foreach ( $default_keys as $key ) {
				unset($original_item[$key]);
			}

			$item = array(
				'product_id'     => $product_id,
				'quantity'       => $wc_cart_item['quantity'],
				'price'          => self::_get_original_price( $product_id, $context->get_price_mode(), $original_item ),
				'price_readonly' => self::_is_readonly_price( $product_id, $context->get_price_mode() ),
				'price_mode'     => $context->get_price_mode(),
				'woo_sale_price' => $product->get_sale_price( '' ),
				'woo_in_on_sale' => $product->is_on_sale( '' ),
				'variation'      => isset( $wc_cart_item['variation'] ) ? $wc_cart_item['variation'] : array(),
				'original_item'  => $original_item,
			);

			$items[ $wc_cart_item_key ] = $item;
		}
		$external_coupons = array();
		foreach( $wc_cart->get_coupons() as $coupon ) {
			if( $coupon->get_id() )
				$external_coupons[] = $coupon->get_code();
		}
		$cart = new self( $items, $external_coupons, $context );

		return $cart;
	}
}