( function() {
	function initMobileDrawer() {
		var openButton = document.querySelector( '.molecule-mobile-menu-button' );
		var closeButton = document.querySelector( '.molecule-mobile-drawer-close' );
		var backdrop = document.querySelector( '.molecule-mobile-drawer-backdrop' );
		var drawer = document.getElementById( 'molecule-mobile-drawer' );

		if ( ! openButton || ! closeButton || ! backdrop || ! drawer ) {
			return;
		}

		function setOpen( isOpen ) {
			openButton.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
			drawer.setAttribute( 'aria-hidden', isOpen ? 'false' : 'true' );
			backdrop.hidden = ! isOpen;
			drawer.classList.toggle( 'is-open', isOpen );
			backdrop.classList.toggle( 'is-open', isOpen );
			document.body.style.overflow = isOpen ? 'hidden' : '';
		}

		openButton.addEventListener( 'click', function() {
			setOpen( true );
		} );

		closeButton.addEventListener( 'click', function() {
			setOpen( false );
		} );

		backdrop.addEventListener( 'click', function() {
			setOpen( false );
		} );

		document.addEventListener( 'keydown', function( event ) {
			if ( 'Escape' === event.key && drawer.getAttribute( 'aria-hidden' ) === 'false' ) {
				setOpen( false );
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initMobileDrawer );
	} else {
		initMobileDrawer();
	}
} )();
