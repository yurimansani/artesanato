jQuery( document ).ready( function ( $ ) {
	function init_events() {
		if ( jQuery( '.wdp_bulk_table_content' ).length > 0 ) {
			jQuery( '.variations_form' ).on( 'found_variation check_variations',
				{variationForm: this},
				function ( event, variation ) {
					var form = event.data.variationForm;
					if ( typeof variation === 'undefined' ) {
						jQuery( '.wdp_bulk_table_content' ).html( "" );
						return false;
					}

					var data = {
						action: 'get_table_with_product_bulk_table',
						product_id: parseInt( variation.variation_id ),
					};

					jQuery.ajax( {
						url: script_data.ajaxurl,
						data: data,
						dataType: 'json',
						type: 'POST',
						success: function ( response ) {
							if ( response.success ) {
								jQuery( '.wdp_bulk_table_content' ).html( response.data )
							} else {
								jQuery( '.wdp_bulk_table_content' ).html( "" );
							}
						},
						error: function ( response ) {
							jQuery( '.wdp_bulk_table_content' ).html( "" );
						}
					} );
				} )
			                            .on( 'click', '.reset_variations',
				                            {variationForm: this},
				                            function ( event, variation ) {
					                            jQuery( '.wdp_bulk_table_content' ).html( "" );
					                            return false;
				                            } );

		}

	}

	if ( script_data.js_init_trigger ) {
		$( document ).on( script_data.js_init_trigger, function () {
			init_events();
		} );
	}

	init_events();
} );