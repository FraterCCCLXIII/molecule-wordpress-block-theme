( function() {
	function getAdminOffset( bodyEl ) {
		if ( ! bodyEl.classList.contains( 'admin-bar' ) ) {
			return 0;
		}

		return window.matchMedia( '(max-width: 782px)' ).matches ? 46 : 32;
	}

	function syncStickyTopNav() {
		const body = document.body;
		const nav = document.querySelector( '.molecule-top-nav' );

		if ( ! body || ! nav ) {
			document.documentElement.style.setProperty( '--molecule-top-nav-offset', '0px' );
			return;
		}

		const adminOffset = getAdminOffset( body );

		nav.style.setProperty( 'position', 'fixed', 'important' );
		nav.style.setProperty( 'top', adminOffset + 'px', 'important' );
		nav.style.setProperty( 'left', '0', 'important' );
		nav.style.setProperty( 'right', '0', 'important' );
		nav.style.setProperty( 'width', '100%', 'important' );
		nav.style.setProperty( 'z-index', '1000', 'important' );

		const navHeight = Math.ceil( nav.getBoundingClientRect().height );
		document.documentElement.style.setProperty(
			'--molecule-top-nav-offset',
			navHeight + adminOffset + 'px'
		);
	}

	const rafSync = function() {
		window.requestAnimationFrame( syncStickyTopNav );
	};

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', rafSync );
	} else {
		rafSync();
	}

	window.addEventListener( 'load', rafSync );
	window.addEventListener( 'resize', rafSync, { passive: true } );
} )();
