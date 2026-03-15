( function ( $ ) {
	'use strict';

	/**
	 * Resolve whether an attribute key represents "size".
	 *
	 * @param {string} key Attribute key from select name/data attribute.
	 * @returns {boolean}
	 */
	function isSizeAttribute( key ) {
		if ( ! key ) {
			return false;
		}

		var normalized = String( key ).toLowerCase();
		return normalized.indexOf( 'size' ) !== -1;
	}

	/**
	 * Sort sizes from small to large with numeric fallback.
	 *
	 * @param {string} value Option label/value.
	 * @returns {number}
	 */
	function getSizeRank( value ) {
		var key = String( value || '' )
			.toLowerCase()
			.replace( /\s+/g, '' );
		var map = {
			xxs: 1,
			xs: 2,
			s: 3,
			sm: 3,
			small: 3,
			m: 4,
			md: 4,
			medium: 4,
			l: 5,
			lg: 5,
			large: 5,
			xl: 6,
			xxl: 7,
			xxxl: 8,
		};

		if ( Object.prototype.hasOwnProperty.call( map, key ) ) {
			return map[ key ];
		}

		var numeric = parseFloat( key.replace( /[^0-9.]/g, '' ) );
		if ( ! Number.isNaN( numeric ) ) {
			return 100 + numeric;
		}

		return 1000;
	}

	function initVariableSizeSelector( form ) {
		var $form = $( form );
		if ( ! $form.length ) {
			return;
		}

		var $ui = $form.find( '[data-molecule-variable-size-selector]' ).first();
		if ( ! $ui.length ) {
			return;
		}

		var $sizeSelect = $form
			.find( 'select[name^="attribute_"]' )
			.filter( function () {
				var $select = $( this );
				var attributeName = $select.attr( 'data-attribute_name' ) || $select.attr( 'name' ) || '';
				return isSizeAttribute( attributeName );
			} )
			.first();

		if ( ! $sizeSelect.length ) {
			$ui.hide();
			return;
		}

		var $sizeRow = $sizeSelect.closest( 'tr' );
		$sizeRow.addClass( 'molecule-size-row' );
		var $topPrice = $form
			.closest( '.wp-block-add-to-cart-form' )
			.siblings( '.wp-block-woocommerce-product-price' )
			.find( '.wc-block-components-product-price' )
			.first();
		var originalTopPriceHtml = $topPrice.length ? $topPrice.html() : '';

		var $optionsHost = $ui.find( '.molecule-variable-size-selector__options' ).first();
		var optionData = [];

		$sizeSelect.find( 'option' ).each( function () {
			var value = String( $( this ).attr( 'value' ) || '' ).trim();
			var label = String( $( this ).text() || '' ).trim();

			if ( '' === value || '' === label ) {
				return;
			}

			optionData.push( {
				value: value,
				label: label,
				rank: getSizeRank( label ),
			} );
		} );

		if ( ! optionData.length ) {
			$ui.hide();
			return;
		}

		optionData.sort( function ( a, b ) {
			if ( a.rank !== b.rank ) {
				return a.rank - b.rank;
			}

			return a.label.localeCompare( b.label, undefined, { numeric: true, sensitivity: 'base' } );
		} );

		optionData.forEach( function ( option ) {
			var button = $( '<button type="button" class="molecule-variable-size-selector__option molecule-available-sizes__option"></button>' );
			button.attr( 'data-value', option.value );
			button.attr( 'aria-pressed', 'false' );
			button.text( option.label );
			$optionsHost.append( button );
		} );

		$form.addClass( 'molecule-size-enhanced' );
		$sizeRow.attr( 'hidden', true ).css( 'display', 'none' );

		// Hide the original table only when every row is transformed/hidden.
		var $table = $form.find( 'table.variations' ).first();
		var visibleRows = $table.find( 'tr' ).not( '.molecule-size-row' );
		if ( ! visibleRows.length ) {
			$form.addClass( 'molecule-hide-variations-table' );
			$table.attr( 'hidden', true ).css( 'display', 'none' );
		}

		function syncFromSelect() {
			var selectedValue = String( $sizeSelect.val() || '' );
			$optionsHost.find( '.molecule-variable-size-selector__option' ).each( function () {
				var $button = $( this );
				var isActive = String( $button.attr( 'data-value' ) ) === selectedValue;
				$button.toggleClass( 'is-active', isActive );
				$button.attr( 'aria-pressed', isActive ? 'true' : 'false' );
			} );
		}

		function resetTopPrice() {
			if ( $topPrice.length && originalTopPriceHtml ) {
				$topPrice.html( originalTopPriceHtml );
			}
		}

		function setTopPriceFromVariation( variation ) {
			if ( ! $topPrice.length || ! variation || ! variation.price_html ) {
				resetTopPrice();
				return;
			}

			$topPrice.html( String( variation.price_html ) );
		}

		$optionsHost.on( 'click', '.molecule-variable-size-selector__option', function () {
			var $button = $( this );
			var value = String( $button.attr( 'data-value' ) || '' );
			var current = String( $sizeSelect.val() || '' );
			var next = current === value ? '' : value;

			$sizeSelect.val( next ).trigger( 'change' );
			syncFromSelect();
		} );

		$sizeSelect.on( 'change', syncFromSelect );
		$form.on( 'woocommerce_update_variation_values reset_data hide_variation show_variation', syncFromSelect );
		$form.on( 'found_variation', function ( event, variation ) {
			setTopPriceFromVariation( variation );
		} );
		$form.on( 'reset_data hide_variation', function () {
			resetTopPrice();
		} );
		syncFromSelect();
	}

	$( function () {
		$( 'form.variations_form' ).each( function () {
			initVariableSizeSelector( this );
		} );
	} );
} )( jQuery );
