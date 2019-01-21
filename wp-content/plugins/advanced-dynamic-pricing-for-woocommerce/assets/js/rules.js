/* global jQuery, wpc_postboxes, ajaxurl, wdp_data */
jQuery(document).ready(function ($) {

    // make rule blocks collapsable and sortable
    wpc_postboxes.add_postbox_toggles( $('#rules-container') );

    // update priority field on reorder
    wpc_postboxes._on_reorder = function() {
        // make aray of objects [ { id, priority }, ... ]
        var data = $('#rules-container .postbox').map(function(i, el) {
            $('.rule-priority', el).val( i );
            return {
                id: $('.rule-id', el).val(),
                priority: i,
            }
        }).toArray();

        $.post(
            ajaxurl,
            {
                action: 'wdp_ajax',
                method: 'reorder_rules',
                items: data
            },
            $.noop,
            'json'
        );
    };

    // load saved rules
    if (wdp_data.rules) {
	    var promises = [];
	    wdp_data.rules.forEach( function ( data ) {
		    promises.push( add_rule( data ) );
	    } );
	    Promise.all( promises ).then( function ( responses ) {
		    $( "#rules-container" ).removeClass( "loading" );
		    $( '#no-rules' ).removeClass( "loading" );
		    $( '.add-rule' ).removeClass( "loading" );
		    $( "#progress_div" ).hide();
	    } ).catch( function ( reason ) {
		    console.log( reason );
	    } );

        if ( wdp_data.selected_rule && wdp_data.selected_rule > 0 ) {
            var $rule = $('#rules-container .rule-id[value="' + wdp_data.selected_rule + '"]').closest('.postbox');
            $rule.removeClass('closed');
            $('html, body').animate({ scrollTop: $rule.position().top }, 500);
        }
    }

    // create new rule when click 'Add rule' button
    $('.add-rule').click(function (e) {
        e.preventDefault();
        add_rule();
    });

    /* Template functions */

    function add_rule(data) {
        window.wdpPreloadRule = true;
        var template_options = {
            c: 0,
            p: (data && data.priority) ? data.priority : get_last_priority(),
            type: (data && data.type) ? data.type : 'package',
        };

        // prepare template
        var rule_template = get_template('rule', template_options);
        var new_rule = $(rule_template);

        addEventHandlersToRule(new_rule);

        // add new rule to rules list
        $('#rules-container').append(new_rule);

        // for exists rule, apply data
        if (data) {
            setRuleData(new_rule, data);
        } else {
            new_rule.removeClass('closed');
            new_rule.addClass('dirty');
        }
        $('#no-rules').hide();
        window.wdpPreloadRule = false;
    }

    function addEventHandlersToRule(new_rule) {
        // on change rule title
        new_rule.find('.wdp-title').on('change input', function () {
            var $postbox = $(this).closest('.postbox');
            var value = $(this).val();
            $postbox.find('[data-wdp-title]').text(value);
        });

        // listeners for buttons
        new_rule.find('.wdp_remove_rule').on('click', function () {
            if (!confirm(wdp_data.labels.confirm_remove_rule)) return;

            var $rule = $(this).closest('.postbox');
            $rule.addClass('removing');

            var rule_id = $rule.find('.rule-id').val();
            if(!rule_id) {
                $rule.remove();
                return;
            }

            $.post(
                ajaxurl,
                {
                    action: 'wdp_ajax',
                    method: 'remove_rule',
                    rule_id: rule_id
                },
                function () {
                    $rule.remove();
                },
                'json'
            );
        });

        new_rule.find('.wdp_copy_rule').on('click', function () {
            var temp = new_rule.serialize();
            temp = deparam(temp);

            var cloned_data = temp.rule;
            cloned_data.id = '';
            cloned_data.priority = get_last_priority();

            add_rule(cloned_data);
        });

        // ** Buttons in rule

        // Add condition
        new_rule.find('.add-condition, .wdp-btn-add-condition').click(function () {
            add_condition($(this));
        });

        // Add limit
        new_rule.find('.add-limit, .wdp-btn-add-limit').click(function () {
            add_limit($(this));
        });

        // Add range (bulk)
        new_rule.find('.add-range').click(function () {
            add_range($(this));
        });

        // Add product filter
        new_rule.find('.add-product-filter, .wdp-btn-add-product-filter').click(function () {
            add_product_filter(new_rule.find('.wdp-filter-block'));
        });

        // Add product filter for 'Add product adjustment' block
        new_rule.find('.wdp-btn-add-product-adjustment').click(function () {
            add_product_adjustment(new_rule.find('.wdp-product-adjustments'));
        });

        // Add bulk adjustment
        new_rule.find('.wdp-btn-add-bulk').click(function () {
            add_bulk_adjustment(new_rule.find('.wdp-bulk-adjustments'));

	        // Hide or show all sortable handlers depends on role discounts visibility
	        if ( new_rule.find( ".wdp-role-discounts" ).is( ":hidden" ) ) {
		        new_rule.find( ".wdp-drag-handle" ).hide();
	        } else {
		        new_rule.find( ".wdp-drag-handle" ).show();
	        }

	        // Show that label with checkbox when role discount on first position
	        if (new_rule.find( ".wdp-sortable-blocks > div:first-child .dont-apply-bulk-if-roles-matched-check"  ).length) {
		        new_rule.find( '.dont-apply-bulk-if-roles-matched-check' ).show();
	        } else {
		        new_rule.find( '.dont-apply-bulk-if-roles-matched-check' ).hide();
	        }
        });

        // Add cart adjustment
        new_rule.find('.add-cart-adjustment, .wdp-btn-add-cart-adjustment').click(function () {
            add_cart_adjustment($(this));
        });

	    // Add cart role discount
	    new_rule.find('.add-role-discount, .wdp-btn-add-role-discount').click(function () {
		    add_role_discount($(this));


		    if ( new_rule.find( ".wdp-bulk-adjustments" ).is( ":hidden" ) ) {
			    // Hide or show all sortable handlers depends on bulk adjustments visibility
			    new_rule.find( ".wdp-drag-handle" ).hide();

			    new_rule.find( '.dont-apply-bulk-if-roles-matched-check' ).hide();
		    } else {
			    new_rule.find( ".wdp-drag-handle" ).show();

			    // Show that label with checkbox only when bulk adjustments is not empty and role discounts on first position
			    if (new_rule.find( ".wdp-sortable-blocks > div:first-child .dont-apply-bulk-if-roles-matched-check"  ).length) {
				    new_rule.find( '.dont-apply-bulk-if-roles-matched-check' ).show();
			    } else {
				    new_rule.find( '.dont-apply-bulk-if-roles-matched-check' ).hide();
			    }
		    }
	    });

        // Add product filter for 'Get products' block
        new_rule.find('.add-filter-get-product, .wdp-btn-add-getproduct').click(function () {
            add_get_products(new_rule.find('.wdp-get-products'));
        });

        // on save rule
        new_rule.find('.save-rule').click(function (e) {
            e.preventDefault();

            var $form = $(this).closest('form');

            $form.attr('disabled', true);

            $.post(
                ajaxurl,
                $form.serialize(),
                function (response) {
                    $form.attr('disabled', false);

                    var id = response.data;
                    $form.find('.rule-id').val(id);

                    $form.removeClass('dirty');
                },
                'json'
            );

            return false;
        });

        // init flipswitch
        new_rule.find('[data-role="flipswitch"]').flipswitch();

        // update title on some changes
        new_rule.find('.wdp-adjustments-repeat, .cart-adjustment-type').change(function () {
            update_rule_title(new_rule);
        });
        new_rule.find('.wdp-field-enabled select').change(function () {
            update_rule_title(new_rule);
            if (!window.wdpPreloadRule) {
                new_rule.find('.save-rule').click();
            }
        });
        new_rule.find('.wdp-field-enabled').click(function(event) {
            event.preventDefault();
            return false;
        });
        update_rule_title(new_rule);

        // make lists inside rule sortable
        make_sortable(new_rule.find('.wdp-sortable'));

	    new_rule.find( '.wdp-sortable-blocks' ).sortable( {
		    containment: 'parent',
		    items: '.wdp-sortable-block',
		    cursor: 'move',
		    axis: 'y',
		    opacity: 0.65,
		    handle: '.wdp-drag-handle',
		    update: function( event, ui ) {
			    new_rule.trigger( 'change' );

			    // Show that label with checkbox when role discount on first position
			    if (new_rule.find( ".wdp-sortable-blocks > div:first-child .dont-apply-bulk-if-roles-matched-check"  ).length) {
				    new_rule.find( '.dont-apply-bulk-if-roles-matched-check' ).show();
			    } else {
				    new_rule.find( '.dont-apply-bulk-if-roles-matched-check' ).hide();
			    }
            }
	    } );

        new_rule.on('change', function() {
            new_rule.addClass('dirty');
        });
    }

    function setRuleData(new_rule, data) {
        new_rule.find('.rule-id').val(data.id);

        new_rule.find('[data-wdp-title]').text(data.title);
        new_rule.find('.wdp-title').val(data.title);
        new_rule.find('[data-role="flipswitch"]').val(data.enabled);
        new_rule.find('[data-role="flipswitch"]').flipswitch('refresh');
        new_rule.removeClass('dirty');

	    if (data.sortable_blocks_priority && data.sortable_blocks_priority.length) {
		    var $sorted_blocks = new_rule.find('.wdp-sortable-blocks');
		    data.sortable_blocks_priority.forEach( function ( data_item ) {
			    $.each(new_rule.find( 'input.priority_block_name' ), function( el_index, el_item ) {
				    if ( data_item === $(el_item).val() ) {
					    $( el_item ).parent().appendTo( $sorted_blocks );
				    }
			    } );

		    } );

	    }

        if (data.options) {
            fill_options(new_rule.find('.wdp-options'), data.options);
        }

        if (data.filters) {
            var $wdp_product_filter = new_rule.find('.wdp-filter-block');
            $.each(data.filters, function (i, filter) {
                add_product_filter($wdp_product_filter, filter);
            });
        }

	    if ( data.additional.conditions_relationship ) {
		    var $radios = new_rule.find( '.wdp-conditions-relationship input:radio' );
		    $radios.filter( '[value=' + data.additional.conditions_relationship + ']' ).prop( 'checked', true );
	    }

        var $btn;
        if (data.conditions) {
            $btn = new_rule.find('.wdp-btn-add-condition');
            $.each(data.conditions, function (i, condition) {
                add_condition($btn, condition);
            });
        }

        if (data.limits) {
            $btn = new_rule.find('.wdp-btn-add-limit');
            $.each(data.limits, function (i, limit) {
                add_limit($btn, limit);
            });
        }

        if (data.product_adjustments && data.product_adjustments.type) {
            if ('total' === data.product_adjustments.type && data.product_adjustments['total']['type']) {
                add_product_adjustment(new_rule.find('.wdp-product-adjustments'), data.product_adjustments);
            }
            else if ('split' === data.product_adjustments.type && data.product_adjustments['split'][0]['type']) {
                add_product_adjustment(new_rule.find('.wdp-product-adjustments'), data.product_adjustments);
            }
        }

        if (data.cart_adjustments) {
            $btn = new_rule.find('.wdp-btn-add-cart-adjustment');
            $.each(data.cart_adjustments, function (i, cart_adjustment) {
                add_cart_adjustment($btn, cart_adjustment);
            });
        }

	    if ( data.role_discounts && data.role_discounts.rows ) {
		    $btn = new_rule.find( '.wdp-btn-add-role-discount' );
		    $.each( data.role_discounts.rows, function ( i, role_discount ) {
			    add_role_discount( $btn, role_discount );
		    } );

		    new_rule.find( '[name="rule[role_discounts][dont_apply_bulk_if_roles_matched]"]' ).attr( 'checked', false );
		    new_rule.find( '.dont-apply-bulk-if-roles-matched-check' ).hide();

		    // Hide role discount sortable handler if bulk adjustments is empty
		    if ( !( data.bulk_adjustments && data.bulk_adjustments.ranges ) ) {
		        new_rule.find(".wdp-role-discounts .wdp-drag-handle").hide();
            } else {
			    // Show that label with checkbox when role discount on first position
			    if (new_rule.find( ".wdp-sortable-blocks > div:first-child .dont-apply-bulk-if-roles-matched-check"  ).length) {
				    new_rule.find( '.dont-apply-bulk-if-roles-matched-check' ).show();
			    } else {
				    new_rule.find( '.dont-apply-bulk-if-roles-matched-check' ).hide();
			    }

			    if ( typeof data.role_discounts.dont_apply_bulk_if_roles_matched === "undefined" ) {
				    new_rule.find( '[name="rule[role_discounts][dont_apply_bulk_if_roles_matched]"]' ).attr( 'checked', true );
			    }
            }
	    }

        if (data.bulk_adjustments && data.bulk_adjustments.ranges) {
            add_bulk_adjustment(new_rule.find('.wdp-bulk-adjustments'), data.bulk_adjustments);

	        // Hide bulk adjustments sortable handler if role discounts is empty
	        if ( !( data.role_discounts && data.role_discounts.rows ) ) {
		        new_rule.find(".wdp-bulk-adjustments .wdp-drag-handle").hide();
	        }
        }

        if (data.get_products && data.get_products.value) {
            fill_get_products_options(new_rule.find('.wdp-get-products-block'), data.get_products);
            var $wdp_product_adjustments = new_rule.find('.wdp-get-products');
            $.each(data.get_products.value, function (i, filter) {
                add_get_products($wdp_product_adjustments, filter);
            });
        }
    }

    function fill_options($container, data) {
        if (data) {
            if (data.repeat) {
                $container.find('.wdp-repeat select').val(data.repeat);
            }
            if (data.apply_to) {
                $container.find('.wdp-apply-to select').val(data.apply_to);
            }
        }
    }

    function add_product_filter($container, data) {
        // hide message 'no rules' when add new filter
        $container.show();

        $container.closest('.postbox').find('.wdp-btn-add-product-filter').hide();

        var product_filter_index = get_new_product_filter_index($container);
        var template = get_template('filter_item_qty', {
            r: get_current_rule_index($container),
            f: product_filter_index,
            t: 'filters'
        });

        var $product_filter = $(template);
        var $product_filter_selector = $product_filter.find('.wdp-filter-type');

        // add filter into rule containter
        $container.find('.wdp-product-filter-container').append($product_filter);

        // load data for existing filter
        if (data) {
            if (data.type) $product_filter_selector.val(data.type);
            if (data.qty) $product_filter.find('.wdp-condition-field-qty input').val(data.qty);
        }

        // hook for remove filter
        $product_filter.find('.wdp_filter_remove').click(function () {
            var $product_filter_container = $(this).closest('.wdp-filter-block');
            $product_filter.closest('.postbox').find('.adjustment-split[data-index=' + $product_filter.attr('data-index') + ']').remove();
            $product_filter.remove();

            var filters_count = $product_filter_container.find('.wdp-filter-item').length;
            if (filters_count === 0) {
                $container.hide();
                $container.closest('.postbox').find('.wdp-btn-add-product-filter').show();
                $container.closest('.postbox').find('.adjustment-mode-total').attr('checked', 'checked').change();
                $container.closest('.postbox').find('.adjustment-mode-split').attr('disabled', 'disabled');
            }
        });

        // render controls for selected filter type
        $product_filter_selector.change(function () {
            update_product_filter_fields($(this));
        });
        update_product_filter_fields($product_filter_selector, data);

        $container.closest('.postbox').find('.adjustment-mode-split').attr('disabled', false);
        add_product_adjustment_split($container.closest('.wdp-filter-block'), product_filter_index);
    }

    function update_fields_qty_type($product_filter) {
        var type = $product_filter.find('.wdp-condition-field-qty-type select').val();
        if (type == 'any') {
            $product_filter.find('.wdp-condition-field-qty').hide();
            $product_filter.find('.wdp-condition-field-range').hide();
        } else if (type == 'qty') {
            $product_filter.find('.wdp-condition-field-qty').show();
            $product_filter.find('.wdp-condition-field-range').hide();
        } else if (type == 'range') {
            $product_filter.find('.wdp-condition-field-qty').hide();
            $product_filter.find('.wdp-condition-field-range').show();
        }
    }

    function update_product_filter_fields($el, data, option) {
        var $container = $el.closest('.wdp-filter-item');
        var type = $el.val();

        // prepare template for filter type
        var template = get_template('filter_' + type, {
            r: get_current_rule_index($el),
            f: get_current_product_filter_index($el),
            t: option || 'filters'
        });

        $container.find('.wdp-condition-field-sub').html(template);

        // load data for existing filter
        if (data) {
            if (data.method) {
                $container.find('.wdp-filter-field-method select').val(data.method);
            }

            if (data.value) {
                var html = '';
                $.each(data.value, function (i, id) {
                    var title = wdp_data.titles[data.type] && wdp_data.titles[data.type][id] ? wdp_data.titles[data.type][id] : id;
                    html += '<option selected value="' + id + '">' + title + '</option>';
                });
                $container.find('.wdp-condition-field-value select').append(html);
            }
        }

        make_select2_products($container.find('[data-field="autocomplete"]'));
    }

    function add_condition($el, data) {
        $el.closest('.postbox').find('.wdp-btn-add-condition').hide();

        var condition_template = get_template('condition_row', {
            r: get_current_rule_index($el),
            c: get_new_condition_index($el)
        });

        var $condition = $(condition_template);

        $condition.find('.wdp-condition-remove').click(function () {
            var $rule = $(this).closest('.postbox');
            $(this).closest('.wdp-condition').remove();

            var conditions_count = $rule.find('.wdp-conditions .wdp-condition').length;
            if (conditions_count === 0) {
                $rule.find('.wdp-conditions').hide();
                $el.closest('.postbox').find('.wdp-btn-add-condition').show();
            }
        });

        $el.closest('.postbox')
            .find('.wdp-conditions').show()
            .find('.wdp-conditions-container').append($condition);

        var $condition_type_selector = $condition.find('.wdp-condition-field-type select');
        if (data && data.type) {
            $condition_type_selector.val(data.type);
        }

        if (!$condition_type_selector.val()) {
            var new_val = $condition_type_selector.find('option').prop('value');
            $condition_type_selector.val(new_val);
        }

        var $condition_type_selector = $condition.find('.wdp-condition-field-type select');
        update_condition_fields($condition_type_selector, data);
        $condition_type_selector.change(function () {
            update_condition_fields($(this));
        });
    }

    function update_condition_fields($el, data) {
        var $container = $el.closest('.wdp-condition');
        var type = $el.val();

        var template = get_template(type, {
            r: get_current_rule_index($el),
            c: $container.data('index')
        });

        $container.find('.wdp-condition-field-sub').html(template);

        if (data && data.options) {

            var fields = $container.find('.wdp-condition-subfield');
            fields.each( function(index, field) {
                var value_field;

                value_field = $('select[multiple]', field);
                if (value_field.length) {
                    var titles = [], get_title;
                    if (value_field.data('field') === 'autocomplete') {
                        titles = wdp_data.titles[value_field.data('list')];
                        get_title = function(id) { return titles[id]; }
                    } else if (value_field.data('field') === 'preloaded') {
                        titles = wdp_data.lists[value_field.data('list')];
                        get_title = function(id) {
                            for (var i = 0; i < titles.length; i++) {
                                if (titles[i].id === parseInt(id)) return titles[i].text;
                            }
                            return id;
                        }
                    }

                    $.each(data.options[index], function (i, val) {
                        // value_field.find('[value="' + val + '"]').prop('selected', 'selected');
                        value_field.append('<option selected value="' + val + '">' + get_title(val) + '</option>');
                    });
                    return;
                }

                value_field = $('select', field);
                if (value_field.length) {
                    value_field.val( data.options[index] );
                    return;
                }

                value_field = $('input', field);
                if (value_field.length) {
                    value_field.val(data.options[index]);
                }
            });
        }

        $container.find('[data-field="date"]').removeClass('hasDatepicker').datepicker({dateFormat:"yy-mm-dd"});
        $container.find('[class="datetimepicker"]').datetimepicker();
        make_select2_tags($container.find('[data-field="tags"]'));

        make_select2_products($container.find('[data-field="autocomplete"]'));
        make_select2_preloaded($container.find('[data-field="preloaded"]'));

        $container.find('.wdp-condition-field-method-coupon select').change( function() {
            var disabled = [ 'at_least_one_any', 'none_at_all' ].indexOf( $( this ).val() ) >= 0;
            $container.find('.wdp-condition-field-value-coupon select').prop('disabled', disabled);
        } );
		
        $container.find('.wdp-condition-field-method-coupon select').each( function() {
            var disabled = [ 'at_least_one_any', 'none_at_all' ].indexOf( $( this ).val() ) >= 0;
            $container.find('.wdp-condition-field-value-coupon select').prop('disabled', disabled);
        } );

	    $container.find( '.wdp-condition-field-method select' ).change( function () {
		    var enable_last = 'in_range' === $( this ).val();
		    $container.find( '.wdp-condition-field-value-last' ).toggle( enable_last );
	    } );

	    $container.find( '.wdp-condition-field-method select' ).each( function () {
		    var enable_last = 'in_range' === $( this ).val();
		    $container.find( '.wdp-condition-field-value-last' ).toggle( enable_last );
	    } );
    }

    function add_limit($el, data) {
        $el.closest('.postbox').find('.wdp-btn-add-limit').hide();

        var template = get_template('limit_row', {
            l: get_new_limit_index($el)
        });

        var $limit = $(template);

        $el.closest('.postbox')
            .find('.wdp-limits').show()
            .find('.wdp-limits-container').append($limit);

        if (data) {
            $limit.find('.wdp-limit-type select').val(data.type);
        }

        $limit.find('.wdp-limit-remove').click(function () {
            var $rule = $(this).closest('.postbox');
            $(this).closest('.wdp-limit').remove();

            var limits_count = $rule.find('.wdp-limits .wdp-limit').length;
            if (limits_count === 0) {
                $rule.find('.wdp-limits').hide();
                $el.closest('.postbox').find('.wdp-btn-add-limit').show();
            }
        });

        var $limit_type_selector = $limit.find('.wdp-limit-type select');
        update_limit_fields($limit_type_selector, data);
        $limit_type_selector.change(function () {
            update_limit_fields($(this));
        });
    }

    function update_limit_fields($el, data) {
        var $container = $el.closest('.wdp-limit');
        var type = $el.val();

        var template = get_template(type + '_limit', {
            l: $container.data('index'),
        });

        $container.find('.wdp-limit-field-sub').html(template);

        if (data && data.options) {
            $container.find('.wdp-limit-value input').val( data.options );
        }
    }

    function add_range($el, data) {
        var $postbox = $el.closest('.postbox');

        var template = get_template('adjustment_bulk', {
            r: get_current_rule_index($el),
            b: get_new_range_index($el)
        });

        $postbox.find('.wdp-ranges-empty').hide();

        var $range = $(template);

        $postbox.find('.wdp-ranges').append($range);

        if (data) {
            $range.find('.adjustment-from').val(data.from);
            $range.find('.adjustment-to').val(data.to);
            $range.find('.adjustment-value').val(data.value);
        }

        $range.find('.wdp-range-remove').click(function () {
            var $rule = $(this).closest('.postbox');
            $(this).closest('.wdp-range').remove();

            var ranges_count = $rule.find('.wdp-ranges .wdp-range').length;
            if (ranges_count === 0) {
                $postbox.find('.wdp-ranges-empty').show();
            }
        });
    }

    function fill_get_products_options($container, data) {
        if (data) {
            if (data.repeat) {
                $container.find('.wdp-get-products-repeat select').val(data.repeat);
            }
        }
    }

    function add_get_products($container, data) {
        $container.closest('.wdp-get-products-block').show();
        $container.closest('.postbox').find('.wdp-btn-add-getproduct').hide();

        var template = get_template('adjustment_deal', {
            r: get_current_rule_index($container),
            f: get_new_product_filter_index($container)
        });

        var $product_filter = $(template);
        var $product_filter_selector = $product_filter.find('.wdp-filter-type');

        $container.append($product_filter);

        if (data) {
            if (data['qty']) $product_filter.find('.wdp-condition-field-qty input').val(data['qty']);
        }

        var $rule = $container.closest('.postbox');

        $product_filter.find('.wdp_filter_remove').click(function () {
            var $product_filter_container = $(this).closest('.wdp-get-products');
            $product_filter.remove();

            var filters_count = $product_filter_container.find('.wdp-filter-item').length;
            if (filters_count === 0) {
                $container.closest('.wdp-get-products-block').hide();
                $container.closest('.postbox').find('.wdp-btn-add-getproduct').show();
            }
        });

        update_product_filter_fields($product_filter_selector, data, 'get_products][value');
    }

    function add_product_adjustment($container, data) {
        $container.show();

        var $rule = $container.closest('.postbox');
        $rule.find('.wdp-btn-add-product-adjustment').hide();

        $rule.find('.wdp_product_adjustment_remove').click(function () {
            $rule.find('.wdp-btn-add-product-adjustment').show();
            $container.hide();
            flushInputs($container);
        });

        var type;
        if (data) {
            type = data.type;
            $container.find('.adjustment-mode-' + type).attr('checked', 'checked');

            if (data.total) {
                $container.find('.adjustment-total-type').val(data.total.type);
                $container.find('.adjustment-total-value').val(data.total.value);
            }

            $container.find('.adjustment-split').each(function(index) {
                if (data.split && data.split[index]) {
                    fill_product_adjustment_split($(this), data.split[index]);
                }
                else {
                    fill_product_adjustment_split($(this));
                }
            });
            if (data['max_discount_sum']) {
                $container.find('.product-adjustments-max-discount').val(data['max_discount_sum']);
            }
        } else {
            type = 'total';
            $container.find('.adjustment-mode-total').attr('checked', 'checked');
            $container.find('.adjustment-total-type').find('option:first-child').prop('selected', 'selected');

            $container.find('.adjustment-split').each(function() {
                fill_product_adjustment_split($(this));
            });
        }

        $container.find('.adjustment-mode').change(function () {
            updateElementsVisisibilyInRowForElementValue($(this).val(), $(this).closest('.wdp-product-adjustments'));
        });
        updateElementsVisisibilyInRowForElementValue(type, $container);
    }

    function add_product_adjustment_split($container, adj_index, data) {
        var template = get_template('adjustment_split_row', {
            adj: adj_index
        });

        var $split_adjustment = $(template);

        $container.closest('.postbox')
            .find('.wdp-product-adjustments-split-container').append($split_adjustment);

        fill_product_adjustment_split($split_adjustment, data);
    }

    function fill_product_adjustment_split($split_adjustment, data) {
        if (data) {
            $split_adjustment.find('.adjustment-split-type').val(data.type);
            $split_adjustment.find('.adjustment-split-value').val(data.value);
        }
        else {
            $split_adjustment.find('.adjustment-split-type').find('option:first-child').prop('selected', 'selected');
        }
    }

    function add_bulk_adjustment($container, data) {
        $container.show();

        var $rule = $container.closest('.postbox');
        $rule.find('.wdp-btn-add-bulk').hide();

        $rule.find('.wdp_bulk_adjustment_remove').click(function () {
            $rule.find('.wdp-btn-add-bulk').show();
            $container.hide();
            flushInputs($container);
            $container.find('.wdp-range').remove();
            $container.find('.wdp-ranges-empty').show();

	        // Unconditionally hide all sortable handlers
	        $rule.find(".wdp-drag-handle").hide();
	        // Hide label with checkbox in role discount
	        $rule.find( '.dont-apply-bulk-if-roles-matched-check' ).hide();
        });

        if (data) {
            $container.find('.bulk-adjustment-type').val(data.type);

            if (data.discount_type) {
                $container.find('.bulk-discount-type').val(data.discount_type);
            }

	        if (data.qty_based) {
		        $container.find('.bulk-qty_based-type').val(data.qty_based);
	        }

            if (data.ranges) {
                var $range_button = $container.find('.add-range');
                $.each(data.ranges, function (index, item) {
                    add_range($range_button, item);
                });
            }

            if (data.table_message) {
                $container.find('.bulk-table-message').val(data.table_message);
            }
        } else {
            $rule.find('.bulk-adjustment-type').find('option:first-child').prop("selected", "selected");
	        $rule.find('.bulk-qty_based-type').find('option:first-child').prop("selected", "selected");
            $rule.find('.bulk-discount-type').find('option:first-child').prop("selected", "selected");
        }
    }

    function add_cart_adjustment($el, data) {
        $el.closest('.postbox').find('.wdp-btn-add-cart-adjustment').hide();

        var template = get_template('cart_adjustment_row', {
            ca: get_new_cart_adjustment_index($el)
        });

        var $cart_adjustment = $(template);

        $el.closest('.postbox')
            .find('.wdp-cart-adjustments').show()
            .find('.wdp-cart-adjustments-container').append($cart_adjustment);

        if (data) {
            $cart_adjustment.find('.wdp-cart-adjustment-type select').val(data.type);
        }

        $cart_adjustment.find('.wdp-cart-adjustment-remove').click(function () {
            var $rule = $(this).closest('.postbox');
            $(this).closest('.wdp-cart-adjustment').remove();

            var adjs_count = $rule.find('.wdp-cart-adjustments .wdp-cart-adjustment').length;
            if (adjs_count === 0) {
                $rule.find('.wdp-cart-adjustments').hide();
                $el.closest('.postbox').find('.wdp-btn-add-cart-adjustment').show();
            }
        });

        var $adj_type_selector = $cart_adjustment.find('.wdp-cart-adjustment-type select');
        update_cart_adjustment_fields($adj_type_selector, data);
        $adj_type_selector.change(function () {
            update_cart_adjustment_fields($(this));
        });
    }

	function add_role_discount( $el, data ) {
		$el.closest( '.postbox' ).find( '.wdp-btn-add-role-discount' ).hide();

		var template = get_template( 'role_discount_row', {
			indx: get_new_role_discount_index( $el )
		} );

		var $role_discount = $( template );

		$el.closest( '.postbox' )
		   .find( '.wdp-role-discounts' ).show()
		   .find( '.wdp-role-discounts-container' ).append( $role_discount );

		if ( data ) {
			$role_discount.find( 'input.wdp-role-discount-value, select.wdp-role-discount-value' ).each(
				function ( index, el ) {
					var field_name = $( el ).data( 'field-name' );
					var field_value = data[field_name];
					if ( field_value !== undefined ) {
						if ( "roles" === field_name ) {
							var html = '';
							$.each( field_value, function ( i, id ) {
								html += '<option selected value="' + id + '">' + id + '</option>';
							} );
							$( this ).append( html );
						} else {
							$( this ).val( field_value );
						}
					}
				} );
		}

		make_select2_preloaded( $role_discount.find( '[data-field="preloaded"]' ) );

		$role_discount.find( '.wdp_role_discount_remove' ).click( function () {
			var $rule = $( this ).closest( '.postbox' );
			$( this ).closest( '.wdp-role-discount' ).remove();

			var role_discounts_count = $rule.find( '.wdp-role-discounts .wdp-role-discount' ).length;
			if ( role_discounts_count === 0 ) {
				$rule.find( '.wdp-role-discounts' ).hide();
				$el.closest( '.postbox' ).find( '.wdp-btn-add-role-discount' ).show();

				// Unconditionally hide all sortable handlers
                $rule.find(".wdp-drag-handle").hide();
			}
		} );

	}

    function update_cart_adjustment_fields($el, data) {
        var $container = $el.closest('.wdp-cart-adjustment');
        var type = $el.val();

        var template = get_template(type + '_cart_adjustment', {
            ca: $container.data('index'),
        });

        $container.find('.wdp-cart-adjustment-field-sub').html(template);

        if (data && data.options) {
            $container.find('.wdp-cart-adjustment-value input, .wdp-cart-adjustment-value select').each(function (index) {
                if (data.options[index] !== undefined) {
                    jQuery(this).val(data.options[index]);
                }
            });
        }
    }

    function updateDealOption($row, type) {
        var before = '', after = '';
        if (type === 'free') {
            $row.find('.wdp-condition-field-deal-options').hide();
            return;
        } else if (type === 'price__fixed') {
            after = wdp_data.labels.currency_symbol;
        } else if (type === 'discount__percentage') {
            after = '%';
        } else if (type === 'discount__amount') {
            before = '-';
            after = wdp_data.labels.currency_symbol;
        }

        $row.find('.wdp-condition-field-deal-options').show();
        $row.find('.wdp-condition-field-deal-options--before').html(before);
        $row.find('.wdp-condition-field-deal-options--after').html(after);
        $row.find('.wdp-condition-field-deal-options input').val('');
    }

    function update_get_products_auto_visibility($rule) {
        var $items = $rule.find('.wdp-get-products .wdp-filter-item'),
            filter_val = $items.find('.wdp-condition-field-value select').val(),
            filter_type = $items.find('.wdp-filter-type').val(),
            deal_type = $items.find('.wdp-condition-field-deal-type select').val();

        var show = $items.length === 1 && filter_type === 'products' && deal_type === 'free' &&
                filter_val && filter_val.length === 1;

        $rule.find('.wdp-get-products-auto').toggle(show);
    }


    /* Utils */
    // find template by id, replace variables by values and return string
        function get_template(name, variables) {
        var template = $('#' + name + '_template').html() || '';
        for (var v in variables) {
            template = template.replace(new RegExp('{' + v + '}', 'g'), variables[v]);
        }
        return template;
    }

    // find next index for condition row
    function get_new_condition_index($el) {
        var newIndex = 0;

        $el.closest('.postbox').find('.wdp-conditions .wdp-condition').each(function (i, el) {
            var index = ~~ $(el).data('index');
            if (index >= newIndex) newIndex = index + 1;
        });

        return newIndex;
    }

    // returns index rule where eleemnt placed
    function get_current_rule_index($el) {
        return $el.closest('.postbox').data('index');
    }

    // find next index for filter row
    function get_new_product_filter_index($container) {
        var newIndex = 0;

        $container.find('.wdp-filter-item').each(function (i, el) {
            var index = ~~$(el).data('index');
            if (index >= newIndex) newIndex = index + 1;
        });

        return newIndex;
    }

    // returns index of filter where element placed
    function get_current_product_filter_index($el) {
        return $el.closest('.wdp-filter-item').data('index');
    }

    // find next index for limit row
    function get_new_limit_index($el) {
        var newIndex = 0;

        $el.closest('.postbox').find('.wdp-limits .wdp-limit').each(function (i, el) {
            var index = ~~$(el).data('index');
            if (index >= newIndex) newIndex = index + 1;
        });

        return newIndex;
    }

    // find next index for cart adjustment row
    function get_new_cart_adjustment_index($el) {
        var newIndex = 0;

        $el.closest('.postbox').find('.wdp-cart-adjustments .wdp-cart-adjustment').each(function (i, el) {
            var index = ~~$(el).data('index');
            if (index >= newIndex) newIndex = index + 1;
        });

        return newIndex;
    }

	function get_new_role_discount_index( $el ) {
		var newIndex = 0;

		$el.closest( '.postbox' ).find( '.wdp-role-discounts .wdp-role-discount' ).each( function ( i, el ) {
			var index = ~ ~ $( el ).data( 'index' );
			if ( index >= newIndex ) {
				newIndex = index + 1;
			}
		} );

		return newIndex;
	}

    // find next index for range row
    function get_new_range_index($el) {
        var newIndex = 0;

        $el.closest('.postbox').find('.wdp-ranges .wdp-range').each(function (i, el) {
            var index = ~~$(el).data('index');
            if (index >= newIndex) newIndex = index + 1;
        });

        return newIndex;
    }

    function get_last_priority() {
        var newIndex = 0;

        $('#rules-container .postbox').each(function (i, el) {
            var index = ~~ $('.rule-priority', el).val();
            if (index >= newIndex) newIndex = index + 1;
        });

        return newIndex;
    }

    // make select to select2 autocomplete
    function make_select2_products($els) {
        $els.each(function (index, el) {
            var $el = $(el);

            $el.select2({
                width: '100%',
                minimumInputLength: 1,
                placeholder: $el.data('placeholder'),
                escapeMarkup: function (text) { return text; },
                language: {
                    noResults: function () {
                        return wdp_data.labels.select2_no_results;
                    }
                },
                ajax: {
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            query: params.term,
                            action: 'wdp_ajax',
                            method: $el.data('list') || 'product',
                            selected: $el.val()
                        };
                    },
                    processResults: function (response) {
                        return { results: response.data || [] };
                    }
                }
            });

            $el.on('select2:select', function (e) {
                var type  = $el.attr('data-list');
                var id    = e.params.data.id;
                var title = e.params.data.text;

                wdp_data.titles[type][id] = title;
            });

            $el.parent().find('.select2-search__field').css('width', '100%');
        });
    }

    function make_select2_preloaded($els) {
        $els.each(function (index, el) {
            var $el = $(el);
            var data = wdp_data.lists[ $el.data('list') ];

            $el.select2({
                width: '100%',
                escapeMarkup: function (text) { return text; },
                minimumInputLength: 1,
                placeholder: $el.data('placeholder'),
                language: {
                    noResults: function () {
                        return wdp_data.labels.select2_no_results;
                    }
                },
                data: data
            });

            $el.parent().find('.select2-search__field').css('width', '100%');
        });
    }

    // make select to select2 with tags
    function make_select2_tags($els) {
        $els.each(function (index, el) {
            var $el = $(el);
            $el.select2({ width: '100%' });
            $el.parent().find('.select2-search__field').css('width', '100%');
        });
    }

    // update rule title
    function update_rule_title($rule) {
        var title = $rule.find('h2.hndle.ui-sortable-handle > span');

        // check if rule enabled
        var $toggler = $rule.find('.wdp-field-enabled select');
        var disabled = $toggler.val() === 'off';
        title.toggleClass('wdp-title-disabled', disabled);
        $rule.toggleClass('disabled', disabled);

        // check if bulk rule repeated
        var repeat = false;
        var $checkbox_repeat = $rule.find('.wdp-adjustments-repeat');
        if ($checkbox_repeat.length) {
            repeat = $checkbox_repeat.prop('checked');
        }
        title.toggleClass('wdp-title-repeat', repeat);

        // check if cart rule provide discount or fee
        var cart_adjustment_type = $rule.find('.cart-adjustment-type ').val() || '';
        title.toggleClass('wdp-title-discount', cart_adjustment_type.indexOf('discount') === 0);
        title.toggleClass('wdp-title-fee', cart_adjustment_type.indexOf('fee') === 0);
    }

    // make lists inside rule sortable
    function make_sortable($container) {
        $container.sortable({
            containment: 'parent',
            items: '.wdp-row',
            cursor: 'move',
            axis:   'y',
            opacity: 0.65
        });
    }

    function updateElementsVisisibilyInRowForElementValue(value, $container) {
        var $row_elements = $container.find('[data-show-if]');
        $row_elements.each(function (i, el) {
            var $el = $(el);
            var show_if = $el.data('show-if').split(',');
            var visible = show_if.indexOf(value) >= 0;
            $el.toggle( visible );

            if (!visible) {
                // flushInputs($el);
            }
        });
    }

    function flushInputs($container) {
        $container.find('input:not([data-readonly]), select:not([data-readonly]), textarea:not([data-readonly])').val('');
    }

    $('.hide-disabled-rules').change( function() {
        $('#rules-container').toggleClass('hide-disabled', $(this).val() );
    } );

    function deparam(params){

        var digitTest = /^\d+$/,
            keyBreaker = /([^\[\]]+)|(\[\])/g,
            plus = /\+/g,
            paramTest = /([^?#]*)(#.*)?$/;

        if(! params || ! paramTest.test(params) ) {
            return {};
        }


        var data = {},
            pairs = params.split('&'),
            current;

        for(var i=0; i < pairs.length; i++){
            current = data;
            var pair = pairs[i].split('=');

            // if we find foo=1+1=2
            if(pair.length != 2) {
                pair = [pair[0], pair.slice(1).join("=")]
            }

            var key = decodeURIComponent(pair[0].replace(plus, " ")),
                value = decodeURIComponent(pair[1].replace(plus, " ")),
                parts = key.match(keyBreaker);

            for ( var j = 0; j < parts.length - 1; j++ ) {
                var part = parts[j];
                if (!current[part] ) {
                    // if what we are pointing to looks like an array
                    current[part] = digitTest.test(parts[j+1]) || parts[j+1] == "[]" ? [] : {}
                }
                current = current[part];
            }
            lastPart = parts[parts.length - 1];
            if(lastPart == "[]"){
                current.push(value)
            }else{
                current[lastPart] = value;
            }
        }
        return data;
    }
});