(function () {
	var ROOT_SELECTOR =
		'.wp-block-woocommerce-checkout-order-summary-coupon-form-block';
	var CUSTOM_CLASS = 'molecule-checkout-coupon--custom';
	var initialized = false;
	var observerStarted = false;

	function getRoot() {
		return document.querySelector( ROOT_SELECTOR );
	}

	function waitForCartStore( callback ) {
		if ( typeof wp === 'undefined' || ! wp.data ) {
			window.setTimeout( function () {
				waitForCartStore( callback );
			}, 50 );
			return;
		}

		var cartStore = wp.data.select( 'wc/store/cart' );
		var cartDispatch = wp.data.dispatch( 'wc/store/cart' );

		if (
			! cartStore ||
			! cartDispatch ||
			typeof cartDispatch.applyCoupon !== 'function'
		) {
			window.setTimeout( function () {
				waitForCartStore( callback );
			}, 50 );
			return;
		}

		callback( cartStore, cartDispatch );
	}

	function hideWooCouponPanel( root ) {
		var panels = root.querySelectorAll( '.wc-block-components-totals-coupon' );

		panels.forEach( function ( panel ) {
			if ( panel.classList.contains( 'molecule-checkout-coupon__always-visible' ) ) {
				return;
			}

			panel.style.display = 'none';
			panel.setAttribute( 'aria-hidden', 'true' );
		} );
	}

	function setFormLoading( form, isLoading ) {
		var button = form.querySelector( '.wc-block-components-totals-coupon__button' );
		var input = form.querySelector( 'input[type="text"]' );

		if ( ! button || ! input ) {
			return;
		}

		button.disabled = isLoading || input.value.trim() === '';
		button.classList.toggle(
			'wc-block-components-totals-coupon__button--loading',
			isLoading
		);
		button.setAttribute( 'aria-busy', isLoading ? 'true' : 'false' );
		input.disabled = isLoading;
	}

	function buildCustomCouponForm( root, cartStore, cartDispatch ) {
		if ( root.querySelector( '.molecule-checkout-coupon__always-visible' ) ) {
			root.classList.add( CUSTOM_CLASS );
			hideWooCouponPanel( root );
			return true;
		}

		var wrapper = document.createElement( 'div' );
		wrapper.className =
			'wc-block-components-totals-coupon molecule-checkout-coupon__always-visible';

		wrapper.innerHTML =
			'<div class="wc-block-components-panel__content">' +
			'<div class="wc-block-components-totals-coupon__content">' +
			'<form class="wc-block-components-totals-coupon__form molecule-checkout-coupon__form" novalidate>' +
			'<div class="wc-block-components-text-input wc-block-components-totals-coupon__input">' +
			'<div class="wc-block-components-text-input__wrapper">' +
			'<input id="molecule-checkout-coupon-code" type="text" autocomplete="off" autocapitalize="characters" spellcheck="false" aria-label="Coupon or Affiliate Code" />' +
			'</div>' +
			'</div>' +
			'<button type="submit" class="wc-block-components-button wp-element-button wc-block-components-totals-coupon__button">' +
			'Apply' +
			'</button>' +
			'</form>' +
			'</div>' +
			'</div>';

		root.insertBefore( wrapper, root.firstChild );
		root.classList.add( CUSTOM_CLASS );
		hideWooCouponPanel( root );

		var form = wrapper.querySelector( '.molecule-checkout-coupon__form' );
		var input = wrapper.querySelector( '#molecule-checkout-coupon-code' );

		if ( ! form || ! input ) {
			return false;
		}

		input.addEventListener( 'input', function () {
			setFormLoading( form, cartStore.isApplyingCoupon() );
		} );

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();

			var code = input.value.trim();
			if ( ! code || cartStore.isApplyingCoupon() ) {
				return;
			}

			setFormLoading( form, true );

			cartDispatch.applyCoupon( code ).then( function ( success ) {
				setFormLoading( form, false );

				if ( success ) {
					input.value = '';
				}

				input.focus();
			} ).catch( function () {
				setFormLoading( form, false );
				input.focus();
			} );
		} );

		setFormLoading( form, cartStore.isApplyingCoupon() );
		return true;
	}

	function initCheckoutCouponForm() {
		var root = getRoot();
		if ( ! root ) {
			return false;
		}

		if ( initialized ) {
			hideWooCouponPanel( root );
			return true;
		}

		waitForCartStore( function ( cartStore, cartDispatch ) {
			if ( buildCustomCouponForm( root, cartStore, cartDispatch ) ) {
				initialized = true;
			}
		} );

		return initialized;
	}

	function watchCheckoutCouponForm() {
		initCheckoutCouponForm();

		if ( observerStarted ) {
			return;
		}

		observerStarted = true;

		var attempts = 0;
		var maxAttempts = 300;

		function retry() {
			if ( initCheckoutCouponForm() ) {
				return;
			}

			attempts += 1;
			if ( attempts < maxAttempts ) {
				window.setTimeout( retry, 100 );
			}
		}

		retry();

		var observer = new MutationObserver( function () {
			initCheckoutCouponForm();
		} );

		observer.observe( document.body, {
			childList: true,
			subtree: true,
		} );
	}

	function boot() {
		watchCheckoutCouponForm();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}

	window.addEventListener( 'load', initCheckoutCouponForm );
})();
