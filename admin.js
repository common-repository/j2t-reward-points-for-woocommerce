
/* global ajaxurl, typenow, inlineUpdateCustomerPoints */

window.wp = window.wp || {};

/**
 * Manages the quick edit and bulk edit windows for editing posts or pages.
 *
 * @namespace inlineUpdateCustomerPoints
 *
 * @since 2.7.0
 *
 * @type {Object}
 *
 * @property {string} type The type of inline editor.
 * @property {string} what The prefix before the post ID.
 *
 */
( function( $, wp ) {

	window.inlineUpdateCustomerPoints = {
	init : function(){
		//var t = this, qeRow = $('.j2t-add-row');
		var t = this, qeRow = $('#inline-edit');

		qeRow.keyup(function(e){
			// Revert changes if Escape key is pressed.
			if ( e.which === 27 ) {
				return inlineUpdateCustomerPoints.revert();
			}
		});

		$( '.cancel', qeRow ).click( function() {
			return inlineUpdateCustomerPoints.revert();
		});
		$( '.save', qeRow ).click( function() {
			return inlineUpdateCustomerPoints.save(this);
		});
		
		$( '#the-list' ).on( 'click', '.editinline', function() {
			$( this ).attr( 'aria-expanded', 'true' );
			inlineUpdateCustomerPoints.edit( this );
		});
	},

	edit : function(id) {
		var t = this, fields, editRow, rowData, status, pageOpt, pageLevel, nextPage, pageLoop = true, nextLevel, f, val, pw;
		t.revert();

        var parent_elt, user_id ;
		if ( typeof(id) === 'object' ) {
            user_id = $(id).closest('tr').data('user-id');
            parent_elt = $(id).closest('div.row-actions');
            id = $(id).attr('id');
		}
		editRow = $('#inline-edit').clone(true);
        if ( typeof(parent_elt) === 'object' ) {
            
            $( editRow ).insertAfter( parent_elt ).show().addClass('j2t-add-row').attr('id','edit-'+user_id);

			$('.j2t-add-row input').keydown(function(e){
				//console.log('key press');
				if ( e.which === 13 && ! $( e.target ).hasClass( 'cancel' ) ) {
					return t.save($( e.target ));
				}
			});
        }
		return false;
	},

	save : function(id) {

        if ( typeof(id) === 'object' ) {
            var user_id = $(id).closest('tr').data('user-id');
		    $( 'table.widefat .spinner' ).addClass( 'is-active' );

            params = {
                action: 'inline_rewardpoints_save',
                user_id: user_id,
                point_update: 'true'
            };
            fields = $('#edit-'+user_id).find(':input').serialize();
		    params = fields + '&' + $.param(params);
            
			var $errorNotice = $( '#edit-' + user_id + ' .inline-edit-save .notice-error' ),
					$error = $errorNotice.find( '.error' );

			var inserted_point_value = $('tr.item-row-user-'+user_id+' input.user_points').val();
			if (!inserted_point_value || inserted_point_value == 0 && !isNaN(inserted_point_value) ) {
				$errorNotice.removeClass( 'hidden' );
				$error.text( wp.i18n.__( 'Point value must be greater or lower than zero.' ) );
				$( 'table.widefat .spinner' ).removeClass( 'is-active' );
			} else {
				// Make Ajax request.
				$.post( ajaxurl, params,
					function(r) {	
						$( 'table.widefat .spinner' ).removeClass( 'is-active' );						
						$('tr.item-row-user-'+user_id+' .inline-val.points_collected').html(r.gathered_points);
						$('tr.item-row-user-'+user_id+' .inline-val.points_used').html(r.used_points);
						$('tr.item-row-user-'+user_id+' .inline-val.points_current').html(r.available_points);	
	
						if (r.in_error) {						
							$errorNotice.removeClass( 'hidden' );
							$error.text( r.error_message );
						} else {
							wp.a11y.speak( wp.i18n.__( 'Changes saved.' ) );
							$('#edit-'+user_id).remove();
						}
					},
				'json');
			}            
        }
		return false;
	},
	
	revert : function(){
        $('.j2t-add-row').remove();
		return false;
	}
};


$( document ).ready( function(){ inlineUpdateCustomerPoints.init(); } );


})( jQuery, window.wp );
