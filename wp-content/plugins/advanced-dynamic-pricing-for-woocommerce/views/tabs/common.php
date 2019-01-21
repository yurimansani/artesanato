<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

    <div id="poststuff">

        <p>
            <label>
                <input type="checkbox" class="hide-disabled-rules">
				<?php _e( 'Hide inactive rules', 'advanced-dynamic-pricing-for-woocommerce' ) ?>
            </label>
        </p>

        <div id="post-body" class="metabox-holder">
            <div id="postbox-container-2" class="postbox-container">
                <div id="normal-sortables" class="meta-box-sortables ui-sortable">
                    <div id="rules-container" class="sortables-container group-container loading"></div>
                    <p id="no-rules" class="loading"><?php _e( 'No common rules defined', 'advanced-dynamic-pricing-for-woocommerce' ) ?></p>
                    <p>
                        <button class="button add-rule loading">
							<?php _e( 'Add rule', 'advanced-dynamic-pricing-for-woocommerce' ) ?></button>
                    </p>
                    <div id="progress_div" style="">
                        <div id="container"><span class="spinner is-active" style="float:none;"></span></div>
                    </div>

                </div>
            </div>

            <div style="clear: both;"></div>
        </div>
    </div>

<?php include WC_ADP_PLUGIN_PATH.'/views/rules/templates.php'; ?>