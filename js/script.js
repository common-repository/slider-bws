( function( $ ) {
	$( window ).on( 'load',  function() {

		$( window ).on( 'resize', function() {
			/* Enable style for the Booking search form */

			$( '.sldr_bkng_wrapper' ).each( function() {
				var sliderBoxWidth = $( this ).find( '.sldr_wrapper' ).width();

				var sliderExist = $( this ).find( '.sldr_wrapper' ).length;

				var return_location_list = $( this ).find( 'select[name="bws_bkng_search[bkng_return_location]"]' ).parent().parent();

				if ( sliderExist > 0 && sliderBoxWidth > 900 ) {
					/* If the slider block width is more than 900px, display standard Booking search form */
					if ( $( this ).hasClass( 'sldr_bkng_inline_form' ) ) {
						$( this ).removeClass( 'sldr_bkng_inline_form' );
					}

					if ( $( this ).hasClass( 'sldr_bkng_mobile_view' ) ) {
						$( this ).removeClass( 'sldr_bkng_mobile_view' );
					}

					if ( $( this ).hasClass( 'sldr_bkng_mobile_inline_view' ) ) {
						$( this ).removeClass( 'sldr_bkng_mobile_inline_view' );
					}

					if ( $( this ).find('.bws_bkng_search_products_item').parent().hasClass( 'bws_bkng_search_products_items' ) ) {
						$( this ).find( '.bws_bkng_search_products_item' ).unwrap( '<div class="bws_bkng_search_products_items" />' );
					}
					/* For theme Renty */
					var interval = setInterval( function() {
						if ( $( 'a.sbToggle' ).length > 0 ) {
							clearInterval( interval );
							$( 'a.sbToggle' ).css( 'width', '0' );
						}
					}, 50 );
					/* end */
					$( 'a.sbToggle' ).css( 'width', '0' );
					return_location_list.css( {'display' : 'block', 'width' : '100%'} );
				} else if( sliderExist > 0 && sliderBoxWidth > 520 && sliderBoxWidth < 900 ) {

					if ( $( this ).hasClass( 'sldr_bkng_mobile_view' ) ) {
						$( this ).removeClass( 'sldr_bkng_mobile_view' );
					}

					/* If the slider block width is less than 900px, display Booking search form in row */
					if ( ! $( this ).hasClass( 'sldr_bkng_mobile_inline_view' ) ) {
						$( this ).addClass( 'sldr_bkng_mobile_inline_view' );
					}

					/* Show/hide the return locations list in the products search form */
					$( this ).find( '.crrntl-return-different-location' ).prop( 'checked', true );

					/* Display hidden return locations field on the search form */
					$( this ).find( '.crrntl-return-different-location' ).on( 'change', function() {

						/*var return_location_list = $( this ).parent().parent().next( 'div' );*/
						if ( $( this ).is( ':checked' ) ) {
							return_location_list.css( { 'display':'block', 'width':'21%' } );
						} else {
							return_location_list.hide();
						}
					} ).trigger( 'change' );

					if ( ! $( this ).hasClass( 'sldr_bkng_inline_form' ) ) {
						$( this ).addClass( 'sldr_bkng_inline_form' );
					}

					$( '.bws_bkng_search_products_form' ).each( function() {
						if ( ! $( this ).find( '.bws_bkng_filter_button' ).parent().hasClass( 'bws_bkng_buttons' ) ) {
							$( this ).find( '.bws_bkng_filter_button[type="submit"]' ).wrap( '<div class="bws_bkng_buttons" />' );

							$( this ).find( '.bws_bkng_filter_button[type="reset"]' ).appendTo( $( this ).find( '.bws_bkng_buttons' ) );
						}

						if ( ! $( this ).children().hasClass( 'bws_bkng_search_products_items' ) ) {
							$( this ).children().wrapAll( '<div class="bws_bkng_search_products_items" />' );
						}
					} );
					/* if there isn't any created location */
					if ( $( 'select[name="bws_bkng_search[bkng_return_location]"]' ).length == 0 ) {
						$( '.bws_bkng_search_products_item:nth-child(2)' ).css( 'top', '55%' );
					}
					/* end */
				} else if ( sliderExist > 0 && sliderBoxWidth < 520 ) {
					/* If the slider block width is less than 520px, display standard mobile Booking search form */
					if ( ! $( this ).hasClass( 'sldr_bkng_mobile_view' ) ) {
						$( this ).addClass( 'sldr_bkng_mobile_view' );
					}

					if ( $( this ).hasClass( 'sldr_bkng_mobile_inline_view' ) ) {
						$( this ).removeClass( 'sldr_bkng_mobile_inline_view' );
					}

					if ( $( this ).hasClass( 'sldr_bkng_inline_form' ) ) {
						$( this ).removeClass( 'sldr_bkng_inline_form' );
					}
					return_location_list.css( {'display' : 'block', 'width' : '100%'} );
				}
			} );
			/* Search form position regarding the height of the slider */
			$( '.sldr_bkng_wrapper' ).each( function() {
				var height = $( this ).find( '.sldr_wrapper' ).height();
				var marginTop = Math.ceil( height * -0.2 );
				$( this ).find( '.crrntl_search_form_wrap' ).find( '.crrntl_search_form' ).css( 'margin-top', marginTop );
			} );

		} ).trigger( 'resize' );
	} );
} )( jQuery );
