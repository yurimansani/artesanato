<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * @var string $conditions_templates
 * @var array $conditions_templates
 * @var array $conditions_titles
 * @var array $limits_templates
 * @var array $limits_titles
 * @var array $cart_titles
 * @var array $cart_templates
 */
?>

<div id="templates" style="display: none;">

    <?php
    foreach ($conditions_templates as $id => $condition_template):
        echo '<div id="' . $id . '_template">' . $condition_template . '</div>';
    endforeach;
    ?>

    <?php
    foreach ($limits_templates as $id => $limit_template):
        echo '<div id="' . $id . '_limit_template">' . $limit_template . '</div>';
    endforeach;
    ?>

	<?php
	foreach ($cart_templates as $id => $cart_template):
		echo '<div id="' . $id . '_cart_adjustment_template">' . $cart_template . '</div>';
	endforeach;
	?>

    <div id="rule_template">
        <?php include 'rule.php'; ?>
    </div>

    <div id="condition_row_template">
        <div class="wdp-row wdp-condition" data-index="{c}">
            <div class="wdp-column wdp-drag-icon">
                <span class="dashicons dashicons-menu"></span>
            </div>

            <div class="wdp-column wdp-condition-field-type">
                <select name="rule[conditions][{c}][type]">
                    <?php foreach ($conditions_titles as $group_name => $group): ?>
                    <optgroup label="<?php echo $group_name ?>">
                        <?php foreach ($group as $condition_id => $condition_title): ?>
                            <option value="<?php echo $condition_id ?>"><?php echo $condition_title ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="wdp-column wdp-column-subfields wdp-condition-field-sub"></div>

            <div class="wdp-column wdp-btn-remove wdp-condition-remove">
                <div class="wdp-btn-remove-handle">
                    <span class="dashicons dashicons-no-alt"></span>
                </div>
            </div>
        </div>
    </div>

    <div id="limit_row_template">
        <div class="wdp-row wdp-limit" data-index="{l}">
            <div class="wdp-column wdp-drag-icon">
                <span class="dashicons dashicons-menu"></span>
            </div>

            <div class="wdp-column wdp-limit-type">
                <select name="rule[limits][{l}][type]">
	                <?php foreach ($limits_titles as $group_name => $group): ?>
                        <optgroup label="<?php echo $group_name ?>">
			                <?php foreach ($group as $limit_id => $limit_title): ?>
                                <option value="<?php echo $limit_id ?>"><?php echo $limit_title ?></option>
			                <?php endforeach; ?>
                        </optgroup>
	                <?php endforeach; ?>
                </select>
            </div>
            <div class="wdp-column wdp-column-subfields wdp-limit-field-sub"></div>

            <div class="wdp-column wdp-btn-remove wdp-limit-remove">
                <div class="wdp-btn-remove-handle">
                    <span class="dashicons dashicons-no-alt"></span>
                </div>
            </div>
        </div>
    </div>

    <div id="cart_adjustment_row_template">
        <div class="wdp-row wdp-cart-adjustment" data-index="{ca}">
            <div class="wdp-column wdp-drag-icon">
                <span class="dashicons dashicons-menu"></span>
            </div>

            <div class="wdp-column wdp-cart-adjustment-type">
                <select name="rule[cart_adjustments][{ca}][type]">
					<?php foreach ($cart_titles as $group_name => $group): ?>
                        <optgroup label="<?php echo $group_name ?>">
							<?php foreach ($group as $cart_adj_id => $cart_adj_title): ?>
                                <option value="<?php echo $cart_adj_id ?>"><?php echo $cart_adj_title ?></option>
							<?php endforeach; ?>
                        </optgroup>
					<?php endforeach; ?>
                </select>
            </div>
            <div class="wdp-column wdp-column-subfields wdp-cart-adjustment-field-sub"></div>

            <div class="wdp-column wdp-btn-remove wdp-cart-adjustment-remove">
                <div class="wdp-btn-remove-handle">
                    <span class="dashicons dashicons-no-alt"></span>
                </div>
            </div>
        </div>
    </div>

    <div id="filter_item_qty_template">
        <div class="wdp-row wdp-filter-item" data-index="{f}">
            <div class="wdp-column wdp-condition-field-qty">
                <input type="number" placeholder="1" min="1" name="rule[{t}][{f}][qty]" value="1">
            </div>

            <div class="wdp-column wdp-filter-field-type">
                <select name="rule[{t}][{f}][type]" class="wdp-filter-type">
                    <option value="products" selected><?php _e('Products', 'advanced-dynamic-pricing-for-woocommerce'); ?></option>
                    <option value="product_categories"><?php _e('Product categories', 'advanced-dynamic-pricing-for-woocommerce'); ?></option>
                    <option value="product_category_slug"><?php _e('Product category slug', 'advanced-dynamic-pricing-for-woocommerce'); ?></option>
                    <option value="product_attributes"><?php _e('Product attributes', 'advanced-dynamic-pricing-for-woocommerce'); ?></option>
                    <option value="product_tags"><?php _e('Product tags', 'advanced-dynamic-pricing-for-woocommerce'); ?></option>
                    <option value="product_sku"><?php _e('Product SKU', 'advanced-dynamic-pricing-for-woocommerce'); ?></option>
                    <option value="product_custom_fields"><?php _e('Product custom fields', 'advanced-dynamic-pricing-for-woocommerce'); ?></option>
                </select>
            </div>

            <div class="wdp-column wdp-column-subfields wdp-condition-field-sub"></div>

            <div class="wdp-column wdp-btn-remove wdp_filter_remove">
                <div class="wdp-btn-remove-handle">
                    <span class="dashicons dashicons-no-alt"></span>
                </div>
            </div>
        </div>
    </div>

    <div id="filter_products_template">
        <div class="wdp-column wdp-filter-field-method">
            <select name="rule[{t}][{f}][method]">
                <option value="in_list" selected><?php _e('in list', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
                <option value="not_in_list"><?php _e('not in list', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            </select>
        </div>

        <div class="wdp-column wdp-condition-field-value">
            <div>
                <select multiple
                    data-list="products"
                    data-field="autocomplete"
                    data-placeholder="Select values"
                    name="rule[{t}][{f}][value][]">
                </select>
            </div>
        </div>
    </div>

    <div id="filter_product_tags_template">
        <div class="wdp-column wdp-filter-field-method">
            <select name="rule[{t}][{f}][method]">
                <option value="in_list" selected><?php _e('in list', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
                <option value="not_in_list"><?php _e('not in list', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            </select>
        </div>

        <div class="wdp-column wdp-condition-field-value">
            <div>
                <select multiple
                    data-list="product_tags"
                    data-field="autocomplete"
                    data-placeholder="Select values"
                    name="rule[{t}][{f}][value][]">
                </select>
            </div>
        </div>
    </div>

    <div id="filter_product_categories_template">
        <div class="wdp-column wdp-filter-field-method">
            <select name="rule[{t}][{f}][method]">
                <option value="in_list" selected><?php _e('in list', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
                <option value="not_in_list"><?php _e('not in list', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            </select>
        </div>

        <div class="wdp-column wdp-condition-field-value">
            <div>
                <select multiple
                    data-list="product_categories"
                    data-field="autocomplete"
                    data-placeholder="Select values"
                    name="rule[{t}][{f}][value][]">
                </select>
            </div>
        </div>
    </div>

    <div id="filter_product_category_slug_template">
        <div class="wdp-column wdp-filter-field-method">
            <select name="rule[{t}][{f}][method]">
                <option value="in_list" selected><?php _e('in list', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
                <option value="not_in_list"><?php _e('not in list', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            </select>
        </div>

        <div class="wdp-column wdp-condition-field-value">
            <div>
                <select multiple
                        data-list="product_category_slug"
                        data-field="autocomplete"
                        data-placeholder="Select values"
                        name="rule[{t}][{f}][value][]">
                </select>
            </div>
        </div>
    </div>

    <div id="filter_product_attributes_template">
        <div class="wdp-column wdp-filter-field-method">
            <select name="rule[{t}][{f}][method]">
                <option value="in_list" selected><?php _e('in list', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
                <option value="not_in_list"><?php _e('not in list', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            </select>
        </div>

        <div class="wdp-column wdp-condition-field-value">
            <select multiple
                data-list="product_attributes"
                data-field="autocomplete"
                data-placeholder="Select values"
                name="rule[{t}][{f}][value][]">
            </select>
        </div>
    </div>

    <div id="filter_product_sku_template">
        <div class="wdp-column wdp-filter-field-method">
            <select name="rule[{t}][{f}][method]">
                <option value="in_list" selected><?php _e('in list', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
                <option value="not_in_list"><?php _e('not in list', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            </select>
        </div>

        <div class="wdp-column wdp-condition-field-value">
            <select multiple
                    data-list="product_sku"
                    data-field="autocomplete"
                    data-placeholder="Select values"
                    name="rule[{t}][{f}][value][]">
            </select>
        </div>
    </div>
    
    <div id="filter_product_custom_fields_template">
        <div class="wdp-column wdp-filter-field-method">
            <select name="rule[{t}][{f}][method]">
                <option value="in_list" selected><?php _e('in list', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
                <option value="not_in_list"><?php _e('not in list', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            </select>
        </div>

        <div class="wdp-column wdp-condition-field-value">
            <select multiple
                data-list="product_custom_fields"
                data-field="autocomplete"
                data-placeholder="Select values"
                name="rule[{t}][{f}][value][]">
            </select>
        </div>
    </div>

    <div id="adjustment_split_row_template">
        <div class="wdp-row adjustment-split" data-index="{adj}">
            <div class="wdp-column">
                <select name="rule[product_adjustments][split][{adj}][type]" class="adjustment-split-type">
                    <option value="discount__amount">
						<?php _e( 'Fixed discount', 'advanced-dynamic-pricing-for-woocommerce' ) ?></option>
                    <option value="discount__percentage">
						<?php _e( 'Percentage discount', 'advanced-dynamic-pricing-for-woocommerce' ) ?></option>
                    <option value="price__fixed">
						<?php _e( 'Fixed unit price', 'advanced-dynamic-pricing-for-woocommerce' ) ?></option>
                </select>
            </div>

            <div class="wdp-column">
                <input name="rule[product_adjustments][split][{adj}][value]"
                       class="adjustment-split-value" type="number" placeholder="0.00" min="0" step="any">
            </div>
        </div>
    </div>

    <div id="adjustment_bulk_template">
        <div class="wdp-row wdp-range" data-index="{b}">
            <div class="wdp-column wdp-drag-icon">
                <span class="dashicons dashicons-menu"></span>
            </div>

            <div class="wdp-column">
                <input name="rule[bulk_adjustments][ranges][{b}][from]"
                    class="adjustment-from" type="number" placeholder="qty from" min="0" step="any">
            </div>

            <div class="wdp-column">
                <input name="rule[bulk_adjustments][ranges][{b}][to]"
                    class="adjustment-to" type="number" placeholder="qty to" min="0" step="any">
            </div>

            <div class="wdp-column">
                <input name="rule[bulk_adjustments][ranges][{b}][value]"
                    class="adjustment-value" type="number" placeholder="0.00" min="0">
            </div>

            <div class="wdp-btn-remove wdp-range-remove">
                <div class="wdp-btn-remove-handle"><span class="dashicons dashicons-no-alt"></span></div>
            </div>
        </div>
    </div>

    <div id="adjustment_deal_template">
		<div class="wdp-row wdp-filter-item" data-index="{f}">
			<div class="wdp-column wdp-drag-icon">
				<span class="dashicons dashicons-menu"></span>
			</div>

			<div class="wdp-column wdp-condition-field-qty">
				<input type="number" placeholder="qty" min="1" name="rule[get_products][value][{f}][qty]" value="1">
			</div>

			<div class="wdp-column wdp-column-subfields wdp-condition-field-sub"></div>

			<input type="hidden" name="rule[get_products][value][{f}][type]" value="products" class="wdp-filter-type">

			<div class="wdp-column wdp-btn-remove wdp_filter_remove">
				<div class="wdp-btn-remove-handle">
					<span class="dashicons dashicons-no-alt"></span>
				</div>
			</div>
		</div>        
    </div>

    <div id="filter_block_template">
        <div class="wdp-block wdp-filter-block">
            <label><?php _e('Products', 'advanced-dynamic-pricing-for-woocommerce'); ?></label>
            <div class="wdp-wrapper wdp_product_filter wdp-sortable">
                <div class="wdp-product-filter-empty"><?php _e('No filters', 'advanced-dynamic-pricing-for-woocommerce') ?></div>
            </div>
        </div>

        <div class="wdp-add-condition">
            <button type="button" class="button add-product-filter"><?php _e('Add Product Filter', 'advanced-dynamic-pricing-for-woocommerce'); ?></button>
        </div>
    </div>

    <div id="role_discount_row_template">
        <div class="wdp-row wdp-role-discount" data-index="{indx}">
            <div class="wdp-column wdp-drag-icon">
                <span class="dashicons dashicons-menu"></span>
            </div>

            <div class="wdp-column">
                <select multiple
                        data-list="user_roles"
                        data-field="preloaded"
                        data-placeholder="Select values"
                        name="rule[role_discounts][rows][{indx}][roles][]"
                        class="role-discount wdp-role-discount-value"
                        data-field-name="roles">
                </select>
            </div>
            <div class="wdp-column">
                <select name="rule[role_discounts][rows][{indx}][discount_type]" class="role-discount-type wdp-role-discount-value" data-field-name="discount_type" >
                    <option value="discount__amount">
						<?php _e( 'Fixed discount', 'advanced-dynamic-pricing-for-woocommerce' ) ?></option>
                    <option value="discount__percentage">
						<?php _e( 'Percentage discount', 'advanced-dynamic-pricing-for-woocommerce' ) ?></option>
                    <option value="price__fixed">
						<?php _e( 'Fixed unit price', 'advanced-dynamic-pricing-for-woocommerce' ) ?></option>
                </select>
            </div>

            <div class="wdp-column">
                <input name="rule[role_discounts][rows][{indx}][discount_value]" data-field-name="discount_value"
                       class="role-discount-value wdp-role-discount-value" type="number" placeholder="0.00" min="0" step="any">
            </div>

            <div class="wdp-column wdp-btn-remove wdp_role_discount_remove">
                <div class="wdp-btn-remove-handle">
                    <span class="dashicons dashicons-no-alt"></span>
                </div>
            </div>
        </div>
    </div>

</div>