( function() {
	function initSearchModal() {
		var modal = document.querySelector( '[data-search-modal]' );
		var triggerForms = document.querySelectorAll(
			'.molecule-header-search form, .molecule-top-nav .wp-block-search form'
		);
		var triggerButtons = document.querySelectorAll(
			'.molecule-header-search .wp-block-search__button, .molecule-top-nav .wp-block-search__button'
		);
		var triggerLinks = document.querySelectorAll(
			'.molecule-top-nav a.molecule-icon-link[aria-label="Search"], .molecule-top-nav a[href*="?s="][aria-label="Search"]'
		);
		if (
			! modal ||
			( ! triggerButtons.length && ! triggerForms.length && ! triggerLinks.length )
		) {
			return;
		}

		var config = window.shadcnSearchModal || {};
		var minQueryChars = parseInt( config.minQueryChars || '2', 10 );
		var labels = config.labels || {};
		var input = modal.querySelector( '[data-search-modal-input]' );
		var resultsContainer = modal.querySelector( '[data-search-modal-results]' );
		var closeButtons = modal.querySelectorAll( '[data-search-modal-close]' );
		var isOpen = false;
		var lastFocusedElement = null;
		var activeController = null;
		var debounceTimer = 0;

		if ( ! input || ! resultsContainer ) {
			return;
		}

		function escapeHtml( value ) {
			return String( value )
				.replace( /&/g, '&amp;' )
				.replace( /</g, '&lt;' )
				.replace( />/g, '&gt;' )
				.replace( /"/g, '&quot;' )
				.replace( /'/g, '&#039;' );
		}

		function setStateMessage( message ) {
			resultsContainer.innerHTML =
				'<div class="molecule-search-modal__state">' + escapeHtml( message ) + '</div>';
		}

		function renderResults( results ) {
			if ( ! results.length ) {
				setStateMessage( labels.noResults || 'No results found.' );
				return;
			}

			var html =
				'<div class="molecule-search-modal__list" role="list">' +
				results
					.map( function( result ) {
						var imageHtml = result.image
							? '<img src="' +
							  escapeHtml( result.image ) +
							  '" alt="' +
							  escapeHtml( result.title ) +
							  '" loading="lazy" />'
							: '<span class="molecule-search-modal__thumb-fallback" aria-hidden="true"></span>';
						var excerptHtml = result.excerpt
							? '<p class="molecule-search-modal__item-excerpt">' +
							  escapeHtml( result.excerpt ) +
							  '</p>'
							: '';

						return (
							'<a class="molecule-search-modal__item" role="listitem" href="' +
							escapeHtml( result.url ) +
							'">' +
							'<span class="molecule-search-modal__thumb">' +
							imageHtml +
							'</span>' +
							'<span class="molecule-search-modal__item-content">' +
							'<span class="molecule-search-modal__item-meta">' +
							'<span class="molecule-search-modal__badge">' +
							escapeHtml( result.type ) +
							'</span>' +
							'<span class="molecule-search-modal__item-title">' +
							escapeHtml( result.title ) +
							'</span>' +
							'</span>' +
							excerptHtml +
							'</span>' +
							'</a>'
						);
					} )
					.join( '' ) +
				'</div>';

			resultsContainer.innerHTML = html;
		}

		function search( query ) {
			if ( ! config.endpoint ) {
				return;
			}

			if ( activeController ) {
				activeController.abort();
			}

			activeController = new AbortController();
			fetch(
				config.endpoint + '?q=' + encodeURIComponent( query ),
				{
					method: 'GET',
					credentials: 'same-origin',
					signal: activeController.signal,
				}
			)
				.then( function( response ) {
					if ( ! response.ok ) {
						throw new Error( 'Search request failed.' );
					}

					return response.json();
				} )
				.then( function( payload ) {
					var results = payload && Array.isArray( payload.results ) ? payload.results : [];
					renderResults( results );
				} )
				.catch( function( error ) {
					if ( error && 'AbortError' === error.name ) {
						return;
					}

					setStateMessage( labels.noResults || 'No results found.' );
				} );
		}

		function closeModal() {
			if ( ! isOpen ) {
				return;
			}

			modal.classList.remove( 'is-open' );
			modal.hidden = true;
			document.body.classList.remove( 'molecule-search-modal-open' );
			isOpen = false;

			if ( activeController ) {
				activeController.abort();
				activeController = null;
			}

			window.clearTimeout( debounceTimer );
			input.value = '';
			setStateMessage( labels.startTyping || 'Start typing to search...' );

			if ( lastFocusedElement && 'function' === typeof lastFocusedElement.focus ) {
				lastFocusedElement.focus();
			}
		}

		function openModal( event ) {
			if ( event ) {
				event.preventDefault();
			}

			if ( isOpen ) {
				return;
			}

			lastFocusedElement = document.activeElement;
			modal.hidden = false;
			modal.classList.add( 'is-open' );
			document.body.classList.add( 'molecule-search-modal-open' );
			isOpen = true;
			input.focus();
		}

		function handleInput() {
			window.clearTimeout( debounceTimer );

			var query = input.value.trim();
			if ( query.length < minQueryChars ) {
				setStateMessage( labels.startTyping || 'Start typing to search...' );
				return;
			}

			setStateMessage( 'Searching...' );
			debounceTimer = window.setTimeout( function() {
				search( query );
			}, 180 );
		}

		triggerButtons.forEach( function( button ) {
			button.addEventListener( 'click', openModal );
		} );

		triggerForms.forEach( function( form ) {
			form.addEventListener(
				'submit',
				function( event ) {
					event.preventDefault();
					openModal( event );
				},
				true
			);
		} );

		triggerLinks.forEach( function( link ) {
			link.addEventListener( 'click', openModal );
		} );

		closeButtons.forEach( function( button ) {
			button.addEventListener( 'click', closeModal );
		} );

		input.addEventListener( 'input', handleInput );

		document.addEventListener(
			'click',
			function( event ) {
				var link = event.target;
				if ( ! link || ! link.closest ) {
					return;
				}

				link = link.closest( '.molecule-top-nav a' );
				if ( ! link ) {
					return;
				}

				var isSearchLink =
					link.classList.contains( 'molecule-icon-link' ) &&
					link.getAttribute( 'aria-label' ) === 'Search';
				var href = link.getAttribute( 'href' ) || '';
				var isSearchHref = href.indexOf( '?s=' ) !== -1;

				if ( ! isSearchLink && ! isSearchHref ) {
					return;
				}

				event.preventDefault();
				openModal( event );
			},
			true
		);

		document.addEventListener(
			'submit',
			function( event ) {
				var form = event.target;
				if ( ! form || ! form.closest ) {
					return;
				}

				if ( ! form.closest( '.molecule-top-nav .wp-block-search' ) ) {
					return;
				}

				event.preventDefault();
				openModal( event );
			},
			true
		);

		document.addEventListener( 'keydown', function( event ) {
			if ( ! isOpen ) {
				return;
			}

			if ( 'Escape' === event.key ) {
				event.preventDefault();
				closeModal();
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initSearchModal );
	} else {
		initSearchModal();
	}
} )();
