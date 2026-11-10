( function ( document ) {
	'use strict';

	var operation = document.getElementById( 'dmmf-operation' );
	var inclusiveRow = document.getElementById( 'dmmf-inclusive-end-row' );
	var inclusive = document.querySelector( '[name="inclusive_end"]' );
	var preview = document.getElementById( 'dmmf-rule-preview' );
	var start = document.getElementById( 'dmmf-start-field' );
	var end = document.getElementById( 'dmmf-end-field' );
	var output = document.getElementById( 'dmmf-output-field' );

	if ( ! operation || ! inclusiveRow || ! inclusive || ! preview || ! start || ! end || ! output ) {
		return;
	}

	function label( select ) {
		return select.options[ select.selectedIndex ].text;
	}

	function update() {
		var calendarDays = 'calendar_days' === operation.value;
		var count = calendarDays ? ( inclusive.checked ? 'calendar days, including the end date' : 'calendar days' ) : 'nights';

		inclusiveRow.hidden = ! calendarDays;
		inclusive.disabled = ! calendarDays;
		preview.textContent = 'Preview: ' + label( end ) + ' - ' + label( start ) + ' = ' + label( output ) + ' (' + count + ')';
	}

	[ operation, inclusive, start, end, output ].forEach( function ( element ) {
		element.addEventListener( 'change', update );
	} );

	update();
}( document ) );
