<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WDP_Rule_Product_Package implements WDP_Rule {
	use WDP_Price_Calc;
	use WDP_Product_Filtering;

	protected $data;

	/** @var WDP_Cart_Adjustment[] */
	protected $cart_adjustments = array();
	/** @var WDP_Condition[] */
	protected $conditions = array();
	/** @var WDP_Limit[] */
	protected $limits = array();

	protected $options = array();

	private $roles_applied = false;

	function __construct( $rule ) {
		$this->data = $rule;

		if ( ! empty( $this->data['cart_adjustments'] ) ) {
			foreach ( $this->data['cart_adjustments'] as $cart_adj_data ) {
				try {
					$cart_adj                 = WDP_Cart_Adj_Registry::get_instance()->create_adj( $cart_adj_data );
					$this->cart_adjustments[] = $cart_adj;
				} catch ( Exception $exception ) {
				}
			}
		}

		if ( ! empty( $this->data['conditions'] ) ) {
			foreach ( $this->data['conditions'] as $condition_data ) {
				try {
					$condition          = WDP_Condition_Registry::get_instance()->create_condition( $condition_data );
					$this->conditions[] = $condition;
				} catch ( Exception $exception ) {
				}
			}
		}

		if ( ! empty( $this->data['limits'] ) ) {
			foreach ( $this->data['limits'] as $limit_data ) {
				try {
					$limit          = WDP_Limit_Registry::get_instance()->create_limit( $limit_data );
					$this->limits[] = $limit;
				} catch ( Exception $exception ) {
				}
			}
		}

		$this->options = WDP_Helpers::get_settings();
	}

	public function get_id() {
		return (int) $this->data['id'];
	}

	public function is_exclusive() {
		return (bool) $this->data['exclusive'];
	}

	public function get_priority() {
		return (int) $this->data['priority'];
	}

	protected function has_filters() {
		$filters = $this->get_filters();

		return ! empty( $filters );
	}

	protected function get_filters() {
		$filters = isset( $this->data['filters'] ) ? $this->data['filters'] : array();

		if ( empty( $filters ) ) {
			$filters[] = array(
				'qty'    => 1,
				'type'   => 'products',
				'method' => 'any',
				'value'  => array(),
			);
		}

		return $filters;
	}

	public function get_count_of_product_dependencies() {
		$filters_count = count( $this->get_filters() );

		$conditions_count = 0;
		foreach ( $this->conditions as $condition ) {
			if ( $condition->has_product_dependency() ) {
				$conditions_count ++;
			}
		}

		$total = $filters_count + $conditions_count;

		return $total;
	}

	public function get_product_dependencies() {
		$dependencies = array();
		foreach ( $this->get_filters() as $filter ) {
			$dependencies[] = array(
				'qty'    => $filter['qty'],
				'type'   => $filter['type'],
				'method' => $filter['method'],
				'values' => $filter['value'],
			);
		}

		//TODO: not now
//		foreach ( $this->conditions as $condition ) {
//			if ( $condition->has_product_dependency() ) {
//				$dependencies[] = $condition->get_product_dependency();
//			}
//		}

		return $dependencies;
	}

	public function has_bulk() {
		return ! empty( $this->data['bulk_adjustments']['ranges'] );
	}

	public function has_roles_discount() {
		return ! empty( $this->data['role_discounts']['rows'] );
	}

	public function get_bulk_details() {
		if ( ! $this->has_bulk() ) {
			return false;
		}

		$bulk   = $this->maybe_update_bulk_prices();
		$ranges = $bulk['ranges'];

		$ret = array(
			'type'          => $bulk['type'],
			'discount'      => $bulk['discount_type'],
			'ranges'        => array(),
			'table_message' => $bulk['table_message'],
		);
		foreach ( $ranges as $range ) {
			$ret['ranges'][] = array(
				'from'  => $range['from'],
				'to'    => $range['to'],
				'value' => $range['value'],
			);
		}

		return $ret;
	}


	/**
	 * Change bulk prices according ONLY roles rules.
	 * Apply only if roles has larger priority and bulk adjustments type is fixed price.
	 * Affected only in bulk table.
	 * We need not to apply other rule sections because fixed price from bulk overrides them.
	 * Be advised, you must rework this method if order of submethods in "apply_to_cart" has been changed.
	 *
	 * @return array
	 */
	private function maybe_update_bulk_prices() {
		$bulk = $this->data['bulk_adjustments'];
		if ( empty( $bulk['discount_type'] ) || "price__fixed" !== $bulk['discount_type'] ) {
			return $bulk;
		}

		$update = false;
		if ( ! empty( $this->data['sortable_blocks_priority'] ) && is_array( $this->data['sortable_blocks_priority'] ) ) {
			$role_priority = - 1;
			$bulk_priority = - 1;

			foreach ( $this->data['sortable_blocks_priority'] as $key => $priority_label ) {
				if ( "roles" == $priority_label ) {
					$role_priority = $key;
				} elseif ( "bulk-adjustments" == $priority_label ) {
					$bulk_priority = $key;
				}
			}

			if ( $role_priority !== - 1 && $bulk_priority !== - 1 && ( $role_priority > $bulk_priority ) ) {
				$update = true;
			}
		}


		if ( $update && ! empty( $bulk['ranges'] ) ) {
			$roles_rule = array();

			$roles_rules = $this->data['role_discounts']['rows'];

			if ( ! ( $role = $this->get_current_user_role() ) ) {
				return $bulk;
			}

			foreach ( $roles_rules as $tmp_roles_rule ) {
				if ( empty( $tmp_roles_rule['roles'] ) ) {
					continue;
				}
				$roles = $tmp_roles_rule['roles'];
				if ( ! in_array( $role, $roles ) ) {
					continue;
				}

				$roles_rule = $tmp_roles_rule;
			}

			foreach ( $bulk['ranges'] as &$range ) {
				if ( empty( $range['value'] ) ) {
					continue;
				}
				$prices = array( $range['value'] );

				$prices = $this->calculate_prices(
					$prices,
					$roles_rule['discount_type'],
					$roles_rule['discount_value']
				);

				$range['value'] = reset( $prices );
			}

		}

		return $bulk;
	}

	private function get_current_user_role() {
		$user = wp_get_current_user();
		if ( ! $user OR empty( $user->roles ) ) {
			return false;
		}
		$roles = ( array ) $user->roles;

		return isset( $roles[0] ) ? $roles[0] : false;
	}

	public function is_product_matched( $cart, $product_id ) {
		$this->cart = $cart;

		$limits_verified = $this->check_limits();
		if ( ! $limits_verified ) {
			return false;
		}

		$conditions_verified = $this->check_conditions_for_a_match();
		if ( ! $conditions_verified ) {
			return false;
		}

		$matched = $this->match_product_with_filters( $product_id );

		return $matched;
	}

	/** @var  WDP_Cart */
	private $cart;
	private $items;
	private $add_products;
	private $used_items_all;
	private $used_items_by_attempt;
	private $used_items_by_filters;
	private $attempt;

	protected function init_applying_to_cart( $cart ) {
		$this->cart                  = $cart;
		$this->cart->sort_cart_items( $this->data['options']['apply_to'] );
		$this->items                 = $this->cart->get_cart_items();
		$this->add_products          = array();
		$this->used_items_all        = array();
		$this->used_items_all_KEYS   = array();
		$this->used_items_by_attempt = array();
		$this->used_items_by_filters = array();
		$this->attempt               = 0;

		foreach ( $this->items as &$item ) {
			$item['initial_price'] = $item['price'];
		}

	}

	public function apply_to_cart( $cart ) {
		$this->init_applying_to_cart( $cart );
		//TODO: check limits +
		$limits_verified = $this->check_limits();
		if ( ! $limits_verified ) {
			return false;
		}

		//TODO: check conditions +
		$conditions_verified = $this->check_conditions();
		if ( ! $conditions_verified ) {
			return false;
		}
		$is_valid = false;
		while ( $this->check_attempt() ) {
			//TODO: check product filters +
			$is_checked = $this->check_product_filters();
			if ( ! $is_checked ) {
				break;
			}

			//TODO: apply product adjustments +
			$this->apply_product_adjustment();

			if ( $this->check_deal_attempt() ) {
				//TODO: apply get products +
				$this->apply_deal_adjustment();
			}

			$is_valid = true;
			$this->attempt ++;
		}

		if ( ! $is_valid ) {
			return false;
		}

		//TODO: check max discount sum for product adjustments +
		$this->check_max_discount_sum();

		if ( ! empty( $this->data['sortable_blocks_priority'] ) && is_array( $this->data['sortable_blocks_priority'] ) ) {
			foreach ( $this->data['sortable_blocks_priority'] as $block_name ) {
				if ( 'roles' == $block_name ) {
					$this->apply_roles_discount();
				} elseif ( 'bulk-adjustments' == $block_name && ( empty( $this->data['role_discounts']['dont_apply_bulk_if_roles_matched'] ) || ! $this->roles_applied ) ) {
					//TODO: apply bulk adjustment +
					$this->apply_bulk_adjustment();
				}
			}
		} else {
			$this->apply_roles_discount();

			//TODO: apply bulk adjustment +
			$this->apply_bulk_adjustment();
		}

		//TODO: apply cart adjustment +
		$this->apply_cart_adjustment();

		//TODO: apply changes to cart +
		$this->apply_changes_to_cart();

//		echo json_encode( $this->items, JSON_PRETTY_PRINT );

		return true;
	}

	protected function check_limits() {
		foreach ( $this->limits as $limit ) {
			if ( ! $limit->check( $this->cart, $this->get_id() ) ) {
				return false;
			}
		}

		return true;
	}

	protected function check_conditions() {
		if ( empty( $this->conditions ) ) {
			return true;
		}
			
		$relationship = ! empty( $this->data['additional']['conditions_relationship'] ) ? $this->data['additional']['conditions_relationship'] : 'and';
		$result = false;
		foreach ( $this->conditions as $condition ) {
			if ( ! $condition->check( $this->cart ) ) {
				if ( 'and' == $relationship ) {
					return false;
				}
				continue;
			}

			$involved_items = $condition->get_involved_cart_items();
			if ( ! empty( $involved_items ) ) {
				foreach ( $involved_items as $item_key ) {
					$this->used_items_all[] = $item_key;
				}
			}

			// check_conditions always true if relationship not 'and' and at least one condition checked
			$result = true;
		}

		return $result;
	}

	protected function check_conditions_for_a_match() {
		if ( empty( $this->conditions ) ) {
			return true;
		}
		
		$relationship = ! empty( $this->data['additional']['conditions_relationship'] ) ? $this->data['additional']['conditions_relationship'] : 'and';
		$result = false;
		foreach ( $this->conditions as $condition ) {
			if ( !$condition->match( $this->cart ) ) {
				if( 'and' == $relationship ) 
					return false;
			} else {
				if ( 'or' == $relationship ) 
					return true;
				else	
					$result = true;
			}
		}

		return $result;
	}

	protected function check_max_discount_sum() {
		$limit = $this->data['product_adjustments']['max_discount_sum'];

		if ( empty( $limit ) ) {
			return true;
		}

		$changed_items = array();

		$discount_sum = 0;
		foreach ( $this->get_keys_of_changeable_prices() as $item_key ) {
			$item = $this->items[ $item_key ];

			$item_discount = 0;
			if ( isset( $item['initial_price'] ) ) {
				$item_discount = $item['initial_price'] - $item['price'];
			}

			if ( ! empty( $item_discount ) ) {
				$discount_sum    += $item_discount;
				$changed_items[] = $item_key;
			}
		}

		if ( $discount_sum <= $limit ) {
			return true;
		}

		//TODO: apply max discount sum option +
		//TODO: split sum between last attempts +
		$left_to_increase = $discount_sum - $limit;

		$changed_items = array_reverse( $changed_items );
		foreach ( $changed_items as $item_key ) {
			$item = $this->items[ $item_key ];

			$increases_sum = min( $item['initial_price'] - $item['price'], $left_to_increase );
			$new_price     = $item['price'] + $increases_sum;

			$new_price = $this->maybe_maximize_discount( $item, $new_price );

			$item['adjusted_price'] = $new_price;
			$item['price']          = $new_price;

			$this->items[ $item_key ] = $item;

			$left_to_increase -= $increases_sum;
			if ( $left_to_increase <= 0 ) {
				break;
			}
		}

		return true;
	}

	protected function check_attempt() {
		$attempts_limit = (int) $this->data['options']['repeat'];

		return $attempts_limit < 0 || $this->attempt < $attempts_limit;
	}

	protected function check_deal_attempt() {
		$attempts_limit = (int) $this->data['get_products']['repeat'];

		return $attempts_limit < 0 || $this->attempt < $attempts_limit;
	}

	protected function apply_changes_to_cart() {
		$is_override_cents = $this->options['is_override_cents'];
		$cached_discount = array();

		foreach ( $this->used_items_all as $item_key ) {
			$item     = $this->items[ $item_key ];
			$item['price'] = $is_override_cents ? $this->override_cents($item['price']) : $item['price'];
			$key = md5( json_encode( $item ) );

			if ( isset( $cached_discount[ $key ] ) ) {
				$discount = $cached_discount[ $key ];
			} else {
				$product       = wc_get_product( $item['product_id'] );
				$initial_price = wc_get_price_to_display( $product, array( 'price' => $item['initial_price'] ) );
				$price         = wc_get_price_to_display( $product, array( 'price' => $item['price'] ) );

				$discount = $initial_price - $price;
				$cached_discount[ $key ] = $discount;
			}

			if ( abs( $discount ) > 0 ) {
				$this->cart->modify_cart_item( $item_key, $item );
				$this->cart->add_rule_discount_to_cart_item( $item_key, $this->get_id(), $discount );
			}
		}
		if ( $this->is_exclusive() ) {
			$this->cart->fix_cart_items( $this->used_items_all );
		}

		$added_item_keys = array();
		foreach ( $this->add_products as $product_id => $qty ) {
			$added_item_keys = array_merge(
				$added_item_keys,
				$this->cart->add_free( $product_id, $qty )
			);

			foreach ( $added_item_keys as $added_item_key ) {
				$this->cart->add_rule_discount_to_cart_item( $added_item_key, $this->get_id(), $this->cart->get_original_price( $product_id ) );
			}
		}
		if ( $this->is_exclusive() ) {
			$this->cart->fix_cart_items( $added_item_keys );
		}
	}

	protected function override_cents( $price ){
		$prices_ends_with  = $this->options['prices_ends_with'];

		$price_fraction = $price - intval( $price );
		$new_price_fraction = $prices_ends_with / 100;

		$round_new_price_fraction = round($new_price_fraction);


		if ( 0 == intval( $price ) AND 0 < $new_price_fraction ){
			$price = $new_price_fraction;
			return $price;
		}


		if ( $round_new_price_fraction ) {

			if ( $price_fraction <= $new_price_fraction - round(1/2, 2) ){
				$price = intval( $price ) - 1 + $new_price_fraction;
			} else {
				$price = intval( $price ) + $new_price_fraction;
			}

		} else {

			if ( $price_fraction >= $new_price_fraction + round(1/2, 2) ){
				$price = intval( $price ) + 1 + $new_price_fraction;
			} else {
				$price = intval( $price ) + $new_price_fraction;
			}

		}

		return $price;
	}

	protected function match_product_with_filters( $product_id ) {
		$matched = true;
		foreach ( $this->get_filters() as $filter_key => $filter ) {
			$matched = $this->check_product_suitability(
				$product_id,
				$filter['type'],
				$filter['value'],
				$filter['method']
			);

			if ( $matched ) {
				break;
			}
		}

		return $matched;
	}

	/**
	 * @return bool
	 */
	protected function check_product_filters() {
		$used_cart_items_by_attempt = array();
		$used_cart_items_by_filter  = array();
		static $last_iteration_product = null;
		static $last_iteration_result = null;

		foreach ( $this->get_filters() as $filter_key => $filter ) {
			$filter_qty = (int) $filter['qty'];
			$found_qty  = 0;

			foreach ( $this->items as $cart_item_key => $cart_item ) {
				$is_used_item = isset( $this->used_items_all_KEYS[$cart_item_key] );

				if ( ! $is_used_item ) {
					$hash = md5( json_encode( array($cart_item, $filter['type'], $filter['value'], $filter['method']) ) );
					if ( $hash == $last_iteration_product ) {
						$is_product_valid  = $last_iteration_result;
					} else {
						$is_product_valid      = $this->check_product_suitability(
							$cart_item['product_id'],
							$filter['type'],
							$filter['value'],
							$filter['method'],
							$cart_item
						);
						$last_iteration_product = $hash;
						$last_iteration_result = $is_product_valid;
					}

					if ( $is_product_valid ) {
						$used_cart_items_by_filter[ $filter_key ][] = $cart_item_key;
						$used_cart_items_by_attempt[]               = $cart_item_key;
						$this->used_items_all_KEYS[$cart_item_key] = 1;
						$found_qty ++;
					}
				}

				if ( $found_qty === $filter_qty ) {
					break;
				}
			}

			if ( $found_qty < $filter_qty ) {
				return false;
			}
		}

		$this->used_items_by_filters[ $this->attempt ] = $used_cart_items_by_filter;
		$this->used_items_by_attempt[ $this->attempt ] = $used_cart_items_by_attempt;

		$this->used_items_all = array_merge( $this->used_items_all, $used_cart_items_by_attempt );

		return true;
	}

	/**
	 * @return bool
	 */
	protected function apply_product_adjustment() {
		$ret = true;

		$adj_type = isset( $this->data['product_adjustments']['type'] ) ? $this->data['product_adjustments']['type'] : false;
		if ( 'total' === $adj_type ) {
			//TODO: implement product adjustment total mode +
			$ret = $this->apply_product_adjustment_total();
		} elseif ( 'split' === $adj_type ) {
			$ret = $this->apply_product_adjustment_split();
		}

		return $ret;
	}

	/**
	 * @return bool
	 */
	protected function apply_product_adjustment_total() {
		$used_item_keys = $this->get_keys_of_changeable_prices( $this->attempt );

		$adjustment = $this->data['product_adjustments']['total'];
		if ( empty( $adjustment ) || empty( $adjustment['type'] ) ) {
			return true;
		}

		$prices = array();
		foreach ( $used_item_keys as $cart_item_key ) {
			$prices[ $cart_item_key ] = $this->items[ $cart_item_key ]['price'];
		}
		$prices = $this->calculate_prices(
			$prices,
			$adjustment['type'],
			$adjustment['value']
		);

		foreach ( $used_item_keys as $cart_item_key ) {
			$cart_item = $this->items[ $cart_item_key ];

			$new_price = $prices[ $cart_item_key ];

			$new_price = $this->maybe_maximize_discount( $cart_item, $new_price );

			$cart_item['discounted_price'] = $new_price;
			$cart_item['price']            = $new_price;

			$this->items[ $cart_item_key ] = $cart_item;
		}

		return true;
	}

	protected function get_keys_of_changeable_prices( $attempt = null ) {
		if ( is_null( $attempt ) ) {
			$used_item_keys = $this->used_items_all;
		} else {
			$used_item_keys = $this->used_items_by_attempt[ $attempt ];
		}

		$ret = array();
		foreach ( $used_item_keys as $cart_item_key ) {
			if ( $this->is_changeable_price( $cart_item_key ) ) {
				$ret[] = $cart_item_key;
			}
		}

		return $ret;
	}

	protected function get_keys_of_changeable_prices_by_filter( $filter_id, $attempt ) {
		$ret = array();

		$by_filter = $this->used_items_by_filters[ $attempt ][ $filter_id ];
		foreach ( $by_filter as $cart_item_key ) {
			if ( $this->is_changeable_price( $cart_item_key ) ) {
				$ret[] = $cart_item_key;
			}
		}

		return $ret;
	}

	protected function is_changeable_price( $cart_item_key ) {
		return empty( $this->items[ $cart_item_key ]['price_readonly'] );
	}

	/**
	 * @return bool
	 */
	protected function apply_product_adjustment_split() {
		$adjustments = $this->data['product_adjustments']['split'];
		if ( empty( $adjustments ) ) {
			return true;
		}
		
		foreach ( $adjustments as $adj_key => $adjustment ) {
			if( empty($adjustment['type']) )
				continue;
			$filter_key = $adj_key;

			$used_cart_items = $this->get_keys_of_changeable_prices_by_filter( $filter_key, $this->attempt );
			foreach ( $used_cart_items as $cart_item_key ) {
				$cart_item = $this->items[ $cart_item_key ];

				$new_price = $this->calculate_single_price(
					$cart_item['price'],
					$adjustment['type'],
					$adjustment['value']
				);

				$new_price = $this->maybe_maximize_discount( $cart_item, $new_price );

				$cart_item['discounted_price'] = $new_price;
				$cart_item['price']            = $new_price;

				$this->items[ $cart_item_key ] = $cart_item;
			}
		}

		return true;
	}

	protected function apply_deal_adjustment() {
		$adjustments = isset( $this->data['get_products']['value'] ) ? $this->data['get_products']['value'] : false;
		if ( empty( $adjustments ) ) {
			return true;
		}

		foreach ( $adjustments as $deal_adjustment ) {
			$product_id = (int) reset( $deal_adjustment['value'] );
			$qty        = (int) $deal_adjustment['qty'];

			if ( ! isset( $this->add_products[ $product_id ] ) ) {
				$this->add_products[ $product_id ] = 0;
			}
			$this->add_products[ $product_id ] += $qty;
		}

		return true;
	}

	protected function calculate_qty_based_comparison_values() {
		$qty_based = isset($this->data['bulk_adjustments']['qty_based']) ? $this->data['bulk_adjustments']['qty_based'] : 'all';

		$qty_based_param   = array();
		$parent_product_id = 0;
		$product_id        = 0;
		if ( 'product' === $qty_based ) {
			foreach ( $this->items as $cart_item_key => $value ) {
				$parent_product_id 	 = wp_get_post_parent_id( $value['product_id'] );
				$product_id              = $value['product_id'];
				$key                     = $parent_product_id ? $parent_product_id : $product_id;
				$qty_based_param[ $key ] = isset( $qty_based_param[ $key ] ) ? $qty_based_param[ $key ] + 1 : 1;
			}
		} elseif ( 'variation' === $qty_based ) {
			foreach ( $this->items as $cart_item_key => $value ) {
				$key                     = $value['product_id'];
				$qty_based_param[ $key ] = isset( $qty_based_param[ $key ] ) ? $qty_based_param[ $key ] + 1 : 1;
			}
		}

		return $qty_based_param;
	}

	protected function maybe_calculate_comparison_value_based_on_qty(
		$comparison_value,
		$used_item_keys,
		$qty_based_comparison_values
	) {
		$qty_based = $this->data['bulk_adjustments']['qty_based'];

		if ( 'product' === $qty_based ) {
			foreach ( $used_item_keys as $cart_item_key ) {
				$product_id        = $this->items[ $cart_item_key ]['product_id'];
				$parent_product_id = wp_get_post_parent_id( $product_id );
				$key               = $parent_product_id ? $parent_product_id : $product_id;
				$comparison_value  = $qty_based_comparison_values[ $key ];
			}
		} elseif ( 'variation' === $qty_based ) {
			foreach ( $used_item_keys as $cart_item_key ) {
				$key              = $this->items[ $cart_item_key ]['product_id'];
				$comparison_value = $qty_based_comparison_values[ $key ];
			}
		}

		return $comparison_value;
	}

	protected function apply_bulk_adjustment() {
		if ( ! $this->has_bulk() ) {
			return true;
		}
		$ranges = $this->data['bulk_adjustments']['ranges'];

		$attempt_count = $this->attempt;

		$adj_type = $this->data['bulk_adjustments']['type'];
		$qty_based_comparison_values = $this->calculate_qty_based_comparison_values();

		for ( $i = 0; $i < $attempt_count; $i ++ ) {
			$used_item_keys  = $this->get_keys_of_changeable_prices( $i );
			$current_attempt = $i + 1;

			if ( 'tier' === $adj_type ) {
				$comparison_value = $current_attempt;
			} else {
				$comparison_value = $this->maybe_calculate_comparison_value_based_on_qty( $attempt_count,
					$used_item_keys,
					$qty_based_comparison_values );
			}

			foreach ( $ranges as $range ) {
				$more_than_from = empty( $range['from'] ) || $comparison_value >= $range['from'];
				$less_than_to   = empty( $range['to'] ) || $comparison_value <= $range['to'];
				if ( $more_than_from && $less_than_to ) {
					//TODO: implement calculation for bulk&tier adjustment +
					$prices = array();
					foreach ( $used_item_keys as $cart_item_key ) {
						$prices[ $cart_item_key ] = $this->items[ $cart_item_key ]['price'];
					}
					$prices = $this->calculate_prices(
						$prices,
						$this->data['bulk_adjustments']['discount_type'],
						$range['value']
					);

					foreach ( $used_item_keys as $cart_item_key ) {
						$cart_item = $this->items[ $cart_item_key ];

						$new_price = $prices[ $cart_item_key ];

						$new_price = $this->maybe_maximize_discount( $cart_item, $new_price );

						$cart_item['bulk_price'] = $new_price;
						$cart_item['price']      = $new_price;
						$this->items[ $cart_item_key ] = $cart_item;
					}
					break;
				}
			}
		}

		return true;
	}

	protected function apply_roles_discount() {
		if ( ! $this->has_roles_discount() ) {
			return true;
		}

		$this->roles_applied = false;

		$roles_rules = $this->data['role_discounts']['rows'];

		$attempt_count = $this->attempt;

		if ( ! ( $role = $this->get_current_user_role() ) ) {
			return true;
		}

		for ( $i = 0; $i < $attempt_count; $i ++ ) {
			$used_item_keys  = $this->get_keys_of_changeable_prices( $i );

			foreach ( $roles_rules as $roles_rule ) {
				$roles = $roles_rule['roles'];
				if ( ! in_array( $role, $roles ) ) {
					continue;
				}
				$this->roles_applied = true;
				$type = $roles_rule['discount_type'];
				$value = $roles_rule['discount_value'];

				$prices = array();
				foreach ( $used_item_keys as $cart_item_key ) {
					$prices[ $cart_item_key ] = $this->items[ $cart_item_key ]['price'];
				}
				$prices = $this->calculate_prices(
					$prices,
					$type,
					$value
				);

				foreach ( $used_item_keys as $cart_item_key ) {
					$cart_item = $this->items[ $cart_item_key ];

					$new_price = $prices[ $cart_item_key ];

					// bulk will maximize discount if enable in rule
					if ( ! $this->has_bulk() ) {
						$new_price = $this->maybe_maximize_discount( $cart_item, $new_price );
					}

					$cart_item['role_price'] = $new_price;
					$cart_item['price']      = $new_price;

					$this->items[ $cart_item_key ] = $cart_item;
				}
			}
		}


		return true;
	}

	private function maybe_maximize_discount( $cart_item, $new_price ) {
		if ( ! empty( $cart_item['price_mode'] ) && 'compare_discounted_and_sale' == $cart_item['price_mode'] ) {
			if ( ! empty( $cart_item['woo_in_on_sale'] ) && isset( $cart_item['woo_sale_price'] ) ) {
				$new_price = (float) $new_price > (float) $cart_item['woo_sale_price'] ? (float) $cart_item['woo_sale_price'] : (float) $new_price;
			}
		}

		return $new_price;
	}

	protected function apply_cart_adjustment() {
		foreach ( $this->cart_adjustments as $cart_adjustment ) {
			$cart_adjustment->apply_to_cart( $this->cart, $this->get_id() );
		}

		return true;
	}
}