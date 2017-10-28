/* global rda_vars */

window.wp = window.wp || {};

/**
 * Mimic WordPress Core's front-page drop-down toggle control.
 */
( function( $, wp ) {
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
		formSubmit     = $( '#rda-options-form :submit' ),
		inputAccessSwitch = $( 'input[name="rda_access_switch"]' ),
		inputAccessCap = $( 'select[name="rda_access_cap"]' );

	var noSubmit = $( '<span></span>' )
		.attr( 'id', 'rda-no-submit-message' )
		.attr( 'class', 'description' )
		.text( rda_vars.no_submit );

	noSubmit.insertAfter( formSubmit ).hide();

	var ajaxCheckCap = function( event ) {
		lockoutMessage.slideUp( 'fast' ).addClass( 'screen-reader-text' ).html( '' );
		formSubmit.removeAttr( 'disabled' );
		noSubmit.hide();

		var switchCap = inputAccessSwitch.filter( ':checked' ).val();

		if ( 'capability' === switchCap ) {
			selectedCap = inputAccessCap.find( 'option' ).filter( ':selected' ).val();

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

				/*
				 * If response.success is true and the notice is showing, hide it and give the all clear.
				 *
				 * If false, print the message.
				 */
				if ( response.success ) {
					if ( ! lockoutMessage.hasClass( 'screen-reader-text' ) ) {
						wp.a11y.speak( rda_vars.yes_submit );
					}
				} else {
					// If response.success is true, nothing to do here. If false, print the message.
					if ( response.data.message ) {
						formSubmit.attr( 'disabled', 'disabled' );
						noSubmit.show();

						lockoutMessage.removeClass( 'screen-reader-text' ).html( response.data.message ).slideDown( 'fast' );
						wp.a11y.speak( response.data.message );
						wp.a11y.speak( rda_vars.no_submit );
					}

				}
			}

		} );
	}

	inputAccessCap.on( 'change', ajaxCheckCap );
	inputAccessSwitch.on( 'change', ajaxCheckCap );

} )( jQuery, window.wp );
