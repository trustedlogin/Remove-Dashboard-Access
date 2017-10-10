/* global rda_vars */

/**
 * Mimic WordPress Core's front-page drop-down toggle control.
 */
( function( $ ) {
	var section = $( '.form-table' ),
		capType = section.find( 'input:radio[value="capability"]' ),
		selects = section.find( 'select' ),
		check_disabled = function() {
			selects.prop( 'disabled', ! capType.prop( 'checked' ) );
		};
	check_disabled();
	section.find( 'input:radio' ).change( check_disabled );

	var	selectedCapInput = $( '#selected-capability' ),
		lockoutMessage = $( '#lockout-message' ),
		formSubmit     = $( '#rda-options-form :submit' );

	$( '#rda-options-form').on( 'change', function( event ) {
		lockoutMessage.slideUp( 'fast' ).addClass( 'screen-reader-text' ).html( '' );
		formSubmit.removeAttr( 'disabled' );

		var switchCap = $( "input[name='rda_access_switch']:checked" ).val();

		if ( 'capability' === switchCap ) {
			selectedCap = $( "select[name='rda_access_cap'] option:selected" ).val();

			selectedCapInput.val( selectedCap );
		} else {
			selectedCapInput.val( switchCap );
		}

		$.ajax( {
			url: rda_vars.ajaxurl,
			type: 'POST',
			data: {
				action: 'cap_lockout_check',
				nonce: $( '#rda-lockout-nonce' ).val(),
				cap: selectedCapInput.val(),
				switch: switchCap
			},
			dataType: 'json',
			success: function( response ) {

				// If response.success is true, nothing to do here. If false, print the message.
				if ( ! response.success && response.data.message ) {
					formSubmit.attr( 'disabled', 'disabled' );
					lockoutMessage.removeClass( 'screen-reader-text' ).html( response.data.message ).slideDown( 'fast' );
				}
			}

		} );

	} );

} )( jQuery );
