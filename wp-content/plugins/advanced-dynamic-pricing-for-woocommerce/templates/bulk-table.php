<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * @var array $data
 * @var array $dependencies
 */
if ( 'bulk' === $data['type'] ) {
	$deal_title = apply_filters( "wdp_{$table_type}_bulk_table_header_for_bulk_title", __( 'Bulk deal', 'advanced-dynamic-pricing-for-woocommerce' ));
} elseif ( 'tier' === $data['type'] ) {
	$deal_title = apply_filters( "wdp_{$table_type}_bulk_table_header_for_tier_title", __( 'Tier deal', 'advanced-dynamic-pricing-for-woocommerce' ));
} else {
	return;
}

$options = WDP_Helpers::get_settings();
$show_discounted_price_in_bulk_table = apply_filters("wdp_show_discounted_price_in_{$table_type}_bulk_table", false);
$show_product_discount_in_bulk_table = apply_filters("wdp_show_product_discount_in_{$table_type}_bulk_table", true);
$show_footer_in_bulk_table = apply_filters("wdp_show_footer_in_{$table_type}_bulk_table", true);
$discounted_price_title = __( 'Discounted price', 'advanced-dynamic-pricing-for-woocommerce' );
if ( in_array( $data['discount'], array( 'discount__amount', 'discount__percentage' ) ) ) {
	$amount_title = __( 'Discount', 'advanced-dynamic-pricing-for-woocommerce' );
	$amount_title = apply_filters( "wdp_{$table_type}_bulk_table_discount_price_title", $amount_title );
} elseif ( 'price__fixed' === $data['discount'] ) {
	$amount_title = __( 'Price', 'advanced-dynamic-pricing-for-woocommerce' );
	$amount_title = apply_filters( "wdp_{$table_type}_bulk_table_fixed_price_title", $amount_title );
	$show_product_discount_in_bulk_table = $show_discounted_price_in_bulk_table;
	$show_discounted_price_in_bulk_table = false;
} else {
	return;
}
if ( 'category' == $table_type ) {
	$show_discounted_price_in_bulk_table = false;
}
$qty_title = apply_filters( "wdp_{$table_type}_bulk_table_qty_title", __( 'Quantity', 'advanced-dynamic-pricing-for-woocommerce' ) );
$discounted_price_title = apply_filters( "wdp_{$table_type}_bulk_table_discounted_price_title", $discounted_price_title );


?>
<div class='clear'></div>

<div class="bulk_table">
<div class="wdp_pricing_table_caption"><?php echo $deal_title; ?></div>
<table class="wdp_pricing_table">
    <thead>
    <tr>
        <td class="wdp_bulk_table_qty_column"><?php echo $qty_title; ?></td>
	    <?php if ( $show_product_discount_in_bulk_table ): ?>
            <td><?php echo $amount_title ?></td>
	    <?php endif; ?>
	    <?php if ( $show_discounted_price_in_bulk_table ): ?>
            <td><?php echo $discounted_price_title ?></td>
	    <?php endif; ?>
    </tr>
    </thead>

    <tbody>
	<?php foreach ( $data['ranges'] as $line ): ?>
        <tr>
            <td>
	            <?php echo ( $line['from'] == $line['to'] ) ? $line['from'] : ( $line['from'] . '-' . $line['to'] ); ?>
            </td>
	        <?php if ( $show_product_discount_in_bulk_table ): ?>
                <td>
                    <?php
                    if ( in_array( $data['discount'], array( 'price__fixed', 'discount__amount' ) ) ) {
                        echo wc_price( $line['value'] );
                    } elseif ( 'discount__percentage' === $data['discount'] ) {
                        echo "{$line['value']}%";
                    }
                    ?>
                </td>
	        <?php endif; ?>
	        <?php if ( $show_discounted_price_in_bulk_table ): ?>
                <td>
	                <?php echo isset( $line['discounted_price'] ) ? $line['discounted_price'] : ""; ?>
                </td>
            <?php endif; ?>
        </tr>
	<?php endforeach; ?>
    </tbody>
</table>
	<?php if ( $show_footer_in_bulk_table ): ?>
        <span class="wdp_pricing_table_footer">
            <?php if ( ! empty( $data['table_message'] ) ): ?>
                <p class=""><?php _e( $data['table_message'], 'advanced-dynamic-pricing-for-woocommerce' ); ?></p>
            <?php elseif ( $show_footer_in_bulk_table ): ?>
                <div>
                    <?php _e( 'Bulk pricing will be applied to package:',
	                    'advanced-dynamic-pricing-for-woocommerce' ); ?>
                </div>
                <ul>
                    <?php foreach ( $dependencies as $dependency ): ?>
                        <li>
                            <?php
                            if ( 'any' === $dependency['method'] ) {
	                            echo sprintf(
		                            '<a href="%s">%s</a>',
		                            get_permalink( wc_get_page_id( 'shop' ) ),
		                            sprintf( __( '%d of any product(s)', 'advanced-dynamic-pricing-for-woocommerce' ),
			                            $dependency['qty'] )
	                            );
	                            continue;
                            }

                            $items = array();
                            foreach ( $dependency['values'] as $i => $value ) {
	                            $name = $dependency['titles'][ $i ];
	                            $link = $dependency['links'][ $i ];
	                            if ( ! empty( $link ) ) {
		                            $items[] = "<a href='{$link}'>{$name}</a>";
	                            } else {
		                            $items[] = "'{$name}'";
	                            }
                            }
                            $items = implode( ', ', $items );
                            if ( 'products' === $dependency['type'] ) {
	                            if ( 'in_list' === $dependency['method'] ) {
		                            echo sprintf(
			                            __( '%d product(s) from list: %s', 'advanced-dynamic-pricing-for-woocommerce' ),
			                            $dependency['qty'],
			                            $items
		                            );
	                            } elseif ( 'not_in_list' === $dependency['method'] ) {
		                            echo sprintf(
			                            __( '%d product(s) not from list: %s',
				                            'advanced-dynamic-pricing-for-woocommerce' ),
			                            $dependency['qty'],
			                            $items
		                            );
	                            }
                            } elseif ( 'product_categories' === $dependency['type'] ) {
	                            if ( 'in_list' === $dependency['method'] ) {
		                            echo sprintf(
			                            __( '%d product(s) from categories: %s',
				                            'advanced-dynamic-pricing-for-woocommerce' ),
			                            $dependency['qty'],
			                            $items
		                            );
	                            } elseif ( 'not_in_list' === $dependency['method'] ) {
		                            echo sprintf(
			                            __( '%d product(s) not from categories: %s',
				                            'advanced-dynamic-pricing-for-woocommerce' ),
			                            $dependency['qty'],
			                            $items
		                            );
	                            }
                            } elseif ( 'product_tags' === $dependency['type'] ) {
	                            if ( 'in_list' === $dependency['method'] ) {
		                            echo sprintf(
			                            __( '%d product(s) with tags from list: %s',
				                            'advanced-dynamic-pricing-for-woocommerce' ),
			                            $dependency['qty'],
			                            $items
		                            );
	                            } elseif ( 'not_in_list' === $dependency['method'] ) {
		                            echo sprintf(
			                            __( '%d product(s) with tags not from list: %s',
				                            'advanced-dynamic-pricing-for-woocommerce' ),
			                            $dependency['qty'],
			                            $items
		                            );
	                            }
                            } elseif ( 'product_attributes' === $dependency['type'] ) {
	                            if ( 'in_list' === $dependency['method'] ) {
		                            echo sprintf(
			                            __( '%d product(s) with attributes from list: %s',
				                            'advanced-dynamic-pricing-for-woocommerce' ),
			                            $dependency['qty'],
			                            $items
		                            );
	                            } elseif ( 'not_in_list' === $dependency['method'] ) {
		                            echo sprintf(
			                            __( '%d product(s) with attributes not from list: %s',
				                            'advanced-dynamic-pricing-for-woocommerce' ),
			                            $dependency['qty'],
			                            $items
		                            );
	                            }
                            }
                            ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </span>
	<?php endif; ?>
    <br>
</div>