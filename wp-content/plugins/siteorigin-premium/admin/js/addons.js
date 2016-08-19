jQuery( function($) {

	//  Fill in the missing addon images
	$('.so-addon-banner').each( function(){
		var $$ = $(this),
			$img = $$.find('img');

		if( !$img.length ) {
			// Create an SVG image as a placeholder icon
			var pattern = Trianglify({
				width: 128,
				height: 128,
				variance : 1,
				cell_size: 32,
				seed: $$.data('seed')
			});

			$$.append( pattern.svg() );
		}
	} );

	$('.so-addon' ).each( function(){

		var $$ = $(this ),
			id = $$.data('id' ),
			section = $$.data('section');

		$$.find('.so-addon-toggle-active button' ).click( function( e ){

			var $b = $(this);
			var status = parseInt( $b.data('status') );

			$b.prop('disabled', true);

			// Sent this request to the server
			$.post(
				$('#addons-list' ).data('action-url'),
				{
					id: id,
					section: section,
					status: status
				},
				function( response ){
					$b.prop('disabled', false);

					if( status ) {
						$$.removeClass('so-addon-is-inactive').addClass('so-addon-is-active');
					} else {
						$$.removeClass('so-addon-is-active').addClass('so-addon-is-inactive');
					}

					if( typeof response.action_links !== 'undefined' && response.action_links.length ) {
						// Add the action links
						var $links = $$.find( '.so-addon-links' ).empty();
						if( ! $links.length ) {
							$links = $( '<div class="so-addon-links"></div>' ).insertAfter( $$.find( '.so-addon-name' ) );
						}
						$links.html( response.action_links.join( ' | ' ) );
					}

					$(window ).resize();
				}
			);

		} );

	} );

	// Addon search
	var currentSection = '';
	$('.page-sections a' ).click( function( e ){
		e.preventDefault();

		$('.page-sections li' ).removeClass( 'active-section' );

		var $$ = $(this);

		currentSection = $$.data('section');
		$$.parent( 'li' ).addClass('active-section');

		filterAddons();
	} );

	var filterAddons = function( ){
		var section = currentSection;
		var q = $('.addons-search' ).val();

		if( q === '' ) {
			if( section === '' ) {
				$('.so-addon-wrap').show();
			}
			else {
				$('.so-addon-wrap').hide();
				$('.so-addon[data-section="' + currentSection + '"]' ).parents().show();
			}
		}
		else {
			$('.so-addon').each( function(){
				var $$ = $(this);

				if(
					$$.find('h3').html().toLowerCase().indexOf(q) > -1 &&
					( section === '' || $$.data( 'section' ) === section )
				) {
					$$.parent().show();
				}
				else {
					$$.parent().hide();
				}
			} );
		}
	};
	filterAddons( );

	$('.addons-search' ).on( {
		'keyup' : filterAddons,
		'change' : filterAddons,
		'search' : filterAddons
	} );

	// Make sure addon heights are all the same
	$(window).resize(function() {
		var $addons = $('.so-addon').css('height', 'auto');
		var largestHeight = 0;

		$addons.each(function () {
			largestHeight = Math.max(largestHeight, $(this).height()  );
		});

		$addons.each(function () {
			$(this).css('height', largestHeight);
		});

	}).resize();
} );