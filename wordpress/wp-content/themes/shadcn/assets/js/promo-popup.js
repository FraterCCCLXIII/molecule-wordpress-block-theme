( function() {
	function getConfig() {
		var fallback = {
			storageKey: 'shadcnAgeGateRememberedAt',
			sessionKey: 'shadcnAgeGateAcceptedForSession',
			expiresDays: 7,
			showDelayMs: 600,
			declineUrl: 'https://www.google.com',
		};
		var config = window.shadcnPromoPopup || {};

		return {
			storageKey: config.storageKey || fallback.storageKey,
			sessionKey: config.sessionKey || fallback.sessionKey,
			expiresDays: Number( config.expiresDays || fallback.expiresDays ),
			showDelayMs: Number( config.showDelayMs || fallback.showDelayMs ),
			declineUrl: config.declineUrl || fallback.declineUrl,
		};
	}

	function supportsLocalStorage() {
		try {
			var key = '__molecule_popup_test__';
			window.localStorage.setItem( key, '1' );
			window.localStorage.removeItem( key );
			return true;
		} catch ( error ) {
			return false;
		}
	}

	function isDismissed( config ) {
		if ( window.sessionStorage && window.sessionStorage.getItem( config.sessionKey ) ) {
			return true;
		}

		if ( ! supportsLocalStorage() ) {
			return false;
		}

		var dismissedAt = window.localStorage.getItem( config.storageKey );
		if ( ! dismissedAt ) {
			return false;
		}

		var dismissedAtMs = Number( dismissedAt );
		if ( Number.isNaN( dismissedAtMs ) ) {
			return false;
		}

		var expiryMs = config.expiresDays * 24 * 60 * 60 * 1000;
		return Date.now() - dismissedAtMs < expiryMs;
	}

	function persistDismissed( config ) {
		try {
			window.sessionStorage.setItem( config.sessionKey, '1' );
		} catch ( error ) {
			// Ignore session storage failures.
		}
	}

	function persistRemembered( config ) {
		if ( ! supportsLocalStorage() ) {
			return;
		}

		try {
			window.localStorage.setItem( config.storageKey, String( Date.now() ) );
		} catch ( error ) {
			// Ignore storage failures (privacy mode, full quota, etc.).
		}
	}

	function getFocusableElements( container ) {
		return container.querySelectorAll(
			'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])'
		);
	}

	function initPromoPopup() {
		var config = getConfig();
		var root = document.querySelector( '[data-promo-popup]' );

		if ( ! root || isDismissed( config ) ) {
			return;
		}

		var dialog = root.querySelector( '.molecule-promo-popup__dialog' );
		var acceptButton = root.querySelector( '[data-promo-popup-accept]' );
		var declineButton = root.querySelector( '[data-promo-popup-decline]' );
		var rememberInput = root.querySelector( '[data-promo-popup-remember]' );
		var isOpen = false;
		var lastFocusedElement = null;

		if ( ! dialog || ! acceptButton || ! declineButton || ! rememberInput ) {
			return;
		}

		function openPopup() {
			if ( isOpen ) {
				return;
			}

			lastFocusedElement = document.activeElement;
			root.hidden = false;
			document.body.classList.add( 'molecule-promo-popup-open' );
			isOpen = true;

			var focusable = getFocusableElements( dialog );
			if ( focusable.length ) {
				focusable[ 0 ].focus();
			} else {
				dialog.focus();
			}
		}

		function closePopup() {
			if ( ! isOpen ) {
				return;
			}

			root.hidden = true;
			document.body.classList.remove( 'molecule-promo-popup-open' );
			isOpen = false;
			persistDismissed( config );

			if ( lastFocusedElement && 'function' === typeof lastFocusedElement.focus ) {
				lastFocusedElement.focus();
			}
		}

		acceptButton.addEventListener( 'click', function() {
			if ( rememberInput.checked ) {
				persistRemembered( config );
			}

			closePopup();
		} );

		declineButton.addEventListener( 'click', function() {
			if ( config.declineUrl ) {
				window.location.assign( config.declineUrl );
				return;
			}

			window.history.back();
		} );

		document.addEventListener( 'keydown', function( event ) {
			if ( ! isOpen ) {
				return;
			}

			if ( 'Escape' === event.key ) {
				event.preventDefault();
				return;
			}

			if ( 'Tab' !== event.key ) {
				return;
			}

			var focusable = getFocusableElements( dialog );
			if ( ! focusable.length ) {
				event.preventDefault();
				return;
			}

			var first = focusable[ 0 ];
			var last = focusable[ focusable.length - 1 ];

			if ( event.shiftKey && document.activeElement === first ) {
				event.preventDefault();
				last.focus();
			} else if ( ! event.shiftKey && document.activeElement === last ) {
				event.preventDefault();
				first.focus();
			}
		} );

		window.setTimeout( openPopup, Math.max( 0, config.showDelayMs ) );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initPromoPopup );
	} else {
		initPromoPopup();
	}
} )();
