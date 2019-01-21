<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<tr valign="top">
    <th scope="row" class="titledesc"><?php _e('Show On Sale badge if product price was modified', 'advanced-dynamic-pricing-for-woocommerce') ?></th>
    <td class="forminp forminp-checkbox">
        <fieldset>
            <legend class="screen-reader-text"><span><?php _e('Show On Sale badge if product price was modified', 'advanced-dynamic-pricing-for-woocommerce') ?></span></legend>
            <label for="show_onsale_badge">
                <input <?php checked( $options['show_onsale_badge'] ); ?>
                        name="show_onsale_badge" id="show_onsale_badge" type="checkbox">
            </label>
        </fieldset>
    </td>
</tr>
