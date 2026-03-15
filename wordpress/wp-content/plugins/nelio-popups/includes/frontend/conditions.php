<?php

namespace Nelio_Popups\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Registers custom condition handlers for the frontend (age-gate, custom with functionName).
 * Registers frequency check via is_popup_temporarily_disabled filter.
 * Conditions are evaluated client-side via wp.hooks filters.
 */
function register_condition_handlers() {
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_condition_scripts', 20 );
}

function enqueue_condition_scripts() {
	if ( is_non_popup_preview() && ! show_popup_in_preview() ) {
		return;
	}

	if ( is_singular( 'nelio_popup' ) ) {
		return;
	}

	// Ensure our script runs after nelio-popups-public (which has wp-hooks).
	$script = get_condition_scripts();
	wp_add_inline_script(
		'nelio-popups-public',
		$script,
		'after'
	);
}

/**
 * Returns the inline JavaScript that registers age-gate, custom condition, and frequency handlers.
 *
 * @return string
 */
function get_condition_scripts() {
	$storage_key  = 'shadcnAgeGateRememberedAt';
	$session_key  = 'shadcnAgeGateAcceptedForSession';
	$expires_days = 7;
	$expires_ms   = $expires_days * 24 * 60 * 60 * 1000;

	$script = <<<JS
(function() {
	if ( typeof wp === 'undefined' || ! wp.hooks || ! wp.hooks.addFilter ) {
		return;
	}

	function moleculeAgeGatePassed() {
		try {
			if ( window.sessionStorage && window.sessionStorage.getItem( '{$session_key}' ) ) {
				return true;
			}
			if ( window.localStorage ) {
				var rememberedAt = window.localStorage.getItem( '{$storage_key}' );
				if ( ! rememberedAt ) return false;
				var ts = parseInt( rememberedAt, 10 );
				if ( isNaN( ts ) ) return false;
				return ( Date.now() - ts ) < {$expires_ms};
			}
		} catch ( e ) {}
		return false;
	}

	window.moleculeAgeGatePassed = moleculeAgeGatePassed;

	wp.hooks.addFilter(
		'nelio_popups.does_age-gate_condition_apply',
		'nelio_popups/molecule-age-gate',
		function( result, condition, context ) {
			return Promise.resolve( moleculeAgeGatePassed() );
		}
	);

	wp.hooks.addFilter(
		'nelio_popups.does_custom_condition_apply',
		'nelio_popups/molecule-custom-function',
		function( result, condition, context ) {
			var fnName = condition && condition.functionName;
			if ( ! fnName || typeof window[ fnName ] !== 'function' ) {
				return result;
			}
			try {
				return Promise.resolve( !! window[ fnName ]() );
			} catch ( e ) {
				return result;
			}
		}
	);

	wp.hooks.addFilter(
		'nelio_popups.is_popup_temporarily_disabled',
		'nelio_popups/molecule-frequency',
		function( disabled, popup ) {
			function getPopupFrequency( currentPopup ) {
				var freq = currentPopup && currentPopup.config && currentPopup.config.display && currentPopup.config.display.frequency;
				if ( freq ) {
					return freq;
				}

				try {
					var settings = window.NelioPopupsFrontendSettings;
					var popups = settings && Array.isArray( settings.popups ) ? settings.popups : [];
					var id = currentPopup && currentPopup.id;
					var rawPopup = popups.find( function( candidate ) {
						return String( candidate && candidate.id ) === String( id );
					} );
					return rawPopup && rawPopup.config && rawPopup.config.display
						? rawPopup.config.display.frequency || null
						: null;
				} catch ( e ) {}

				return null;
			}

			var freq = getPopupFrequency( popup );
			if ( ! freq || freq.audience === 'always' ) {
				return disabled;
			}
			var id = popup.id;
			var cookieName = 'nelio_popup_freq_' + id;
			var visitKey = 'nelio_popup_visit_' + id;
			try {
				if ( freq.audience === 'first-time-only' ) {
					var seen = document.cookie.indexOf( cookieName + '=' ) !== -1;
					if ( seen ) return true;
				}
				if ( freq.audience === 'returning-with-cooldown' ) {
					var match = document.cookie.match( new RegExp( cookieName + '=([^;]+)' ) );
					if ( match ) {
						var seenAt = parseInt( match[1], 10 );
						var cooldownMs = ( freq.cooldownDays || 7 ) * 24 * 60 * 60 * 1000;
						if ( ! isNaN( seenAt ) && ( Date.now() - seenAt ) < cooldownMs ) {
							return true;
						}
					}
				}
				var maxVisit = freq.maxDisplaysPerVisit;
				if ( maxVisit && maxVisit !== 'unlimited' && window.sessionStorage ) {
					var count = parseInt( sessionStorage.getItem( visitKey ) || '0', 10 );
					var maxVisitNum = parseInt( maxVisit, 10 );
					if ( ! isNaN( maxVisitNum ) && count >= maxVisitNum ) return true;
				}
			} catch ( e ) {}
			return disabled;
		}
	);

	wp.hooks.addAction(
		'nelio_popups.update_cookies',
		'nelio_popups/molecule-frequency-cookie',
		function( popup ) {
			function getPopupFrequency( currentPopup ) {
				var freq = currentPopup && currentPopup.config && currentPopup.config.display && currentPopup.config.display.frequency;
				if ( freq ) {
					return freq;
				}

				try {
					var settings = window.NelioPopupsFrontendSettings;
					var popups = settings && Array.isArray( settings.popups ) ? settings.popups : [];
					var id = currentPopup && currentPopup.id;
					var rawPopup = popups.find( function( candidate ) {
						return String( candidate && candidate.id ) === String( id );
					} );
					return rawPopup && rawPopup.config && rawPopup.config.display
						? rawPopup.config.display.frequency || null
						: null;
				} catch ( e ) {}

				return null;
			}

			var freq = getPopupFrequency( popup );
			if ( ! freq || freq.audience === 'always' ) return;
			var id = popup.id;
			var cookieName = 'nelio_popup_freq_' + id;
			try {
				var maxVisit = freq.maxDisplaysPerVisit;
				if ( maxVisit && maxVisit !== 'unlimited' && window.sessionStorage ) {
					var visitKey = 'nelio_popup_visit_' + id;
					var count = parseInt( sessionStorage.getItem( visitKey ) || '0', 10 ) + 1;
					sessionStorage.setItem( visitKey, String( count ) );
				}
				var cooldownDays = freq.cooldownDays || 7;
				var expires = new Date();
				expires.setTime( expires.getTime() + cooldownDays * 24 * 60 * 60 * 1000 );
				document.cookie = cookieName + '=' + Date.now() + ';path=/;expires=' + expires.toUTCString() + ';SameSite=Lax';
			} catch ( e ) {}
		}
	);

	// --- Premium condition handlers (Molecule Popups fork) ---
	function stringMatch( matchType, matchValue, actual ) {
		if ( ! matchValue || actual == null ) return true;
		var a = String( actual ).toLowerCase();
		var v = String( matchValue ).toLowerCase();
		switch ( matchType ) {
			case 'is': return a === v;
			case 'is-not': return a !== v;
			case 'includes': return a.indexOf( v ) !== -1;
			case 'does-not-include': return a.indexOf( v ) === -1;
			case 'regex': try { return new RegExp( matchValue ).test( actual ); } catch ( e ) { return false; }
			default: return a === v;
		}
	}

	wp.hooks.addFilter( 'nelio_popups.does_adblock-detection_condition_apply', 'molecule/adblock', function( result, condition ) {
		try {
			var el = document.createElement( 'div' );
			el.className = 'adsbox';
			el.style.cssText = 'height:2px;position:absolute;left:-9999px;';
			document.body.appendChild( el );
			var blocked = el.offsetHeight === 0 || window.getComputedStyle( el ).display === 'none';
			el.remove();
			return Promise.resolve( !! blocked );
		} catch ( e ) { return Promise.resolve( false ); }
	});

	wp.hooks.addFilter( 'nelio_popups.does_browser_condition_apply', 'molecule/browser', function( result, condition, context ) {
		var ua = navigator.userAgent || '';
		var matchValue = condition.matchValue || condition.value || '';
		if ( ! matchValue ) return Promise.resolve( true );
		var browser = /edg/i.test( ua ) ? 'edge' : /opr|opera/i.test( ua ) ? 'opera' : /chrome/i.test( ua ) ? 'chrome' : /firefox|fxios/i.test( ua ) ? 'firefox' : /safari/i.test( ua ) && !/chrome/i.test( ua ) ? 'safari' : /msie|trident/i.test( ua ) ? 'ie' : 'other';
		return Promise.resolve( stringMatch( condition.matchType || 'is', matchValue, browser ) );
	});

	wp.hooks.addFilter( 'nelio_popups.does_device_condition_apply', 'molecule/device', function( result, condition ) {
		var ua = navigator.userAgent || '';
		var w = window.innerWidth || 768;
		var matchValue = condition.matchValue || condition.value || '';
		if ( ! matchValue ) return Promise.resolve( true );
		var device = /mobile|android|iphone|ipod|blackberry|iemobile|opera mini/i.test( ua ) ? ( w >= 768 ? 'tablet' : 'mobile' ) : 'desktop';
		return Promise.resolve( stringMatch( condition.matchType || 'is', matchValue, device ) );
	});

	wp.hooks.addFilter( 'nelio_popups.does_os_condition_apply', 'molecule/os', function( result, condition ) {
		var ua = navigator.userAgent || '';
		var matchValue = condition.matchValue || condition.value || '';
		if ( ! matchValue ) return Promise.resolve( true );
		var os = /win/i.test( ua ) ? 'windows' : /mac/i.test( ua ) ? 'macos' : /linux/i.test( ua ) ? 'linux' : /android/i.test( ua ) ? 'android' : /iphone|ipad|ipod/i.test( ua ) ? 'ios' : 'other';
		return Promise.resolve( stringMatch( condition.matchType || 'is', matchValue, os ) );
	});

	wp.hooks.addFilter( 'nelio_popups.does_language_condition_apply', 'molecule/language', function( result, condition ) {
		var lang = ( navigator.language || navigator.userLanguage || '' ).split( '-' )[0];
		var matchValue = condition.matchValue || condition.value || '';
		if ( ! matchValue ) return Promise.resolve( true );
		return Promise.resolve( stringMatch( condition.matchType || 'is', matchValue, lang ) );
	});

	wp.hooks.addFilter( 'nelio_popups.does_query-arg_condition_apply', 'molecule/query-arg', function( result, condition ) {
		var key = condition.key || condition.matchValue;
		if ( ! key ) return Promise.resolve( true );
		var params = new URLSearchParams( window.location.search );
		var val = params.get( key ) || '';
		var matchValue = condition.value;
		if ( matchValue == null ) return Promise.resolve( !! val );
		return Promise.resolve( stringMatch( condition.matchType || 'is', matchValue, val ) );
	});

	wp.hooks.addFilter( 'nelio_popups.does_visitor_condition_apply', 'molecule/visitor', function( result, condition ) {
		var matchValue = ( condition.matchValue || condition.value || '' ).toLowerCase();
		if ( ! matchValue ) return Promise.resolve( true );
		try {
			var cookie = 'molecule_popup_visitor=1';
			var hasVisited = document.cookie.indexOf( 'molecule_popup_visitor' ) !== -1;
			if ( ! hasVisited ) {
				document.cookie = cookie + ';path=/;max-age=2592000;SameSite=Lax';
			}
			var isNew = ! hasVisited;
			return Promise.resolve( matchValue === 'new' ? isNew : matchValue === 'returning' ? ! isNew : true );
		} catch ( e ) { return Promise.resolve( true ); }
	});

	wp.hooks.addFilter( 'nelio_popups.does_window-width_condition_apply', 'molecule/window-width', function( result, condition ) {
		var w = window.innerWidth || 0;
		var min = condition.min != null ? condition.min : 0;
		var max = condition.max != null ? condition.max : 99999;
		return Promise.resolve( w >= min && w <= max );
	});

	wp.hooks.addFilter( 'nelio_popups.does_date_condition_apply', 'molecule/date', function( result, condition ) {
		var now = new Date();
		var start = condition.start ? new Date( condition.start ) : null;
		var end = condition.end ? new Date( condition.end ) : null;
		if ( start && now < start ) return Promise.resolve( false );
		if ( end && now > end ) return Promise.resolve( false );
		return Promise.resolve( true );
	});

	wp.hooks.addFilter( 'nelio_popups.does_day-of-week_condition_apply', 'molecule/day-of-week', function( result, condition ) {
		var day = ( new Date() ).getDay();
		var days = condition.days || condition.value;
		if ( ! days || ! Array.isArray( days ) ) return Promise.resolve( true );
		return Promise.resolve( days.indexOf( day ) !== -1 );
	});

	wp.hooks.addFilter( 'nelio_popups.does_time_condition_apply', 'molecule/time', function( result, condition ) {
		var now = new Date();
		var mins = now.getHours() * 60 + now.getMinutes();
		var start = condition.start != null ? condition.start : 0;
		var end = condition.end != null ? condition.end : 1439;
		return Promise.resolve( mins >= start && mins <= end );
	});

	wp.hooks.addFilter( 'nelio_popups.does_geolocation_condition_apply', 'molecule/geolocation', function( result, condition ) {
		var matchValue = condition.matchValue || condition.value || condition.country;
		if ( ! matchValue ) return Promise.resolve( true );
		var country = ( window.NelioPopupsFrontendSettings && NelioPopupsFrontendSettings.context && NelioPopupsFrontendSettings.context.country ) || '';
		return Promise.resolve( stringMatch( condition.matchType || 'is', matchValue, country ) );
	});

	wp.hooks.addFilter( 'nelio_popups.does_woocommerce_condition_apply', 'molecule/woocommerce', function( result, condition ) {
		if ( ! window.wc || ! window.wc.cart ) return Promise.resolve( false );
		var subType = condition.subType || condition.type;
		if ( ! subType ) return Promise.resolve( true );
		if ( subType === 'cart-total' && condition.value != null ) {
			var total = ( window.wc.cart.totals && parseFloat( window.wc.cart.totals.total ) ) || 0;
			var op = condition.operator || '>=';
			var val = parseFloat( condition.value ) || 0;
			var ok = op === '>=' ? total >= val : op === '<=' ? total <= val : op === '>' ? total > val : op === '<' ? total < val : total === val;
			return Promise.resolve( ok );
		}
		return Promise.resolve( true );
	});
})();
JS;

	return $script;
}

register_condition_handlers();
