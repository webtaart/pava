jQuery( function( $ ){
	var tl = $('#toplevel_page_siteorigin .toplevel_page_siteorigin' );
	var img = tl.find( '.wp-menu-image img' );

	if( img.length ) {
		var hoverImg = $( '<img>' )
			.attr( 'src', img.attr('src' ).replace('.svg', '-hover.svg') )
			.css( 'padding-top', 3 )
			.insertAfter( img )
			.hide();

		img.data( 'src', img.attr('src') );

		// Add the hover animation
		tl.hover(
			function(){
				img.hide();
				hoverImg.show();
			},
			function(){
				img.show();
				hoverImg.hide();
			}
		);
	}
} ) ;