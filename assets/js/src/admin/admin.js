function showGroupSettings( selector ) {
	$( selector ).click( function() {
		$( this ).parent().toggleClass( 'minnpost-mailchimp-group-settings-visible' );
	});
}

function wrapListFields( selector ) {
	var title = selector.replace( 'minnpost-form-processor-mailchimp-group', '' );
	var title = title.split("-").join(" ").replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase()}) + ' settings';
	$( '.' + selector ).wrapAll( '<tr class="minnpost-mailchimp-fields-wrap minnpost-mailchimp-fields-wrap-' + selector + '"><td colspan="2"><table />' );
	$( '.minnpost-mailchimp-fields-wrap-' + selector + ' > td table').before( '<h4 class="minnpost-mailchimp-group-settings">' + title + '<span class="dashicons dashicons-arrow-right"></span></h4>' );
}

function groupListFields( selector ) {
	var wrapList = [];
	$( selector ).each( function() {
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

$( document ).ready( function() {
	if ( 0 < $( '.minnpost-form-processor-mailchimp-group' ).length ) {
		groupListFields( '.minnpost-form-processor-mailchimp-group' );
	}
	if ( 0 < $( '.minnpost-mailchimp-group-settings' ).length ) {
		showGroupSettings( '.minnpost-mailchimp-group-settings' );
	}
});
