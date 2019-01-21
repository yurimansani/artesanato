<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait WDP_Product_Filtering {
	/**
	 * @param int    $product_id
	 * @param string $operation_type
	 * @param array  $operation_values
	 * @param string $operation_method
	 * @param array  $cart_item
	 *
	 * @return bool
	 */
	protected function check_product_suitability( $product_id, $operation_type, $operation_values, $operation_method, $cart_item = array() ) {
		$operation_method = ! empty( $operation_method ) ? $operation_method : 'in_list';

		if ( 'products' === $operation_type && isset( $operation_values ) ) {
			return $this->compare_product_with_products( $product_id, $operation_values, $operation_method );
		} elseif ( 'product_categories' === $operation_type ) {
			return $this->compare_product_with_categories( $product_id, $operation_values, $operation_method );
		} elseif ( 'product_category_slug' === $operation_type ) {
			return $this->compare_product_with_category_slug( $product_id, $operation_values, $operation_method );
		} elseif ( 'product_tags' === $operation_type ) {
			return $this->compare_product_with_tags( $product_id, $operation_values, $operation_method );
		} elseif ( 'product_attributes' === $operation_type ) {
			return $this->compare_product_with_attributes( $product_id, $operation_values, $operation_method, $cart_item );
		} elseif ( 'product_sku' === $operation_type ) {
			return $this->compare_product_with_sku( $product_id, $operation_values, $operation_method );
		} elseif ( 'product_custom_fields' === $operation_type ) {
			return $this->compare_product_with_custom_fields( $product_id, $operation_values, $operation_method );
		}

		return false;
	}
	
	//TODO: add caching here ?
	protected function get_cached_wc_product( $product_id ) {
		if ( empty( $cached_products ) ) {
			static $cached_products = array();
		}

		// do not increase
		$depth = 3;

		if ( isset( $cached_products[ $product_id ] ) ) {
			$product = $cached_products[ $product_id ];
		} else {
			$product = wc_get_product( $product_id );
			if ( count( $cached_products ) >= $depth ) {
				reset($cached_products);
				unset($cached_products[key($cached_products)]);
			}
			$cached_products[ $product_id ] = $product;
		}

		return $product;
	}
	
	protected function get_main_product( $product_id ) {
		$product = $this->get_cached_wc_product( $product_id );
		if ( $product->is_type( 'variation' ) )
			$product = $this->get_cached_wc_product( $product->get_parent_id() );
		return $product;
	}

	protected function compare_product_with_products( $product_id, $operation_values, $operation_method ) {
		$result = false;
		$product_parent = $this->get_main_product( $product_id );

		if ( 'in_list' === $operation_method ) {
			$result = ( in_array( $product_id, $operation_values ) OR in_array( $product_parent->get_id(), $operation_values ) );
		} elseif ( 'not_in_list' === $operation_method ) {
			$result = ! ( in_array( $product_id, $operation_values ) OR in_array( $product_parent->get_id(), $operation_values ) );
		} elseif ( 'any' === $operation_method ) {
			$result = true;
		}

		return $result;
	}

	protected function compare_product_with_categories( $product_id, $operation_values, $operation_method ) {
		$product = $this->get_main_product( $product_id );
		$categories = $product->get_category_ids();

		$is_product_in_category = count( array_intersect( $categories, $operation_values ) ) > 0;

		if ( 'in_list' === $operation_method ) {
			return $is_product_in_category;
		} elseif ( 'not_in_list' === $operation_method ) {
			return ! $is_product_in_category;
		}

		return false;
	}

	protected function compare_product_with_category_slug( $product_id, $operation_values, $operation_method ) {
		$product = $this->get_main_product( $product_id );
		$category_slugs = array_map(function($category_id) {
			$term = get_term( $category_id, 'product_cat' );
			return $term ? $term->slug : '';
		}, $product->get_category_ids());

		$is_product_in_category = count( array_intersect( $category_slugs, $operation_values ) ) > 0;

		if ( 'in_list' === $operation_method ) {
			return $is_product_in_category;
		} elseif ( 'not_in_list' === $operation_method ) {
			return ! $is_product_in_category;
		}

		return false;
	}

	protected function compare_product_with_tags( $product_id, $operation_values, $operation_method ) {
		//TODO: product filter by tag +
		$product = $this->get_main_product( $product_id );
		$tag_ids = $product->get_tag_ids();

		$is_product_has_tag = count( array_intersect( $tag_ids, $operation_values ) ) > 0;

		if ( 'in_list' === $operation_method ) {
			return $is_product_has_tag;
		} elseif ( 'not_in_list' === $operation_method ) {
			return ! $is_product_has_tag;
		}

		return false;
	}

	protected function compare_product_with_attributes( $product_id, $operation_values, $operation_method, $cart_item ) {
		//TODO: product filter by attribute
		$product = $this->get_cached_wc_product( $product_id ); // use variation attributes?
		$attrs   = $product->get_attributes();

		$attr_ids = array();
		if ( $product->is_type( 'variation' ) ) {
			if ( count( array_filter($attrs) ) < count( $attrs ) ){
				if ( isset($cart_item['variation']) ) {
					$attrs = array();
					foreach ( $cart_item['variation'] as $attribute_name => $value ) {
						$attrs[ str_replace( 'attribute_', '', $attribute_name ) ] = $value;
					}
				}
			}

			foreach ( $attrs as $taxonomy => $value ) {
				if ( $value ) {
					// The WP_Term object
					$term_obj = get_term_by( 'slug', $value, $taxonomy );
					if ( ! is_wp_error( $term_obj ) && $term_obj && $term_obj->name ) {
						$attr_ids = array_merge($attr_ids, (array)($term_obj->term_id) );
					}
				} else {
					$product_variation = wc_get_product( $product->get_parent_id() );
					$attrs_variation   = $product_variation->get_attributes();
					foreach ( $attrs_variation as $attr ) {
						/** @var WC_Product_Attribute $attr */
						if ( $taxonomy == $attr->get_name() )
							$attr_ids = array_merge($attr_ids, $attr->get_options() );
					}
				 }
			}
		} else {
			foreach ( $attrs as $attr ) {
				/** @var WC_Product_Attribute $attr */
				$attr_ids = array_merge($attr_ids, $attr->get_options() );
			}
		}
		$attr_ids = array_unique( $attr_ids );

		$is_product_has_attrs = count( array_intersect( $attr_ids, $operation_values ) ) > 0;

		if ( 'in_list' === $operation_method ) {
			return $is_product_has_attrs;
		} elseif ( 'not_in_list' === $operation_method ) {
			return ! $is_product_has_attrs;
		}

		return false;
	}

	protected function compare_product_with_sku( $product_id, $operation_values, $operation_method ) {
		$result = false;
		$product = $this->get_cached_wc_product( $product_id ); // use variation sku!
		$product_sku = $product->get_sku();

		if ( 'in_list' === $operation_method ) {
			$result = ( in_array( $product_sku, $operation_values ) );
		} elseif ( 'not_in_list' === $operation_method ) {
			$result = ! ( in_array( $product_sku, $operation_values ) );
		} elseif ( 'any' === $operation_method ) {
			$result = true;
		}

		return $result;
	}

	protected function compare_product_with_custom_fields( $product_id, $operation_values, $operation_method ) {
		$product = $this->get_main_product( $product_id );
		$meta = get_post_meta( $product->get_id() );
		
		$customfields = array();
		foreach($meta as $key => $values) {
			foreach($values as $value )
				$customfields[] = "$key=$value";
		}	
		$is_product_has_fields = count( array_intersect( $customfields, $operation_values ) ) > 0;

		if ( 'in_list' === $operation_method ) {
			return $is_product_has_fields;
		} elseif ( 'not_in_list' === $operation_method ) {
			return ! $is_product_has_fields;
		}
	
	}
}