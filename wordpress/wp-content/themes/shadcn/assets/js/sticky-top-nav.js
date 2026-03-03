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

	function initLegacyDesktopDropdown() {
		const dropdowns = document.querySelectorAll( '.molecule-desktop-dropdown' );

		dropdowns.forEach( function( dropdown ) {
			const toggle = dropdown.querySelector( '.molecule-desktop-dropdown-toggle' );
			const menu = dropdown.querySelector( '.molecule-desktop-dropdown-menu' );
			let closeTimer = null;

			if ( ! toggle || ! menu ) {
				return;
			}

			function setOpen( isOpen ) {
				toggle.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
				menu.hidden = ! isOpen;
			}

			function clearCloseTimer() {
				if ( closeTimer ) {
					window.clearTimeout( closeTimer );
					closeTimer = null;
				}
			}

			function queueClose() {
				clearCloseTimer();
				closeTimer = window.setTimeout( function() {
					setOpen( false );
				}, 120 );
			}

			setOpen( false );

			toggle.addEventListener( 'click', function( event ) {
				event.preventDefault();
				const isOpen = toggle.getAttribute( 'aria-expanded' ) === 'true';
				setOpen( ! isOpen );
			} );

			dropdown.addEventListener( 'mouseenter', clearCloseTimer );
			menu.addEventListener( 'mouseenter', clearCloseTimer );
			dropdown.addEventListener( 'mouseleave', queueClose );

			dropdown.addEventListener( 'focusout', function( event ) {
				const nextTarget = event.relatedTarget;

				if ( nextTarget && dropdown.contains( nextTarget ) ) {
					return;
				}

				setOpen( false );
			} );

			dropdown.addEventListener( 'keydown', function( event ) {
				if ( 'Escape' === event.key ) {
					setOpen( false );
					toggle.focus();
				}
			} );

			document.addEventListener( 'click', function( event ) {
				if ( ! dropdown.contains( event.target ) ) {
					setOpen( false );
				}
			} );
		} );
	}

	const rafSync = function() {
		window.requestAnimationFrame( syncStickyTopNav );
	};

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', rafSync );
		document.addEventListener( 'DOMContentLoaded', initLegacyDesktopDropdown );
	} else {
		rafSync();
		initLegacyDesktopDropdown();
	}

	window.addEventListener( 'load', rafSync );
	window.addEventListener( 'resize', rafSync, { passive: true } );
} )();
