<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<tr valign="top">
    <th scope="row" class="titledesc"><?php _e( 'Apply pricing rules to backend orders', 'advanced-dynamic-pricing-for-woocommerce' ) ?></th>
    <td class="forminp forminp-checkbox">
        <fieldset>
            <legend class="screen-reader-text">
                <span><?php _e( 'Apply  pricing rules to backend orders', 'advanced-dynamic-pricing-for-woocommerce' ) ?></span></legend>
            <label for="load_in_backend">
                <input <?php checked( $options['load_in_backend'] ) ?>
                        name="load_in_backend" id="load_in_backend" type="checkbox">
            </label>
        </fieldset>
    </td>
</tr>