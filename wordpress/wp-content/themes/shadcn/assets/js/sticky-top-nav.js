( function() {
	let lastKnownAnnouncementHidden = false;

	function getAnnouncementReserveHeight( nav, announcement ) {
		const cached = parseInt( nav.dataset.moleculeAnnouncementReserveHeight || '0', 10 );
		if ( cached > 0 ) {
			return cached;
		}

		const wasHidden = nav.classList.contains( 'is-announcement-hidden' );
		if ( wasHidden ) {
			nav.classList.remove( 'is-announcement-hidden' );
		}

		const measuredHeight = Math.ceil( announcement.getBoundingClientRect().height );
		nav.dataset.moleculeAnnouncementReserveHeight = String( Math.max( measuredHeight, 0 ) );

		if ( wasHidden ) {
			nav.classList.add( 'is-announcement-hidden' );
		}

		return measuredHeight;
	}

	function clearAnnouncementHeightCache() {
		const nav = document.querySelector( '.molecule-top-nav' );
		if ( ! nav ) {
			return;
		}

		delete nav.dataset.moleculeAnnouncementReserveHeight;
	}

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

		const announcement = nav.querySelector( '.molecule-top-nav-announcement' );
		const reserveAnnouncementHeight = announcement
			? getAnnouncementReserveHeight( nav, announcement )
			: 0;
		const navHeight = Math.ceil( nav.getBoundingClientRect().height );
		const isAnnouncementHidden = nav.classList.contains( 'is-announcement-hidden' );
		const effectiveNavHeight = isAnnouncementHidden
			? navHeight + reserveAnnouncementHeight
			: navHeight;

		document.documentElement.style.setProperty(
			'--molecule-top-nav-offset',
			effectiveNavHeight + adminOffset + 'px'
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
		const hideAfter = Math.max( 8, getAnnouncementReserveHeight( nav, announcement ) );
		const shouldHide = currentScrollY > hideAfter;

		if ( shouldHide ) {
			nav.classList.add( 'is-announcement-hidden' );
		} else {
			nav.classList.remove( 'is-announcement-hidden' );
		}

		const isHidden = nav.classList.contains( 'is-announcement-hidden' );
		if ( isHidden !== lastKnownAnnouncementHidden ) {
			lastKnownAnnouncementHidden = isHidden;
			window.setTimeout( syncStickyTopNav, 170 );
		}

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
	window.addEventListener(
		'resize',
		function() {
			clearAnnouncementHeightCache();
			rafSync();
		},
		{ passive: true }
	);
	window.addEventListener( 'scroll', syncAnnouncementScrollState, { passive: true } );
} )();
