/**
 * Shop catalog: toggle ?filter_stock_status=instock for WooCommerce block product collection.
 */
(function () {
	document.addEventListener( 'change', function ( event ) {
		const input = event.target;
		if (
			! ( input instanceof HTMLInputElement ) ||
			! input.classList.contains( 'molecule-in-stock-only-switch' )
		) {
			return;
		}

		const url = new URL( window.location.href );
		const key = 'filter_stock_status';

		if ( input.checked ) {
			url.searchParams.set( key, 'instock' );
		} else {
			url.searchParams.delete( key );
		}

		window.location.assign( url.toString() );
	} );
} )();
