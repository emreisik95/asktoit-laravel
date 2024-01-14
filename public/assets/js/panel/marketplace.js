$( document ).ready( function () {
	"use strict";

	var addonFilter = "All";
    $( ".addons_filter" ).on( "click", function () {
		$( '.addons_filter' ).removeClass( 'active' );
		$( this ).addClass( 'active' );
		var filter = $( this ).attr( "data-filter" );
		addonFilter = filter;
		//updateChatsByFilter();
	} );


    


} );

