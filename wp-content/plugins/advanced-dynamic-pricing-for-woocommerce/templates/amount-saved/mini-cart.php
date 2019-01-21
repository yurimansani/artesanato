<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<li class="woocommerce-mini-cart-item" style="text-align: center">
    <strong><?php _e( 'Discount amount', 'woocommerce' ); ?>:</strong>
	<?php echo wc_price($amount_saved); ?>
</li>