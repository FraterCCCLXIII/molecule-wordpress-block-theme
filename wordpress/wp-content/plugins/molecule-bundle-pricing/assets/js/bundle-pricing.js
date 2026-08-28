/**
 * Molecule Bundle Pricing - front-end interaction.
 *
 * Builds the quantity-tier cards and drives WooCommerce's native quantity input,
 * keeping the native form as the single source of truth (validation, stock,
 * variation handling, and the server-side discount all stay intact).
 */
( function ( $ ) {
	'use strict';

	var cfg = window.mbpConfig || {};

	/**
	 * Format a numeric amount using WooCommerce's currency settings.
	 *
	 * @param {number} amount Raw amount.
	 * @returns {string} HTML price string.
	 */
	function formatPrice( amount ) {
		var pf = cfg.priceFormat || {};
		var decimals = typeof pf.decimals === 'number' ? pf.decimals : 2;
		var decSep = pf.decimalSeparator || '.';
		var thoSep = pf.thousandSeparator || ',';
		var symbol = pf.symbol || '$';
		var format = pf.priceFormat || '%1$s%2$s';

		var value = Math.abs( Number( amount ) || 0 ).toFixed( decimals );
		var parts = value.split( '.' );
		parts[ 0 ] = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, thoSep );
		var number = parts.length > 1 ? parts.join( decSep ) : parts[ 0 ];

		return format.replace( '%1$s', symbol ).replace( '%2$s', number );
	}

	/**
	 * Create a text node element to avoid any HTML injection from labels.
	 *
	 * @param {string} tag       Tag name.
	 * @param {string} className Class name.
	 * @param {string} text      Text content.
	 * @returns {jQuery}
	 */
	function el( tag, className, text ) {
		var $node = $( document.createElement( tag ) ).addClass( className );
		if ( typeof text === 'string' ) {
			$node.text( text );
		}
		return $node;
	}

	/**
	 * Bundle tier selector controller.
	 *
	 * @param {HTMLElement} root Container element.
	 * @constructor
	 */
	function BundleSelector( root ) {
		this.$root = $( root );

		var config = {};
		try {
			config = JSON.parse( this.$root.attr( 'data-mbp-config' ) || '{}' );
		} catch ( e ) {
			config = {};
		}

		this.config = config;
		this.tiers = Array.isArray( config.tiers ) ? config.tiers : [];
		this.variations = config.variations || {};
		this.isVariable = config.productType === 'variable';
		this.unitPrice = this.isVariable ? null : Number( config.basePrice ) || 0;

		this.$list = this.$root.find( '.molecule-bundle-tiers__list' ).first();
		this.$form = this.$root.closest( 'form.cart' );
		if ( ! this.$form.length ) {
			this.$form = this.$root.closest( 'form' );
		}

		if ( ! this.tiers.length || ! this.$list.length ) {
			return;
		}

		this.$form.addClass( 'mbp-has-bundle-tiers' );
		this.buildCards( this.tiers );
		this.bindFormEvents();
		this.syncFromQty();
		this.updatePrices();
	}

	/**
	 * Locate the native WooCommerce quantity input within the form.
	 *
	 * @returns {jQuery}
	 */
	BundleSelector.prototype.getQtyInput = function () {
		return this.$form.find( 'input.qty' ).first();
	};

	/**
	 * Read the current quantity from the native input.
	 *
	 * @returns {number}
	 */
	BundleSelector.prototype.getQty = function () {
		var $qty = this.getQtyInput();
		var value = parseInt( $qty.val(), 10 );
		return isNaN( value ) ? 1 : value;
	};

	/**
	 * Write a quantity into the native input (clamped to its min/max) and notify WooCommerce.
	 *
	 * @param {number} quantity Desired quantity.
	 */
	BundleSelector.prototype.setQty = function ( quantity ) {
		var $qty = this.getQtyInput();
		if ( ! $qty.length ) {
			return;
		}

		var min = parseInt( $qty.attr( 'min' ), 10 );
		var max = parseInt( $qty.attr( 'max' ), 10 );

		if ( ! isNaN( min ) && quantity < min ) {
			quantity = min;
		}
		if ( ! isNaN( max ) && max > 0 && quantity > max ) {
			quantity = max;
		}

		$qty.val( quantity ).trigger( 'change' );
	};

	/**
	 * Render the tier cards for a given tier set.
	 *
	 * @param {Array} tiers Tier definitions.
	 */
	BundleSelector.prototype.buildCards = function ( tiers ) {
		var self = this;
		this.tiers = tiers;
		this.$list.empty();

		tiers.forEach( function ( tier, index ) {
			var $card = el( 'div', 'molecule-bundle-tier' )
				.attr( {
					role: 'radio',
					'aria-checked': 'false',
					tabindex: index === 0 ? '0' : '-1',
					'data-quantity': tier.quantity,
					'data-discount': tier.discount,
					'data-open-ended': tier.openEnded ? '1' : '0'
				} );

			if ( tier.openEnded ) {
				$card.addClass( 'molecule-bundle-tier--open-ended' );
			}

			if ( tier.badge ) {
				$card.append( el( 'span', 'molecule-bundle-tier__badge', tier.badge ) );
			}

			$card.append( el( 'span', 'molecule-bundle-tier__indicator' ).attr( 'aria-hidden', 'true' ) );
			$card.append( el( 'span', 'molecule-bundle-tier__label', tier.label ) );

			if ( tier.sublabel ) {
				$card.append( el( 'span', 'molecule-bundle-tier__sublabel', tier.sublabel ) );
			}

			$card.append( el( 'span', 'molecule-bundle-tier__price' ) );
			$card.append( el( 'span', 'molecule-bundle-tier__price-was' ) );

			if ( tier.openEnded ) {
				$card.append( self.buildStepper() );
			}

			self.$list.append( $card );
		} );

		this.bindCardEvents();
	};

	/**
	 * Build the quantity stepper used inside the open-ended card.
	 *
	 * @returns {jQuery}
	 */
	BundleSelector.prototype.buildStepper = function () {
		var i18n = cfg.i18n || {};
		var $stepper = el( 'div', 'molecule-bundle-tier__stepper' ).attr( 'hidden', 'hidden' );

		var $minus = el( 'button', 'molecule-bundle-tier__step', '\u2212' )
			.attr( { type: 'button', 'data-step': '-1', 'aria-label': i18n.decrease || 'Decrease quantity' } );
		var $value = el( 'span', 'molecule-bundle-tier__qty' ).attr( 'aria-live', 'polite' );
		var $plus = el( 'button', 'molecule-bundle-tier__step', '+' )
			.attr( { type: 'button', 'data-step': '1', 'aria-label': i18n.increase || 'Increase quantity' } );

		return $stepper.append( $minus, $value, $plus );
	};

	/**
	 * Bind click + keyboard interactions on the cards.
	 */
	BundleSelector.prototype.bindCardEvents = function () {
		var self = this;

		this.$list.off( '.mbp' );

		this.$list.on( 'click.mbp', '.molecule-bundle-tier', function () {
			self.selectCard( $( this ) );
		} );

		this.$list.on( 'keydown.mbp', '.molecule-bundle-tier', function ( event ) {
			var key = event.key;
			if ( key === 'Enter' || key === ' ' || key === 'Spacebar' ) {
				event.preventDefault();
				self.selectCard( $( this ) );
				return;
			}

			var $cards = self.$list.find( '.molecule-bundle-tier' );
			var idx = $cards.index( this );
			var next = null;

			if ( key === 'ArrowRight' || key === 'ArrowDown' ) {
				next = $cards.eq( ( idx + 1 ) % $cards.length );
			} else if ( key === 'ArrowLeft' || key === 'ArrowUp' ) {
				next = $cards.eq( ( idx - 1 + $cards.length ) % $cards.length );
			}

			if ( next && next.length ) {
				event.preventDefault();
				next.trigger( 'focus' );
				self.selectCard( next );
			}
		} );

		// Stepper controls drive the native qty input without re-triggering card selection.
		this.$list.on( 'click.mbp', '.molecule-bundle-tier__step', function ( event ) {
			event.stopPropagation();
			var step = parseInt( $( this ).attr( 'data-step' ), 10 ) || 0;
			var $card = $( this ).closest( '.molecule-bundle-tier' );
			var threshold = parseInt( $card.attr( 'data-quantity' ), 10 ) || 1;
			var nextQty = self.getQty() + step;
			if ( nextQty < threshold ) {
				nextQty = threshold;
			}
			self.setQty( nextQty );
			self.syncFromQty();
			self.updatePrices();
		} );
	};

	/**
	 * Bind to WooCommerce form lifecycle events.
	 */
	BundleSelector.prototype.bindFormEvents = function () {
		var self = this;

		var $qty = this.getQtyInput();
		if ( $qty.length ) {
			$qty.on( 'change.mbp input.mbp', function () {
				self.syncFromQty();
				self.updatePrices();
			} );
		}

		if ( this.isVariable ) {
			this.$form.on( 'found_variation.mbp', function ( event, variation ) {
				self.onFoundVariation( variation );
			} );
			this.$form.on( 'reset_data.mbp hide_variation.mbp', function () {
				self.onResetVariation();
			} );
		}
	};

	/**
	 * Handle a resolved variation: swap tiers/price for that variation.
	 *
	 * @param {Object} variation WooCommerce variation payload.
	 */
	BundleSelector.prototype.onFoundVariation = function ( variation ) {
		if ( ! variation || ! variation.variation_id ) {
			return;
		}

		var data = this.variations[ variation.variation_id ];
		if ( data ) {
			this.unitPrice = Number( data.price ) || 0;
			if ( Array.isArray( data.tiers ) && data.tiers.length ) {
				this.buildCards( data.tiers );
			}
		} else {
			this.unitPrice = Number( variation.display_price ) || 0;
		}

		this.$root.removeClass( 'molecule-bundle-tiers--pending' );
		this.syncFromQty();
		this.updatePrices();
	};

	/**
	 * Handle variation reset (no selection): blank the prices.
	 */
	BundleSelector.prototype.onResetVariation = function () {
		this.unitPrice = null;
		this.$root.addClass( 'molecule-bundle-tiers--pending' );
		this.updatePrices();
	};

	/**
	 * Select a card: set the native quantity and refresh state.
	 *
	 * @param {jQuery} $card Target card.
	 */
	BundleSelector.prototype.selectCard = function ( $card ) {
		if ( ! $card || ! $card.length ) {
			return;
		}

		var quantity = parseInt( $card.attr( 'data-quantity' ), 10 ) || 1;
		this.setQty( quantity );
		this.syncFromQty();
		this.updatePrices();
	};

	/**
	 * Mark the active card based on the current native quantity (highest threshold <= qty).
	 */
	BundleSelector.prototype.syncFromQty = function () {
		var qty = this.getQty();
		var $cards = this.$list.find( '.molecule-bundle-tier' );
		var activeIndex = -1;

		$cards.each( function ( index ) {
			var threshold = parseInt( $( this ).attr( 'data-quantity' ), 10 ) || 1;
			if ( qty >= threshold ) {
				activeIndex = index;
			}
		} );

		if ( activeIndex === -1 ) {
			activeIndex = 0;
		}

		$cards.each( function ( index ) {
			var $card = $( this );
			var isActive = index === activeIndex;
			$card.toggleClass( 'molecule-bundle-tier--active', isActive );
			$card.attr( 'aria-checked', isActive ? 'true' : 'false' );
			$card.attr( 'tabindex', isActive ? '0' : '-1' );

			var $stepper = $card.find( '.molecule-bundle-tier__stepper' );
			if ( $stepper.length ) {
				var isOpenEnded = $card.attr( 'data-open-ended' ) === '1';
				if ( isActive && isOpenEnded ) {
					$stepper.removeAttr( 'hidden' );
					$stepper.find( '.molecule-bundle-tier__qty' ).text( qty );
				} else {
					$stepper.attr( 'hidden', 'hidden' );
				}
			}
		} );
	};

	/**
	 * Recompute and render the price (and strikethrough) on each card.
	 */
	BundleSelector.prototype.updatePrices = function () {
		var unit = this.unitPrice;
		var qty = this.getQty();

		this.$list.find( '.molecule-bundle-tier' ).each( function () {
			var $card = $( this );
			var $price = $card.find( '.molecule-bundle-tier__price' );
			var $was = $card.find( '.molecule-bundle-tier__price-was' );

			if ( unit === null || isNaN( unit ) ) {
				$price.html( '' );
				$was.html( '' );
				return;
			}

			var threshold = parseInt( $card.attr( 'data-quantity' ), 10 ) || 1;
			var discount = parseFloat( $card.attr( 'data-discount' ) ) || 0;
			var isOpenEnded = $card.attr( 'data-open-ended' ) === '1';
			var isActive = $card.hasClass( 'molecule-bundle-tier--active' );

			// The open-ended active card reflects the live quantity; others use their threshold.
			var effectiveQty = isOpenEnded && isActive ? Math.max( threshold, qty ) : threshold;

			var full = unit * effectiveQty;
			var net = full * ( 1 - ( discount / 100 ) );

			$price.html( formatPrice( net ) );

			if ( discount > 0 ) {
				$was.html( formatPrice( full ) ).show();
			} else {
				$was.html( '' );
			}
		} );
	};

	$( function () {
		$( '[data-molecule-bundle-tiers]' ).each( function () {
			new BundleSelector( this );
		} );
	} );
} )( jQuery );
