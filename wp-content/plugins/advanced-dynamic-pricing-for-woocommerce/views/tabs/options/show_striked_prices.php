<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<tr valign="top">
    <th scope="row" class="titledesc"><?php _e('Show Striked Prices in the Cart', 'advanced-dynamic-pricing-for-woocommerce') ?></th>
    <td class="forminp forminp-checkbox">
        <fieldset>
            <legend class="screen-reader-text"><span><?php _e('Show Striked Prices in the Cart', 'advanced-dynamic-pricing-for-woocommerce') ?></span></legend>
            <label for="show_striked_prices">
                <input <?php checked( $options['show_striked_prices'] ); ?>
                        name="show_striked_prices" id="show_striked_prices" type="checkbox">
            </label>
        </fieldset>
    </td>
</tr>
