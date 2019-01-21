<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WDP_Ajax {

	private $limit = null;

	public function __construct() {
		$options = WDP_Helpers::get_settings();
		$this->limit = $options['limit_results_in_autocomplete'];
	}

	public function ajax_requests() {
		$result = null;

		$method      = filter_input( INPUT_POST, 'method', FILTER_SANITIZE_STRING );
		$method_name = 'ajax_' . $method;

		if ( method_exists( $this, $method_name ) ) {
			$result = $this->$method_name();
		}

		wp_send_json_success( $result );
	}

	public function ajax_products() {
		$query = filter_input( INPUT_POST, 'query', FILTER_SANITIZE_STRING );
		$data_store = WC_Data_Store::load( 'product' );
		$ids        = $data_store->search_products( $query, '', true, false, $this->limit );
		return array_map( function ( $post_id ) {
			return array(
				'id'   => (string) $post_id,
				'text' => '#' . $post_id . ' ' . get_the_title( $post_id ),
			);
		}, array_filter( $ids ) );
	}

	public function ajax_product_sku() {
		global $wpdb;
		$query = filter_input( INPUT_POST, 'query', FILTER_SANITIZE_STRING );

		$results = $wpdb->get_results( "
			SELECT DISTINCT meta_value
			FROM $wpdb->postmeta
			WHERE meta_key = '_sku' AND meta_value  like '%$query%' LIMIT $this->limit
		" );

		return array_map(function ( $result ) {
			return array(
				'id'   => (string) $result->meta_value,
				'text' => 'SKU: ' . $result->meta_value,
			);
		}, $results);
	}

	public function ajax_product_category_slug() {
		$query = filter_input( INPUT_POST, 'query', FILTER_SANITIZE_STRING );
		$terms = get_terms( array( 'taxonomy' => 'product_cat', 'name__like' => $query, 'hide_empty' => false, 'number' => $this->limit ) );

		return array_map( function ( $term ) {
			return array(
				'id'   => $term->slug,
				'text' => __('Slug', 'advanced-dynamic-pricing-for-woocommerce') . ': ' . $term->slug,
			);
		}, $terms );
	}

	public function ajax_product_categories() {
		$query = filter_input( INPUT_POST, 'query', FILTER_SANITIZE_STRING );
		$terms = get_terms( array('taxonomy'=>'product_cat','name__like'=>$query, 'hide_empty' => false, 'number' => $this->limit ) );
		return array_map( function ( $term ) {
			return array(
				'id'   => (string) $term->term_id,
				'text' => $term->name,
			);
		}, $terms );
	}

	public function ajax_product_attributes() {
		global $wc_product_attributes, $wpdb;

		$query = filter_input( INPUT_POST, 'query', FILTER_SANITIZE_STRING );

		$taxonomies = array_map( function($item) {
			return "'$item'";
		}, array_keys( $wc_product_attributes ) );
		$taxonomies = implode( ', ', $taxonomies );

		$items = $wpdb->get_results( "
			SELECT $wpdb->terms.term_id, $wpdb->terms.name, taxonomy
			FROM $wpdb->term_taxonomy INNER JOIN $wpdb->terms USING (term_id)
			WHERE taxonomy in ($taxonomies)
			AND $wpdb->terms.name  like '%$query%' LIMIT $this->limit
		" );


		return array_map(function ( $term ) use ($wc_product_attributes) {
			$attribute = $wc_product_attributes[ $term->taxonomy ]->attribute_label;
			return array(
				'id'   => (string) $term->term_id,
				'text' => $attribute . ': ' . $term->name,
			);
		}, $items);
	}

	public function ajax_product_tags() {
		$query = filter_input( INPUT_POST, 'query', FILTER_SANITIZE_STRING );
		$terms = get_terms( array('taxonomy'=>'product_tag','name__like'=>$query, 'hide_empty' => false, 'number' => $this->limit ) );
		return array_map( function ( $term ) {
			return array(
				'id'   => (string) $term->term_id,
				'text' => $term->name,
			);
		}, $terms );
	}
	
	public function ajax_product_custom_fields() {
		global $wpdb;
		
		$query = filter_input( INPUT_POST, 'query', FILTER_SANITIZE_STRING );
		$like     = $wpdb->esc_like( $query );
		
		$wp_fields = $wpdb->get_col( "SELECT DISTINCT CONCAT(fields.meta_key,'=',fields.meta_value) FROM {$wpdb->postmeta} AS fields 
										JOIN {$wpdb->posts} AS products ON products.ID = fields.post_id
										WHERE products.post_type IN ('product','product_variation') AND CONCAT(fields.meta_key,'=',fields.meta_value) LIKE '%{$like}%' ORDER BY meta_key LIMIT $this->limit");
		
		return array_map( function ( $custom_field ) {
			return array(
				'id'   => $custom_field,
				'text' => $custom_field,
			);
		}, $wp_fields );
	}

	public function ajax_coupons() {
		$posts_raw = get_posts( array(
			'posts_per_page'    => $this->limit,
			'post_type'         => 'shop_coupon',
			'post_status'       => array('publish'),
			'fields'            => 'ids',
		) );

		$items = array_map( function ( $post_id ) {
			$code = get_the_title($post_id);
			return array(
				'id'    => $code,
				'text'  => $code
			);
		}, $posts_raw );

		$query = filter_input( INPUT_POST, 'query', FILTER_SANITIZE_STRING );
		if ( ! empty( $query ) ) {
			$items = array_filter($items, function( $item ) use ( $query ) {
				return strpos( $item[ 'text'], $query ) !== FALSE;
			});
		}

		return $items;
	}

	public function ajax_users_list() {
		$query = filter_input( INPUT_POST, 'query', FILTER_SANITIZE_STRING );
		$query = "*$query*";
		$users = get_users( array( 'fields'=>array('ID','user_nicename'), 'search' => $query ,'orderby'=>'user_nicename', 'number' => $this->limit) );
		return array_map( function ( $user) {
			return array(
				'id'   => (string) $user->ID,
				'text' => $user->user_nicename,
			);
		}, $users);
	}

	public function ajax_save_rule() {
		if ( ! isset( $_POST['rule'] ) ) return;
		$rule = $_POST['rule'];

		// prepare data to store each rule in db
		$data = array(
			'deleted'                  => 0,
			'enabled'                  => ( isset( $rule['enabled'] ) && $rule['enabled'] === 'on' ) ? 1 : 0,
			'exclusive'                => ( isset( $rule['exclusive'] ) && $rule['exclusive'] ) ? 1 : 0,
			'title'                    => sanitize_text_field( $rule['title'] ),
			'type'                     => sanitize_text_field( $rule['type'] ),
			'priority'                 => isset( $rule['priority'] ) ? (int) $rule['priority'] : 0,
			'options'                  => isset( $rule['options'] ) ? $rule['options'] : array(),
			'conditions'               => array_values( isset( $rule['conditions'] ) ? $rule['conditions'] : array() ),
			'filters'                  => isset( $rule['filters'] ) ? $rule['filters'] : array(),
			'limits'                   => array_values( isset( $rule['limits'] ) ? $rule['limits'] : array() ),
			'cart_adjustments'         => array_values( isset( $rule['cart_adjustments'] ) ? $rule['cart_adjustments'] : array() ),
			'product_adjustments'      => isset( $rule['product_adjustments'] ) ? $rule['product_adjustments'] : array(),
			'sortable_blocks_priority' => isset( $rule['sortable_blocks_priority'] ) ? $rule['sortable_blocks_priority'] : array(),
			'bulk_adjustments'         => isset( $rule['bulk_adjustments'] ) ? $rule['bulk_adjustments'] : array(),
			'role_discounts'           => isset( $rule['role_discounts'] ) ? $rule['role_discounts'] : array(),
			'get_products'             => isset( $rule['get_products'] ) ? $rule['get_products'] : array(),
			'additional'               => isset( $rule['additional'] ) ? $rule['additional'] : array(),
		);
		
		// arrays  saved as serialized values, must do "sanitize" recursive
		$arrays = array(
			'options',
			'conditions',
			'filters',
			'limits',
			'cart_adjustments',
			'product_adjustments',
			'sortable_blocks_priority',
			'bulk_adjustments',
			'role_discounts',
			'get_products',
			'additional',
		);
		foreach($arrays as $name)
			$data[$name] = serialize( $this->sanitize_array_text_fields( $data[$name] ) );

		// insert or update
		$id = WDP_Database::store_rule( $data, empty( $rule['id'] ) ? null : (int) $rule['id'] );
		wp_send_json_success( $id );
	}
	
	
	function sanitize_array_text_fields($array) {
		foreach ( $array as $key => &$value ) {
			if ( is_array( $value ) ) {
				$value = $this->sanitize_array_text_fields($value);
			}
			else {
				$value = sanitize_text_field( $value );
			}
		}
		return $array;
	}

	public function ajax_remove_rule() {
		$rule_id = (int) $_POST['rule_id']; 
		if( $rule_id )
			WDP_Database::mark_rule_as_deleted( $rule_id );
		wp_send_json_success();
	}

	public function ajax_reorder_rules() {
		$items = $_POST['items'];

		foreach ($items as $item) {
			$id = (int) $item[ 'id' ];
			if ( ! empty($id) ) {
				$data = array( 'priority' => (int) $item[ 'priority' ] );
				WDP_Database::store_rule( $data, $id );
			}
		}
	}

	public function ajax_subscriptions(){
		if ( get_option( 'woocommerce_subscriptions_is_active', false ) ) {

			$query      = filter_input( INPUT_POST, 'query', FILTER_SANITIZE_STRING );

			$posts = wc_get_products( array(
				'type'    => array( 'subscription', 'subscription_variation', 'variable-subscription' ),
				's' => $query,
				'limit' => $this->limit
			) );

			$result = array();
			foreach ( $posts as $post ) {
				$result[] = array(
					'id'   => $post->get_id(),
					'text' => $post->get_name(),
				);
			}

			return $result;

		} else {
			return null;
		}
	}
}