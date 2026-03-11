/**
 * Frontend script for molecule/product-carousel.
 * Wires the left/right scroll buttons to the scrollable product track.
 */
import './style.css';

document.addEventListener( 'DOMContentLoaded', function () {
	document.querySelectorAll( '.molecule-product-carousel' ).forEach( function ( carousel ) {
		const track   = carousel.querySelector( '.molecule-product-carousel__track' );
		const btnLeft = carousel.querySelector( '.molecule-product-carousel__btn--left' );
		const btnRight = carousel.querySelector( '.molecule-product-carousel__btn--right' );

		if ( ! track ) {
			return;
		}

		if ( btnLeft ) {
			btnLeft.addEventListener( 'click', function () {
				track.scrollBy( { left: -400, behavior: 'smooth' } );
			} );
		}

		if ( btnRight ) {
			btnRight.addEventListener( 'click', function () {
				track.scrollBy( { left: 400, behavior: 'smooth' } );
			} );
		}
	} );
} );
