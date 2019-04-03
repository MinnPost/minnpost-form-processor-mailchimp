( function ( $ ) {

	function wrapListFields( selector ) {
		$( '.' + selector ).wrapAll( '<tr class="minnpost-mailchimp-fields-wrap minnpost-mailchimp-fields-wrap-' + selector + '"><td colspan="2"><table />');
	}

	function groupListFields( selector ) {
		var wrapList = [];
		$( selector ).each( function () {
			var classList = $( this ).attr( 'class' ).split( /\s+/ );
			$.each( classList, function( index, item ) {
				if ( 1 === index ) {
					wrapList.push( item );
				}
			});
		});
		$.each( Array.from( new Set( wrapList ) ), function( index, item ) {
			wrapListFields( item );
		});
	}

	$( document ).ready( function () {
		if ( $( '.minnpost-form-processor-mailchimp-group' ).length > 0 ) {
			groupListFields( '.minnpost-form-processor-mailchimp-group' );
		}
	} );

})(jQuery);