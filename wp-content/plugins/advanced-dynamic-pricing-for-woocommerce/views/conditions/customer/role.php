<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="wdp-column wdp-condition-subfield wdp-condition-field-method">
    <select name="rule[conditions][{c}][options][0]">
        <option value="in_list" selected><?php _e('in list', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
        <option value="not_in_list"><?php _e('not in list', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
    </select>
</div>

<div class="wdp-column wdp-condition-subfield wdp-condition-field-value">
    <select multiple
            data-list="user_roles"
            data-field="preloaded"
            data-placeholder="Select values"
            name="rule[conditions][{c}][options][1][]">
    </select>
</div>