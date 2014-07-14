/**
 * Mimic WordPress Core's front-page dropdown toggle control.
 */
jQuery( document ).ready( function( $ ) {
	var section = $( '.form-table' ),
		capType = section.find( 'input:radio[value="capability"]' ),
		selects = section.find( 'select' ),
		check_disabled = function() {
			selects.prop( 'disabled', ! capType.prop( 'checked' ) );
		};
	check_disabled();
	section.find( 'input:radio' ).change( check_disabled );
} );
