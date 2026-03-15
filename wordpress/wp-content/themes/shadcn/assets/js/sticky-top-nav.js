( function() {
	let lastScrollY = 0;
	let lastKnownAnnouncementHidden = false;

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

	function syncAnnouncementScrollState() {
		const nav = document.querySelector( '.molecule-top-nav' );
		if ( ! nav ) {
			return;
		}

		const announcement = nav.querySelector( '.molecule-top-nav-announcement' );
		if ( ! announcement ) {
			return;
		}

		const currentScrollY = window.scrollY || 0;
		const delta = currentScrollY - lastScrollY;
		const minDelta = 6;
		const shouldReveal = currentScrollY <= 8 || delta < -minDelta;
		const shouldHide = currentScrollY > 8 && delta > minDelta;

		if ( shouldReveal ) {
			nav.classList.remove( 'is-announcement-hidden' );
		} else if ( shouldHide ) {
			nav.classList.add( 'is-announcement-hidden' );
		}

		const isHidden = nav.classList.contains( 'is-announcement-hidden' );
		if ( isHidden !== lastKnownAnnouncementHidden ) {
			lastKnownAnnouncementHidden = isHidden;
			window.setTimeout( syncStickyTopNav, 170 );
		}

		lastScrollY = currentScrollY;
	}

	const rafSync = function() {
		window.requestAnimationFrame( function() {
			syncStickyTopNav();
			syncAnnouncementScrollState();
		} );
	};

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', rafSync );
	} else {
		rafSync();
	}

	window.addEventListener( 'load', rafSync );
	window.addEventListener( 'resize', rafSync, { passive: true } );
	window.addEventListener( 'scroll', syncAnnouncementScrollState, { passive: true } );
} )();
