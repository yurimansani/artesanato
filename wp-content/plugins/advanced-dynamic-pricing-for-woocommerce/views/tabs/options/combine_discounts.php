<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<tr valign="top">
    <th scope="row" class="titledesc">
		<?php _e( 'Combine Multiple Fixed Discounts', 'advanced-dynamic-pricing-for-woocommerce' ) ?></th>
    <td class="forminp forminp-checkbox">
        <fieldset>
            <legend class="screen-reader-text">
                <span><?php _e( 'Combine Multiple Fixed Discounts', 'advanced-dynamic-pricing-for-woocommerce' ) ?></span>
            </legend>
            <label for="combine_discounts">
                <input <?php checked( $options['combine_discounts'] ) ?>
                        name="combine_discounts" id="combine_discounts" type="checkbox">
            </label>
        </fieldset>
    </td>
</tr>