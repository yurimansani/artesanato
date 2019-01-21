<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="wdp-column wdp-condition-subfield wdp-condition-field-qty">
    <input type="number" placeholder="qty" min="1" name="rule[conditions][{c}][options][0]" value="1">
</div>

<div class="wdp-column wdp-condition-subfield wdp-condition-field-method">
	<select name="rule[conditions][{c}][options][1]">
		<option value="in_list" selected><?php _e('in list', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
		<option value="not_in_list"><?php _e('not in list', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
	</select>
</div>

<div class="wdp-column wdp-condition-subfield wdp-condition-field-value">
	<div>
		<select multiple
		        data-list="product_sku"
		        data-field="autocomplete"
		        data-placeholder="Select values"
		        name="rule[conditions][{c}][options][2][]">
		</select>
	</div>
</div>
