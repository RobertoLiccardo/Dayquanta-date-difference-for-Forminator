( function ( window, document ) {
	'use strict';

	var settings = window.DMMFDateMath;
	if ( ! settings || ! Array.isArray( settings.rules ) || ! settings.rules.length ) {
		return;
	}

	function parseDate( value, format ) {
		var patterns = {
			'yy-mm-dd': /^([0-9]{4})-([0-9]{2})-([0-9]{2})$/,
			'yy/mm/dd': /^([0-9]{4})\/([0-9]{2})\/([0-9]{2})$/,
			'dd/mm/yy': /^([0-9]{2})\/([0-9]{2})\/([0-9]{4})$/,
			'mm/dd/yy': /^([0-9]{2})\/([0-9]{2})\/([0-9]{4})$/,
			'dd-mm-yy': /^([0-9]{2})-([0-9]{2})-([0-9]{4})$/,
			'mm-dd-yy': /^([0-9]{2})-([0-9]{2})-([0-9]{4})$/
		};
		var match = patterns[ format ] && patterns[ format ].exec( value || '' );
		var year;
		var month;
		var day;
		var date;

		if ( ! match ) {
			return null;
		}

		if ( 0 === format.indexOf( 'yy' ) ) {
			year = Number( match[ 1 ] );
			month = Number( match[ 2 ] );
			day = Number( match[ 3 ] );
		} else if ( 0 === format.indexOf( 'dd' ) ) {
			day = Number( match[ 1 ] );
			month = Number( match[ 2 ] );
			year = Number( match[ 3 ] );
		} else {
			month = Number( match[ 1 ] );
			day = Number( match[ 2 ] );
			year = Number( match[ 3 ] );
		}

		date = new Date( Date.UTC( year, month - 1, day ) );
		if ( date.getUTCFullYear() !== year || date.getUTCMonth() !== month - 1 || date.getUTCDate() !== day ) {
			return null;
		}

		return date;
	}

	function field( form, name ) {
		return form.querySelector( '[name="' + name + '"]' );
	}

	function message( form, rule, text ) {
		var className = 'dmmf-date-math-message-' + rule.fields.output;
		var element = form.querySelector( '.' + className );
		if ( ! element ) {
			element = document.createElement( 'p' );
			element.className = className;
			element.setAttribute( 'role', 'status' );
			form.appendChild( element );
		}

		element.textContent = text || '';
	}

	function setOutput( output, value ) {
		output.value = value;
		output.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		output.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	}

	function refresh( form, rule ) {
		var startInput = field( form, rule.fields.start );
		var endInput = field( form, rule.fields.end );
		var output = field( form, rule.fields.output );
		var start;
		var end;
		var value;

		if ( ! startInput || ! endInput || ! output ) {
			return;
		}

		if ( 'number' === rule.output_type ) {
			output.readOnly = true;
		}

		start = parseDate( startInput.value, rule.date_formats.start );
		end = parseDate( endInput.value, rule.date_formats.end );
		if ( ! start || ! end ) {
			setOutput( output, '' );
			message( form, rule, '' );
			return;
		}

		value = Math.round( ( end.getTime() - start.getTime() ) / 86400000 );
		if ( value < 0 ) {
			setOutput( output, '' );
			message( form, rule, rule.error_message || settings.messages.endBeforeStart );
			return;
		}

		if ( 'calendar_days_inclusive' === rule.mode ) {
			value += 1;
		}

		setOutput( output, String( value ) );
		message( form, rule, '' );
	}

	function attachRule( rule ) {
		var forms = document.querySelectorAll( '[id="forminator-module-' + rule.form_id + '"]' );
		Array.prototype.forEach.call( forms, function ( form ) {
			var startInput = field( form, rule.fields.start );
			var endInput = field( form, rule.fields.end );
			if ( ! startInput || ! endInput ) {
				return;
			}

			[ startInput, endInput ].forEach( function ( input ) {
				[ 'change', 'input', 'blur' ].forEach( function ( eventName ) {
					input.addEventListener( eventName, function () { refresh( form, rule ); } );
				} );
				if ( window.jQuery ) {
					window.jQuery( input ).on( 'change.dmmf input.dmmf blur.dmmf', function () { refresh( form, rule ); } );
				}
			} );

			window.setTimeout( function () { refresh( form, rule ); }, 0 );
			window.setTimeout( function () { refresh( form, rule ); }, 1200 );
		} );
	}

	function attach() {
		settings.rules.forEach( attachRule );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', attach );
	} else {
		attach();
	}
}( window, document ) );
