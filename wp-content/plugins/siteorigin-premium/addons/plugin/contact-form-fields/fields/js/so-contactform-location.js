/**
 * (c) SiteOrigin, freely distributable under the terms of the GPL 2.0 license.
 */
var sowb = window.sowb || {};
sowb.LocationFieldMap = function ( $ ) {
	return {
		DEFAULT_LOCATIONS: [
			'Addo Elephant National Park, R335, Addo',
			'Cape Town, Western Cape, South Africa',
			'San Francisco Bay Area, CA, United States',
			'New York, NY, United States',
		],
		showMap: function ( element, location, options ) {

			var userMapTypeId = 'user_map_style';

			var mapOptions = {
				zoom: 10,
				disableDefaultUI: true,
				zoomControl: true,
				panControl: true,
				center: location,
				mapTypeControlOptions: {
					mapTypeIds: [ google.maps.MapTypeId.ROADMAP, userMapTypeId ]
				}
			};

			var map = new google.maps.Map( element, mapOptions );

			var addressMarker = new google.maps.Marker( {
				position: location,
				map: map,
				draggable: true,
			} );

			var center;
			google.maps.event.addDomListener( map, 'idle', function () {
				center = map.getCenter();
			} );
			google.maps.event.addDomListener( window, 'resize', function () {
				map.setCenter( center );
			} );

			var updateMapLocation = function ( address ) {
				if ( this.inputAddress != address ) {
					this.inputAddress = address;
					this.getLocation( this.inputAddress ).done(
						function ( location ) {
							map.setZoom( 15 );
							map.setCenter( location );
							addressMarker.setPosition( location );
							addressMarker.setTitle( this.inputAddress );
						}.bind( this )
					);
				}
			}.bind( this );

			var $autocompleteElement = $( options.autocompleteElement );
			var autocomplete = options.autocomplete;
			autocomplete.addListener( 'place_changed', function () {
				var place = autocomplete.getPlace();
				map.setZoom( 15 );
				if ( place.geometry ) {
					map.setCenter( place.geometry.location );
					addressMarker.setPosition( place.geometry.location );
				}
			} );

			google.maps.event.addDomListener( options.autocompleteElement, 'keypress', function ( event ) {
				var key = event.keyCode || event.which;
				if ( key == '13' ) {
					event.preventDefault();
				}
			} );

			$autocompleteElement.focusin( function () {
				if ( !this.resultsObserver ) {
					var autocompleteResultsContainer = document.querySelector( '.pac-container' );
					this.resultsObserver = new MutationObserver( function () {
						var $topResult = $( $( '.pac-item' ).get( 0 ) );
						var queryPartA = $topResult.find( '.pac-item-query' ).text();
						var queryPartB = $topResult.find( 'span' ).not( '[class]' ).text();
						var topQuery = queryPartA + ( queryPartB ? (', ' + queryPartB) : '' );
						if ( topQuery ) {
							updateMapLocation( topQuery );
						}
					} );

					var config = { attributes: true, childList: true, characterData: true };

					this.resultsObserver.observe( autocompleteResultsContainer, config );
				}
			}.bind( this ) );

			var revGeocode = function ( latLng ) {
				this.getGeocoder().geocode( { location: latLng }, function ( results, status ) {
					if ( status == google.maps.GeocoderStatus.OK ) {
						if ( results.length > 0 ) {
							var addr = results[ 0 ].formatted_address;
							$autocompleteElement.val( addr );
							addressMarker.setPosition( latLng );
							addressMarker.setTitle( addr );
						}
					}
				}.bind( this ) );
			}.bind( this );

			map.addListener( 'click', function ( event ) {
				revGeocode( event.latLng );
			} );

			addressMarker.addListener( 'dragend', function ( event ) {
				revGeocode( event.latLng );
			} );

		},
		initMaps: function () {
			var $autoCompleteFields = $( '.so-contactform-location-autocomplete' );
			$autoCompleteFields.each( function ( index, element ) {
				var autocomplete = new google.maps.places.Autocomplete(
					element,
					{ types: [ 'address' ] }
				);

				var $mapField = $( element ).siblings( '.so-contactform-location-google-map' );

				if ( $mapField.length > 0 ) {
					var options = $mapField.data( 'options' );
					options.autocomplete = autocomplete;
					options.autocompleteElement = element;
					this.getLocation( options.address ).done(
						function ( location ) {
							this.showMap( $mapField.get( 0 ), location, options );
						}.bind( this )
					).fail( function () {
						$mapField.append( '<div><p><strong>There were no results for the place you entered. Please try another.</strong></p></div>' );
					} );
				}
			}.bind( this ) );
		},
		getGeocoder: function () {
			if ( !this._geocoder ) {
				this._geocoder = new google.maps.Geocoder();
			}
			return this._geocoder;
		},
		getLocation: function ( inputLocation ) {
			var locationPromise = new $.Deferred();
			var location = { address: inputLocation };
			//check if address is actually a valid latlng
			var latLng;
			if ( inputLocation && inputLocation.indexOf( ',' ) > -1 ) {
				var vals = inputLocation.split( ',' );
				// A latlng value should be of the format 'lat,lng'
				if ( vals && vals.length == 2 ) {
					latLng = new google.maps.LatLng( vals[ 0 ], vals[ 1 ] );
					// Let the API decide if we have a valid latlng
					if ( !(isNaN( latLng.lat() ) || isNaN( latLng.lng() )) ) {
						location = { location: { lat: latLng.lat(), lng: latLng.lng() } };
					}
				}
			}

			if ( location.hasOwnProperty( 'location' ) ) {
				// We're using entered latlng coordinates directly
				locationPromise.resolve( location.location );
			} else if ( location.hasOwnProperty( 'address' ) ) {

				// Either user entered an address, or fall back to defaults and use the geocoder.
				if ( !location.address ) {
					var rndIndx = parseInt( Math.random() * this.DEFAULT_LOCATIONS.length );
					location.address = this.DEFAULT_LOCATIONS[ rndIndx ];
				}
				this.getGeocoder().geocode( location, function ( results, status ) {
					if ( status == google.maps.GeocoderStatus.OK ) {
						locationPromise.resolve( results[ 0 ].geometry.location );
					}
					else if ( status == google.maps.GeocoderStatus.ZERO_RESULTS ) {
						locationPromise.reject( status );
					}
				} );
			}
			return locationPromise;
		},
	};
};

// Called by Google Maps API when it has loaded.
function soGoogleMapInitialize () {
	if(typeof google.maps.places !== 'undefined') {
		new sowb.LocationFieldMap(window.jQuery).initMaps();
	}
}

jQuery( function ( $ ) {
	if ( window.google && window.google.maps && window.google.maps.places ) {
		soGoogleMapInitialize();
	} else {
		var $mapField = $( '.so-contactform-location-google-map' );
		var apiKey = $mapField.length ? $mapField.data( 'options' ).apiKey : null;

		var apiUrl = 'https://maps.googleapis.com/maps/api/js?callback=soGoogleMapInitialize&libraries=places';
		if ( apiKey ) {
			apiUrl += '&key=' + apiKey;
		}
		var script = $( '<script type="text/javascript" src="' + apiUrl + '">' );
		$( 'body' ).append( script );
	}
} );
