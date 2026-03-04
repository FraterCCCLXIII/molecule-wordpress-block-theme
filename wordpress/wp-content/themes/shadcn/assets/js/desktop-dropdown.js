( function() {
	function initDesktopDropdowns() {
		var dropdowns = document.querySelectorAll( '.molecule-desktop-nav-dropdown' );

		if ( ! dropdowns.length ) {
			return;
		}

		dropdowns.forEach( function( dropdown ) {
			var toggle = dropdown.querySelector( '.molecule-desktop-dropdown-toggle' );
			var panel  = dropdown.querySelector( '.molecule-desktop-dropdown-panel' );

			if ( ! toggle || ! panel ) {
				return;
			}

			function setOpen( isOpen ) {
				toggle.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
				panel.hidden = ! isOpen;
				dropdown.classList.toggle( 'is-open', isOpen );
			}

			toggle.addEventListener( 'click', function() {
				var isCurrentlyOpen = toggle.getAttribute( 'aria-expanded' ) === 'true';
				// Close all other open dropdowns first.
				dropdowns.forEach( function( other ) {
					if ( other !== dropdown ) {
						var otherToggle = other.querySelector( '.molecule-desktop-dropdown-toggle' );
						var otherPanel  = other.querySelector( '.molecule-desktop-dropdown-panel' );
						if ( otherToggle ) {
							otherToggle.setAttribute( 'aria-expanded', 'false' );
						}
						if ( otherPanel ) {
							otherPanel.hidden = true;
						}
						other.classList.remove( 'is-open' );
					}
				} );
				setOpen( ! isCurrentlyOpen );
			} );

			document.addEventListener( 'keydown', function( event ) {
				if ( 'Escape' === event.key && toggle.getAttribute( 'aria-expanded' ) === 'true' ) {
					setOpen( false );
					toggle.focus();
				}
			} );
		} );

		// Close any open dropdown when clicking outside.
		document.addEventListener( 'click', function( event ) {
			dropdowns.forEach( function( dropdown ) {
				if ( ! dropdown.contains( event.target ) ) {
					var toggle = dropdown.querySelector( '.molecule-desktop-dropdown-toggle' );
					var panel  = dropdown.querySelector( '.molecule-desktop-dropdown-panel' );
					if ( toggle ) {
						toggle.setAttribute( 'aria-expanded', 'false' );
					}
					if ( panel ) {
						panel.hidden = true;
					}
					dropdown.classList.remove( 'is-open' );
				}
			} );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initDesktopDropdowns );
	} else {
		initDesktopDropdowns();
	}
} )();
