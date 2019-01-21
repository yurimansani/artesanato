<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * @var string $tab
 */
?>

<form class="postbox closed" data-index="{r}">
    <input type="hidden" name="action" value="wdp_ajax">
    <input type="hidden" name="method" value="save_rule">
    <input type="hidden" name="rule[priority]" value="{p}" class="rule-priority" />
    <input type="hidden" value="" name="rule[id]" class="rule-id">
    <input type="hidden" name="rule[type]" value="{type}" class="rule-type">
    <input type="hidden" value="<?php echo ($tab === 'exclusive') ? 1 : 0 ?>" name="rule[exclusive]">

    <button type="button" class="handlediv" aria-expanded="false">
        <span class="screen-reader-text"><?php _e( 'Expand', 'advanced-dynamic-pricing-for-woocommerce' ) ?></span>
        <span class="toggle-indicator" aria-hidden="true"
              title="<?php _e( 'Expand', 'advanced-dynamic-pricing-for-woocommerce' ) ?>"></span>
    </button>

    <div class="wdp-actions">
        <button type="button" class="button-link wdp_copy_rule">
            <span class="screen-reader-text"><?php _e( 'Clone', 'advanced-dynamic-pricing-for-woocommerce' ) ?>
                <span data-wdp-title></span></span>
            <span class="dashicons dashicons-admin-page"
                  title="<?php _e( 'Clone', 'advanced-dynamic-pricing-for-woocommerce' ) ?>"></span>
        </button>
        <button type="button" class="button-link wdp_remove_rule">
            <span class="screen-reader-text"><?php _e( 'Delete', 'advanced-dynamic-pricing-for-woocommerce' ) ?>
                <span data-wdp-title></span></span>
            <span class="dashicons dashicons-no-alt"
                  title="<?php _e( 'Delete', 'advanced-dynamic-pricing-for-woocommerce' ) ?>"></span>
        </button>
        <div class="rule-type-bage"></div>
    </div>

    <h2 class="hndle ui-sortable-handle">
        <div class="wdp-column wdp-field-enabled">
            <select name="rule[enabled]" data-role="flipswitch" data-mini="true">
                <option value="off">Off</option>
                <option value="on" selected>On</option>
            </select>
        </div>
        <span data-wdp-title></span>&nbsp;</h2>
    <!-- <div style="clear: both;"></div> -->
    <div class="inside">
        <div class="wdp-row wdp-options">
            <div class="wdp-row wdp-column wdp-field-title">
                <label><?php _e('Title', 'advanced-dynamic-pricing-for-woocommerce'); ?></label>
                <input class="wdp-column wdp-title" type="text" name="rule[title]">
            </div>

            <div class="wdp-row wdp-column wdp-repeat">
                <label><?php _e( 'Can be applied:', 'advanced-dynamic-pricing-for-woocommerce' ) ?>
                    <select name="rule[options][repeat]">
                        <option value="-1"><?php _e( 'Unlimited', 'advanced-dynamic-pricing-for-woocommerce' ) ?></option>
                        <option value="1"><?php _e( 'Once', 'advanced-dynamic-pricing-for-woocommerce' ) ?></option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10">10</option>
                    </select>
                </label>
            </div>

            <div class="wdp-row wdp-column wdp-apply-to">
                <label><?php _e( 'Apply at first to:', 'advanced-dynamic-pricing-for-woocommerce' ) ?>
                    <select name="rule[options][apply_to]">
                        <option value="expensive"><?php _e( 'Expensive products', 'advanced-dynamic-pricing-for-woocommerce' ) ?></option>
                        <option value="cheap"><?php _e( 'Cheap products', 'advanced-dynamic-pricing-for-woocommerce' ) ?></option>
                    </select>
                </label>
            </div>
        </div>

        <div class="wdp-block wdp-filter-block" style="display: none;">
            <label><?php _e('Filter by', 'advanced-dynamic-pricing-for-woocommerce'); ?></label>
            <div class="wdp-wrapper wdp_product_filter">
                <div class="wdp-product-filter-container"></div>
                <div class="wdp-block wdp-add-condition">
                    <button type="button" class="button add-product-filter"><?php _e('Add product filter', 'advanced-dynamic-pricing-for-woocommerce'); ?></button>
                </div>
            </div>
        </div>

        <div class="wdp-block wdp-product-adjustments" style="display: none;">
            <label><?php _e('Product discounts', 'advanced-dynamic-pricing-for-woocommerce'); ?></label>
            <div class="wdp-wrapper">
                <div class="wdp-row">
                    <div class="wdp-column">
                        <label>
                            <input type="radio" name="rule[product_adjustments][type]"
                                   class="adjustment-mode adjustment-mode-total"
                                   data-readonly="1"
                                   value="total"/><?php _e( 'Total', 'advanced-dynamic-pricing-for-woocommerce' ) ?>
                        </label>
                        <label>
                            <input type="radio" name="rule[product_adjustments][type]"
                                   class="adjustment-mode adjustment-mode-split"
                                   data-readonly="1"
                                   value="split"
                                   disabled
                            /><?php _e( 'Split', 'advanced-dynamic-pricing-for-woocommerce' ) ?>
                        </label>
                    </div>

                    <div class="wdp-column wdp-btn-remove wdp_product_adjustment_remove">
                        <div class="wdp-btn-remove-handle">
                            <span class="dashicons dashicons-no-alt"></span>
                        </div>
                    </div>
                </div>

                <div class="wdp-row" data-show-if="total">
                    <div class="wdp-column">
                        <select name="rule[product_adjustments][total][type]" class="adjustment-total-type">
                            <option value="discount__amount"><?php _e('Fixed discount', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
                            <option value="discount__percentage"><?php _e('Percentage discount', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
                            <option value="price__fixed"><?php _e('Fixed unit price', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
                        </select>
                    </div>

                    <div class="wdp-column">
                        <input name="rule[product_adjustments][total][value]" class="adjustment-total-value" type="number" placeholder="0.00" min="0" step="any">
                    </div>
                </div>

                <div class="wdp-product-adjustments-split-container" data-show-if="split"></div>

                <div class="wdp-product-adjustments-options">
                    <div class="wdp-column">
                        <label><?php _e( 'Max discount sum:', 'advanced-dynamic-pricing-for-woocommerce' ) ?>
                            <input name="rule[product_adjustments][max_discount_sum]" type="number"
                                   class="product-adjustments-max-discount" placeholder="0.00" min="0" step="any"/>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="wdp-block wdp-cart-adjustments wdp-sortable" style="display: none;">
            <label><?php _e('Cart adjustments', 'advanced-dynamic-pricing-for-woocommerce'); ?></label>
            <div class="wdp-wrapper">
                <div class="wdp-cart-adjustments-container"></div>
                <div class="wdp-block add-cart-adjustment">
                    <button type="button" class="button"><?php _e('Add cart adjustment', 'advanced-dynamic-pricing-for-woocommerce'); ?></button>
                </div>
            </div>
        </div>

        <div class="wdp-sortable-blocks" style="">
            <!--            data-readonly="1" to prevent purge by "flushInputs"-->
            <div class="wdp-block wdp-role-discounts wdp-sortable-block" style="display: none;">
                <input data-readonly="1" type="hidden" class="priority_block_name" name="rule[sortable_blocks_priority][]" value="roles">
                <div class="wdp-column wdp-drag-handle">
                    <span class="dashicons dashicons-menu"></span>
                </div>
                <div>
                    <label><?php _e('Role discounts', 'advanced-dynamic-pricing-for-woocommerce'); ?></label>
                    <div class="wdp-wrapper">
                        <div class="wdp-role-discounts-container"></div>
                        <div class="wdp-block">
                            <button type="button" class="button add-role-discount"><?php _e('Add role discount', 'advanced-dynamic-pricing-for-woocommerce'); ?></button>
                        </div>
                        <label class="dont-apply-bulk-if-roles-matched-check">
                            <input type="checkbox" name="rule[role_discounts][dont_apply_bulk_if_roles_matched]" value="1">
                            <?php _e('Skip bulk rules if role rule was applied', 'advanced-dynamic-pricing-for-woocommerce'); ?>
                        </label>

                    </div>
                </div>
            </div>

            <div class="wdp-block wdp-bulk-adjustments wdp-sortable-block" style="display: none;">
                <input data-readonly="1" type="hidden" class="priority_block_name" name="rule[sortable_blocks_priority][]" value="bulk-adjustments">
                <div class="wdp-column wdp-drag-handle">
                    <span class="dashicons dashicons-menu"></span>
                </div>
                <div>
                <label><?php _e('Bulk mode', 'advanced-dynamic-pricing-for-woocommerce'); ?></label>
                <div class="wdp-wrapper">
                    <div class="wdp-row">
                        <div class="wdp-column">
                            <select name="rule[bulk_adjustments][type]" class="bulk-adjustment-type">
                                <option value="bulk"><?php _e('Bulk', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
                                <option value="tier"><?php _e('Tier', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
                            </select>
                        </div>

                        <div class="wdp-column">
                            <select name="rule[bulk_adjustments][qty_based]" class="bulk-qty_based-type">
                                <option value="all"><?php _e('Qty based on all matched products', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
                                <option value="product"><?php _e('Qty based on product', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
                                <option value="variation"><?php _e('Qty based on variation', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
                            </select>
                        </div>

                        <div class="wdp-column">
                            <select name="rule[bulk_adjustments][discount_type]" class="bulk-discount-type">
                                <option value="discount__amount"><?php _e('Fixed discount', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
                                <option value="discount__percentage"><?php _e('Percentage discount', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
                                <option value="price__fixed"><?php _e('Fixed unit price', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
                            </select>
                        </div>

                        <div class="wdp-column wdp-btn-remove wdp_bulk_adjustment_remove">
                            <div class="wdp-btn-remove-handle">
                                <span class="dashicons dashicons-no-alt"></span>
                            </div>
                        </div>
                    </div>

                    <div class="wdp-adjustment-ranges">
                        <div class="wdp-ranges wdp-sortable">
                            <div class="wdp-ranges-empty"><?php _e('No ranges', 'advanced-dynamic-pricing-for-woocommerce') ?></div>
                        </div>

                        <div class="wdp-add-condition">
                            <button type="button" class="button add-range"><?php _e('Add range', 'advanced-dynamic-pricing-for-woocommerce'); ?></button>
                        </div>
                    </div>

                    <div class="wdp-bulk-adjustment-options">
                        <div class="wdp-column">
                            <label>
							    <?php _e( 'Bulk table message', 'advanced-dynamic-pricing-for-woocommerce' ) ?>
                                <input type="text" name="rule[bulk_adjustments][table_message]" class="bulk-table-message"
                                       placeholder="<?php _e( 'If you leave this field empty, we will show default bulk description', 'advanced-dynamic-pricing-for-woocommerce' ) ?>"/>
                            </label>
                        </div>
                    </div>
                </div>
                </div>
            </div>

        </div>

        <div class="wdp-block wdp-get-products-block wdp-get-products-options" style="display: none;">
            <div class="wdp-row wdp-get-products-repeat">
                <label><?php _e( 'Free products. Can be applied ', 'advanced-dynamic-pricing-for-woocommerce' ); ?></label>
                <div class="wdp-column">
                    <select name="rule[get_products][repeat]">
                        <option value="-1"><?php _e( 'Unlimited', 'advanced-dynamic-pricing-for-woocommerce' ) ?></option>
                        <option value="0"><?php _e( 'No one', 'advanced-dynamic-pricing-for-woocommerce' ) ?></option>
                        <option value="1"><?php _e( 'Once', 'advanced-dynamic-pricing-for-woocommerce' ) ?></option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10">10</option>
                    </select>
                </div>
            </div>

            <div class="wdp-wrapper">
                <div class="wdp-get-products"></div>

                <div class="wdp-add-condition">
                    <button type="button" class="button add-filter-get-product"><?php _e('Add product', 'advanced-dynamic-pricing-for-woocommerce'); ?></button>
                </div>
            </div>
        </div>

        <div class="wdp-block wdp-conditions wdp-sortable" style="display: none;">
            <label><?php _e('Conditions', 'advanced-dynamic-pricing-for-woocommerce'); ?></label>
            <div class="wdp-wrapper">
                <div class="wdp-conditions-relationship">
                    <label><?php _e('Conditions relationship', 'advanced-dynamic-pricing-for-woocommerce'); ?></label>
                    <label><input type="radio" name="rule[additional][conditions_relationship]" value="and" checked><?php _e('AND', 'advanced-dynamic-pricing-for-woocommerce'); ?></label>
                    <label><input type="radio" name="rule[additional][conditions_relationship]" value="or"><?php _e('OR', 'advanced-dynamic-pricing-for-woocommerce'); ?></label>
                </div>
                <div class="wdp-conditions-container"></div>
                <div class="wdp-block add-condition">
                    <button type="button" class="button"><?php _e('Add condition', 'advanced-dynamic-pricing-for-woocommerce'); ?></button>
                </div>
                <?php if( !WDP_Loader::is_pro_version() ): ?>
                <a href="https://algolplus.com/plugins/downloads/advanced-dynamic-pricing-woocommerce-pro/" target=_blank><?php _e( 'Need more conditions?', 'advanced-dynamic-pricing-for-woocommerce' ) ?></a>
                <?php endif; ?>
            </div>
        </div>

        <div class="wdp-block wdp-limits wdp-sortable" style="display: none;">
            <label><?php _e('Limits', 'advanced-dynamic-pricing-for-woocommerce'); ?></label>
            <div class="wdp-wrapper">
                <div class="wdp-limits-container"></div>
                <div class="wdp-block add-limit">
                    <button type="button" class="button"><?php _e('Add limit', 'advanced-dynamic-pricing-for-woocommerce'); ?></button>
                </div>
            </div>
        </div>

        <div class="wdp-add-condition">
            <button type="button" class="button wdp-btn-add-product-filter"><?php _e('Product filters', 'advanced-dynamic-pricing-for-woocommerce'); ?></button>
            <button type="button" class="button wdp-btn-add-product-adjustment"><?php _e('Product rules', 'advanced-dynamic-pricing-for-woocommerce'); ?></button>
            <button type="button" class="button wdp-btn-add-role-discount"><?php _e('Role discounts', 'advanced-dynamic-pricing-for-woocommerce'); ?></button>
            <button type="button" class="button wdp-btn-add-bulk"><?php _e('Bulk rules', 'advanced-dynamic-pricing-for-woocommerce'); ?></button>
            <button type="button" class="button wdp-btn-add-cart-adjustment"><?php _e('Cart rules', 'advanced-dynamic-pricing-for-woocommerce'); ?></button>
            <button type="button" class="button wdp-btn-add-getproduct"><?php _e('Free products', 'advanced-dynamic-pricing-for-woocommerce'); ?></button>
            <button type="button" class="button wdp-btn-add-condition"><?php _e('Cart conditions', 'advanced-dynamic-pricing-for-woocommerce'); ?></button>
            <button type="button" class="button wdp-btn-add-limit"><?php _e('Limits', 'advanced-dynamic-pricing-for-woocommerce'); ?></button>
            <button type="submit" class="button button-primary save-rule"><?php _e('Save changes', 'advanced-dynamic-pricing-for-woocommerce') ?></button>
        </div>
    </div>
</form>