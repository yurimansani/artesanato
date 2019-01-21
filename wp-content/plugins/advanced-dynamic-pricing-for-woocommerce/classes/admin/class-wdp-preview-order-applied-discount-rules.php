<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WDP_Preview_Order_Applied_Discount_Rules {

	public static function output( $rules ) {

	$html = '<style> .wdp-aplied-rules, .wdp-aplied-rules td:first-child { width: 100%; } </style>';

	$html.= '<div class="wc-order-preview-table-wrapper">';
	$html .= '<table class="wc-order-preview-table">';
	$html .= '<tr><td><strong class="ui-sortable-handle">' . __( 'Applied discounts', 'advanced-dynamic-pricing-for-woocommerce' ) . '</strong></td></tr>';
	foreach ($rules as $row){
		$html .='<tr>'.
		        '<td><a href=' . self::rule_url( $row ) . '>' . $row->title . '</a></td>'.
		        '<td>';
		$amount = floatval( $row->amount + $row->extra + $row->gifted_amount );
		$html.= empty( $amount ) ? '-' : wc_price( $amount );
		$html.= '</td>'.
		        '</tr>';
	}
	$html.= '</table>';
    $html.= '</div>';
    return $html;
    }

    public static function add_data($export_data, $order){
	    $rules = WDP_Database::get_applied_rules_for_order( $export_data['order_number'] );
	    if ( !empty( $rules ) ) {
		    $export_data['rules_rendered'] = WDP_Preview_Order_Applied_Discount_Rules::output($rules);
	    }
	    return $export_data;
    }

    public static function render(){
	    echo '{{{ data.rules_rendered }}}';
    }

    private static function rule_url( $row ) {
        $is_rule_exclusive = isset( $row->exclusive ) && $row->exclusive;
        $tab = $is_rule_exclusive ? 'exclusive' : 'common';

        return add_query_arg(
            array(
                'rule_id' => $row->id,
                'tab'     => $tab,
            ),
	        admin_url( 'admin.php?page=wdp_settings' )
        );
    }
}