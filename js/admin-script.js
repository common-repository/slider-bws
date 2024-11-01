function sldr_setMessage( msg ) {
	(function($) {
		$( ".error" ).hide();
		$( ".sldr_image_update_message" ).html( msg ).show();
	})(jQuery);
}

function sldr_setError( msg ) {
	(function($) {
		$( ".sldr_image_update_message" ).hide();
		$( ".error" ).html( msg ).show();
	})(jQuery);
}

(function($) {
	$(document).ready( function() {

		if ( $( window ).width() < 800 ) {
			$.each(	$( '.sldr_add_responsive_column' ), function() {
				var content = '<div class="sldr_info hidden">';
				$.each(	$( this ).find( 'td:hidden' ).not( '.column-order' ), function() {
					content = content + '<label>' + $( this ).attr( 'data-colname' ) + '</label><br/>' + $( this ).html() + '<br/>';
					$( this ).html( '' );
				});
				content = content + '</div>';
				$( this ).find( '.column-title' ).append( content );
				$( this ).find( '.sldr_info_show' ).show();
			});
			$( '.sldr_info_show' ).on( 'click', function( event ) {
				event.preventDefault();
				if ( $( this ).next( '.sldr_info' ).is( ':hidden' ) ) {
					$( this ).next( '.sldr_info' ).show();
				} else {
					$( this ).next( '.sldr_info' ).hide();
				}
			});
		}

		if ( ! $( '#sldr-attachments li' ).length )
			$( '.sldr-media-bulk-select-button' ).hide();

		$( '#sldr-media-insert' ).click( function open_media_window() {
			if ( this.window === undefined ) {
				this.window = wp.media({
					title: sldr_vars.wp_media_title,
					library: { type: 'image, video/MP4, video/WebM, video/Ogg' },
					multiple: true,
					button: { text: sldr_vars.wp_media_button }
				});

				var self = this; /* Needed to retrieve our variable in the anonymous function below */
				this.window.on( 'select', function() {
					var all = self.window.state().get( 'selection' ).toJSON();
					all.forEach( function( item, i, arr ) {
						$.ajax({
							url: '../wp-admin/admin-ajax.php',
							type: "POST",
							data: "action=sldr_add_from_media&add_id=" + item.id + "&post_id=" + $( '#post_ID' ).val() + "&sldr_ajax_add_nonce=" + sldr_vars.sldr_add_nonce,
							success: function( result ) {
								$( '#sldr-attachments' ).prepend( result );
								$( '#sldr-attachments li:first-child' ).addClass( 'success' );
								$( '.sldr-media-bulk-select-button' ).show();
								if ( ! $( '#sldr-attachments' ).data( 'ui-sortable' ) ) {
									sldr_add_sortable();
								}
							}
						});
						$('<input type="hidden" name="sldr_new_image[]" id="sldr_new_image_' + item.id + '" value="' + item.id + '" />').appendTo( '#hidden' );
					});
				});
			}

			this.window.open();
			return false;
		});

		function sldr_add_sortable() {
			if ( $.fn.sortable ) {
				if ( $( "#sldr-attachments li" ).length > 1 ) {
					$( '#sldr-attachments' ).sortable({
						stop: function( event, ui ) {
							var g = $( '#sldr-attachments' ).sortable( 'toArray' );
							var f = g.length;
							$.each(	g,
								function( k,l ) {
									$( '#' + l + ' input[name^="_sldr_order"]' ).val( k + 1 );
								}
							)
						}
					});
				}
			}
		}
		sldr_add_sortable();

		$( '.sldr-media-bulk-select-button' ).on( 'click', function() {
			$( '.attachments' ).sortable( 'disable' ).addClass( 'bulk-selected' );
			$( '.sldr-wp-filter' ).addClass( 'selected' );
			$( '.sldr-media-attachment' ).on( 'click', function() {
				var attachment_id = $( this ).find( '.sldr_attachment_id' ).val();
				if ( $( this ).hasClass( 'details' ) ) {
					$( this ).removeClass( 'details selected' );		
					$( '#sldr_new_image_' + attachment_id ).removeClass( 'selected remove-selected' );
				} else {
					$( this ).addClass( 'details selected' );
					$( '#sldr_new_image_' + attachment_id ).addClass( 'selected remove-selected' );	
				}	
				if ( $( this ).length > 0 )
					$( '.sldr-media-bulk-delete-selected-button' ).removeAttr( 'disabled' );
				else
					$( '.sldr-media-bulk-delete-selected-button' ).attr( 'disabled', 'disabled' );
			});
			$( '.sldr-media-check' ).on( 'click', function() {
				if ( $( this ).parent().hasClass( 'details' ) )
					$( this ).parent().removeClass( 'details selected' );
				else 
					$( this ).parent().addClass( 'details selected' );
				if ( $( '.sldr-media-attachment.selected' ).length > 0 )
					$( '.sldr-media-bulk-delete-selected-button' ).removeAttr( 'disabled' );
				else
					$( '.sldr-media-bulk-delete-selected-button' ).attr( 'disabled', 'disabled' );
				return false;
			});
			return false;
		});

		$( '.sldr-media-bulk-cansel-select-button' ).on( 'click', function() {
			$( '.attachments' ).sortable().removeClass( 'bulk-selected' );
			$( '.attachments' ).sortable( 'option', 'disabled', false );
			$( '.attachments li' ).removeClass( 'details selected' );
			$( '.sldr-wp-filter' ).removeClass( 'selected' );
			$( '.sldr-media-attachment' ).off( 'click' );
			$( '.sldr-media-check' ).off( 'click' );
			return false;
		});

		$( document ).on( 'click', '.sldr-media-actions-delete', function() {
			if ( window.confirm( sldr_vars.warnSingleDelete ) ) {
				var attachment_id = $( this ).parent().find( '.sldr_attachment_id' ).val(),
					slider_id = $( this ).parent().find( '.sldr_slider_id' ).val();
				$.ajax({
					url: '../wp-admin/admin-ajax.php',
					type: "POST",
					data: "action=sldr_delete_image&delete_id_array=" + attachment_id + "&slider_id=" + slider_id + "&sldr_ajax_nonce_field=" + sldr_vars.sldr_nonce,
					success: function( result ) {
						$( '#sldr_new_image_' + attachment_id ).remove();
						$( '#post-' + attachment_id ).remove();
						tb_remove();
						if ( ! $( '.attachments li' ).length )
							$( '.sldr-media-bulk-select-button' ).hide();
					}
				});
			}
		});

		$( '.sldr-media-bulk-delete-selected-button' ).on( 'click', function() {
			if ( 'disabled' != $( this ).attr( 'disabled' ) ) {
				if ( window.confirm( sldr_vars.warnBulkDelete ) ) {
					var delete_id_array = '';
					$( '.attachments li.selected' ).each( function() {
						delete_id_array += $( this ).attr( 'id' ).replace( 'post-', '' ) + ',';
					});
					var slider_id = $( '.sldr_slider_id' ).val();
					$( '.sldr-media-spinner' ).css( 'display', 'inline-block' );
					$( '.attachments' ).attr( 'disabled', 'disabled' );
					$.ajax({
						url: '../wp-admin/admin-ajax.php',
						type: "POST",
						data: "action=sldr_delete_image&delete_id_array=" + delete_id_array + "&slider_id=" + slider_id + "&sldr_ajax_nonce_field=" + sldr_vars.sldr_nonce,
						success: function( result ) {
							$( '.remove-selected' ).remove();
							$( '.sldr-media-attachment.selected' ).remove();
							$( '.sldr-media-bulk-delete-selected-button' ).attr( 'disabled', 'disabled' );
							if ( ! $( '#post-body-content .attachments li' ).length ) {
								$( '.sldr-media-bulk-cansel-select-button' ).trigger( 'click' );
								$( '.sldr-media-bulk-select-button' ).hide();
							}
							$( '.sldr-media-spinner' ).css( 'display', 'none' );
							$( '.attachments' ).removeAttr( 'disabled' );
						}
					});
				}
			}
			return false;
		});

	});
})(jQuery);

/* Create notice on a gallery page */
function sldr_notice_view( data_id ) {
	(function( $ ) {
		/*	function to send Ajax request to gallery notice */
		sldr_notice_media_attach = function( thumb_id ) {
			$.ajax({
				url: "../wp-admin/admin-ajax.php",
				type: "POST",
				data: "action=sldr_media_check&thumbnail_id=" + thumb_id + "&sldr_ajax_nonce_field=" + sldr_vars.sldr_nonce,
				success: function( html ) {
					if ( undefined != html.data ) {
						$( ".media-frame-content" ).find( "#sldr_media_notice" ).html( html.data );
						$( '.button.media-button-select' ).attr( 'disabled', 'disabled' );
					} else {
						$( '.button.media-button-select' ).removeAttr( 'disabled' );
					}
				}
			});
		}
		sldr_notice_media_attach( data_id );
	})( jQuery );
}