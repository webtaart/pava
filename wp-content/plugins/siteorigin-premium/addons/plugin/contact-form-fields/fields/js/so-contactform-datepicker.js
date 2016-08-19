/* globals jQuery */

jQuery( function ( $ ) {

	$( '.datepicker' ).each( function ( index, element ) {
		var $datepicker = $( element );
		var $valInput = $datepicker.siblings( '.so-contactform-datetime' );
		$datepicker.pikaday( {
			defaultDate: new Date( $valInput.val() ),
			setDefaultDate: true,
			onSelect: function () {
				var date = this.getDate();
				var $timepicker = $datepicker.siblings( '.timepicker' );
				if ( $timepicker.length > 0 ) {
					var time = $timepicker.timepicker( 'getTime' );
					if ( time && time instanceof Date ) {
						date.setHours( time.getHours(), time.getMinutes(), time.getSeconds(), time.getMilliseconds() );
						$valInput.val( date );
					}
				} else {
					$valInput.val( date );
				}
			}
		} );
	} );

	$( '.timepicker' ).each( function ( index, element ) {
		var $timepicker = $( element );
		$timepicker.timepicker();
		var $valInput = $timepicker.siblings( '.so-contactform-datetime' );
		if ( $valInput.val() ) {
			var curDate = new Date( $valInput.val() );
			// If it's not a valid date, it's just a time string, e.g. '12:30pm'
			if ( isNaN( curDate.valueOf() ) ) {
				$timepicker.val( $valInput.val() );
			} else {
				$timepicker.timepicker( 'setTime', curDate );
			}
		}
		$timepicker.on( 'changeTime', function () {
			var $datepicker = $timepicker.siblings( '.datepicker' );
			// If we have a datepicker too, then set the time on the datepicker's selected date.
			if ( $datepicker.length > 0 ) {
				var date = $datepicker.data( 'pikaday' ).getDate();
				if ( date ) {
					var time = $timepicker.timepicker( 'getTime' );
					time = new Date( date.setHours(
						time.getHours(),
						time.getMinutes(),
						time.getSeconds(),
						time.getMilliseconds()
					) );
					$valInput.val( time );
				}
			} else {
				$valInput.val( $timepicker.val() );
			}
		} );
	} );

} );
	
