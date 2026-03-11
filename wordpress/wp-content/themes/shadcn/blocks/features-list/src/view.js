/**
 * Frontend script for molecule/features-list.
 * Handles accordion expand/collapse and image crossfade on button click.
 */
import './style.css';

document.addEventListener( 'DOMContentLoaded', function () {
	document.querySelectorAll( '.molecule-features-list' ).forEach( function ( block ) {
		const buttons       = Array.from( block.querySelectorAll( '.molecule-features-list__btn' ) );
		const imageWrappers = Array.from( block.querySelectorAll( '.molecule-features-list__image-wrapper' ) );

		if ( ! buttons.length ) {
			return;
		}

		function activate( activeIndex ) {
			buttons.forEach( function ( btn, i ) {
				const isActive = i === activeIndex;

				btn.classList.toggle( 'molecule-features-list__btn--active', isActive );
				btn.setAttribute( 'aria-pressed', isActive ? 'true' : 'false' );

				if ( imageWrappers[ i ] ) {
					imageWrappers[ i ].classList.toggle(
						'molecule-features-list__image-wrapper--active',
						isActive
					);
				}
			} );
		}

		buttons.forEach( function ( btn, index ) {
			btn.addEventListener( 'click', function () {
				activate( index );
			} );

			// Keyboard support: Space / Enter already fire click on <button>.
			// Add arrow key navigation between buttons.
			btn.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'ArrowDown' || e.key === 'ArrowRight' ) {
					e.preventDefault();
					const next = ( index + 1 ) % buttons.length;
					activate( next );
					buttons[ next ].focus();
				} else if ( e.key === 'ArrowUp' || e.key === 'ArrowLeft' ) {
					e.preventDefault();
					const prev = ( index - 1 + buttons.length ) % buttons.length;
					activate( prev );
					buttons[ prev ].focus();
				}
			} );
		} );
	} );
} );
