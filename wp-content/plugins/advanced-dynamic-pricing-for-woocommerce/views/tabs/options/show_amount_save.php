<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<tr valign="top">
    <th scope="row" class="titledesc">
		<?php _e( 'Show amount saved in', 'advanced-dynamic-pricing-for-woocommerce' ) ?></th>
    <td class="forminp forminp-checkbox">
        <fieldset>
            <div>
                <label for="is_show_amount_saved_in_checkout_cart">
                    <input <?php checked( $options['is_show_amount_saved_in_checkout_cart'] ) ?> name="is_show_amount_saved_in_checkout_cart" id="is_show_amount_saved_in_checkout_cart" type="checkbox">
					<?php _e( 'Checkout', 'advanced-dynamic-pricing-for-woocommerce' ) ?>
                </label>
                <label for="is_show_amount_saved_in_cart">
                    <input <?php checked( $options['is_show_amount_saved_in_cart'] ) ?> name="is_show_amount_saved_in_cart" id="is_show_amount_saved_in_cart" type="checkbox">
					<?php _e( 'Cart', 'advanced-dynamic-pricing-for-woocommerce' ) ?>
                </label>
                <label for="is_show_amount_saved_in_mini_cart">
                    <input <?php checked( $options['is_show_amount_saved_in_mini_cart'] ) ?> name="is_show_amount_saved_in_mini_cart" id="is_show_amount_saved_in_mini_cart" type="checkbox">
					<?php _e( 'Mini cart', 'advanced-dynamic-pricing-for-woocommerce' ) ?>
                </label>
            </div>
        </fieldset>
    </td>
</tr>