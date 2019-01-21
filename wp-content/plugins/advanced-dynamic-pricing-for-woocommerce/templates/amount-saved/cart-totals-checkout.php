<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<tr class="order-total">
	<th><?php _e( 'Discount amount', 'woocommerce' ); ?></th>
	<td data-title="<?php esc_attr_e( 'Discount amount', 'woocommerce' ); ?>"><?php echo wc_price($amount_saved); ?></td>
</tr>