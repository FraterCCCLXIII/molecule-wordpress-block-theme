( function() {
	if (
		typeof window.wp === 'undefined' ||
		! window.wp.plugins ||
		! window.wp.editPost ||
		! window.wp.data ||
		! window.wp.components ||
		! window.wp.element ||
		! window.wp.i18n
	) {
		return;
	}

	var registerPlugin = window.wp.plugins.registerPlugin;
	var PluginDocumentSettingPanel = window.wp.editPost.PluginDocumentSettingPanel;
	var components = window.wp.components;
	var createElement = window.wp.element.createElement;
	var Fragment = window.wp.element.Fragment;
	var useSelect = window.wp.data.useSelect;
	var useDispatch = window.wp.data.useDispatch;
	var __ = window.wp.i18n.__;

	var SelectControl = components.SelectControl;
	var TextControl = components.TextControl;
	var ToggleControl = components.ToggleControl;
	var RangeControl = components.RangeControl;

	function getDefaultDisplay() {
		return {
			disablesOtherPopupOpenings: true,
			isDisabledIfOpenedPopups: false,
			isDisabledOnMobile: false,
			isLocationCheckInServer: true,
			triggerLimit: {
				type: 'unlimited',
				delay: {
					value: 0,
					unit: 'days',
				},
			},
			zIndex: 99999,
		};
	}

	function getDefaultAdvanced() {
		return {
			isBodyScrollLocked: false,
			closeOnEscPressed: true,
			closeOnOverlayClicked: true,
			popupOpenedCookie: {
				isEnabled: false,
			},
		};
	}

	function normalizeFrequency( display ) {
		var frequency = display && display.frequency ? display.frequency : {};
		return {
			audience: frequency.audience || 'always',
			cooldownDays: Number( frequency.cooldownDays || 7 ),
			maxDisplaysPerVisit:
				typeof frequency.maxDisplaysPerVisit === 'number' ? frequency.maxDisplaysPerVisit : 'unlimited',
		};
	}

	function normalizePopupOpenedCookie( advanced ) {
		var cookie = advanced && advanced.popupOpenedCookie ? advanced.popupOpenedCookie : {};
		var expiration = cookie.expiration || {};

		return {
			isEnabled: !! cookie.isEnabled,
			name: cookie.name || '',
			isSession: !! cookie.isSession,
			expiration: {
				value: Number( expiration.value || 7 ),
				unit: expiration.unit || 'days',
			},
		};
	}

	function canonicalizePopupOpenedCookie( value ) {
		var cookie = value || {};
		var expiration = cookie.expiration || {};

		if ( ! cookie.isEnabled ) {
			return {
				isEnabled: false,
			};
		}

		if ( cookie.isSession ) {
			return {
				isEnabled: true,
				name: String( cookie.name || 'nelio_popup_seen' ),
				isSession: true,
			};
		}

		return {
			isEnabled: true,
			name: String( cookie.name || 'nelio_popup_seen' ),
			isSession: false,
			expiration: {
				value: Math.max( 1, Number( expiration.value || 7 ) ),
				unit: expiration.unit || 'days',
			},
		};
	}

	function PopupBehaviorPanel() {
		var state = useSelect( function( select ) {
			var editor = select( 'core/editor' );
			return {
				display: editor.getEditedPostAttribute( 'nelio_popups_display' ) || {},
				advanced: editor.getEditedPostAttribute( 'nelio_popups_advanced' ) || {},
			};
		}, [] );

		var editPost = useDispatch( 'core/editor' ).editPost;

		var frequency = normalizeFrequency( state.display );
		var popupOpenedCookie = normalizePopupOpenedCookie( state.advanced );

		function setFrequency( patch ) {
			var nextFrequency = Object.assign( {}, frequency, patch );
			var nextDisplay = Object.assign( {}, getDefaultDisplay(), state.display, { frequency: nextFrequency } );
			editPost( { nelio_popups_display: nextDisplay } );
		}

		function setPopupOpenedCookie( patch ) {
			var nextCookie = Object.assign( {}, popupOpenedCookie, patch );
			var nextAdvanced = Object.assign( {}, getDefaultAdvanced(), state.advanced, {
				popupOpenedCookie: canonicalizePopupOpenedCookie( nextCookie ),
			} );
			editPost( { nelio_popups_advanced: nextAdvanced } );
		}

		return createElement(
			PluginDocumentSettingPanel,
			{
				name: 'nelio-popups-frequency-and-cookie',
				title: __( 'Display Frequency', 'nelio-popups' ),
				className: 'nelio-popups-frequency-and-cookie-panel',
			},
			createElement(
				Fragment,
				null,
				createElement( SelectControl, {
					label: __( 'Audience', 'nelio-popups' ),
					value: frequency.audience,
					options: [
						{ label: __( 'Always show', 'nelio-popups' ), value: 'always' },
						{ label: __( 'First-time visitors only', 'nelio-popups' ), value: 'first-time-only' },
						{
							label: __( 'Returning visitors with cooldown', 'nelio-popups' ),
							value: 'returning-with-cooldown',
						},
					],
					onChange: function( value ) {
						setFrequency( { audience: value } );
					},
				} ),
				frequency.audience === 'returning-with-cooldown'
					? createElement( RangeControl, {
							label: __( 'Cooldown (days)', 'nelio-popups' ),
							value: Math.max( 1, Number( frequency.cooldownDays || 7 ) ),
							min: 1,
							max: 365,
							onChange: function( value ) {
								setFrequency( { cooldownDays: Number( value || 7 ) } );
							},
					  } )
					: null,
				createElement( ToggleControl, {
					label: __( 'Limit displays per visit', 'nelio-popups' ),
					checked: frequency.maxDisplaysPerVisit !== 'unlimited',
					onChange: function( isChecked ) {
						setFrequency( {
							maxDisplaysPerVisit: isChecked ? 1 : 'unlimited',
						} );
					},
				} ),
				frequency.maxDisplaysPerVisit !== 'unlimited'
					? createElement( RangeControl, {
							label: __( 'Max displays per visit', 'nelio-popups' ),
							value: Number( frequency.maxDisplaysPerVisit || 1 ),
							min: 1,
							max: 20,
							onChange: function( value ) {
								setFrequency( {
									maxDisplaysPerVisit: Math.max( 1, Number( value || 1 ) ),
								} );
							},
					  } )
					: null,
				createElement( 'hr', { style: { margin: '16px 0' } } ),
				createElement( ToggleControl, {
					label: __( 'Remember popup with cookie', 'nelio-popups' ),
					checked: popupOpenedCookie.isEnabled,
					onChange: function( isChecked ) {
						if ( ! isChecked ) {
							setPopupOpenedCookie( { isEnabled: false } );
							return;
						}

						setPopupOpenedCookie( {
							isEnabled: true,
							name: popupOpenedCookie.name || 'nelio_popup_seen',
							isSession: popupOpenedCookie.isSession
								? true
								: popupOpenedCookie.isSession,
							expiration: Object.assign(
								{
									value: 7,
									unit: 'days',
								},
								popupOpenedCookie.expiration || {}
							),
						} );
					},
				} ),
				popupOpenedCookie.isEnabled
					? createElement(
							Fragment,
							null,
							createElement( TextControl, {
								label: __( 'Cookie name', 'nelio-popups' ),
								value: popupOpenedCookie.name,
								onChange: function( value ) {
									setPopupOpenedCookie( { name: value } );
								},
							} ),
							createElement( ToggleControl, {
								label: __( 'Session cookie only', 'nelio-popups' ),
								checked: popupOpenedCookie.isSession,
								onChange: function( isChecked ) {
									setPopupOpenedCookie( { isSession: isChecked } );
								},
							} ),
							! popupOpenedCookie.isSession
								? createElement(
										Fragment,
										null,
										createElement( RangeControl, {
											label: __( 'Cookie duration', 'nelio-popups' ),
											value: Math.max( 1, Number( popupOpenedCookie.expiration.value || 7 ) ),
											min: 1,
											max: 365,
											onChange: function( value ) {
												setPopupOpenedCookie( {
													expiration: Object.assign( {}, popupOpenedCookie.expiration, {
														value: Math.max( 1, Number( value || 7 ) ),
													} ),
												} );
											},
										} ),
										createElement( SelectControl, {
											label: __( 'Cookie duration unit', 'nelio-popups' ),
											value: popupOpenedCookie.expiration.unit || 'days',
											options: [
												{ label: __( 'Seconds', 'nelio-popups' ), value: 'seconds' },
												{ label: __( 'Minutes', 'nelio-popups' ), value: 'minutes' },
												{ label: __( 'Hours', 'nelio-popups' ), value: 'hours' },
												{ label: __( 'Days', 'nelio-popups' ), value: 'days' },
												{ label: __( 'Months', 'nelio-popups' ), value: 'months' },
											],
											onChange: function( value ) {
												setPopupOpenedCookie( {
													expiration: Object.assign( {}, popupOpenedCookie.expiration, {
														unit: value,
													} ),
												} );
											},
										} )
								  )
								: null
					  )
					: null
			)
		);
	}

	registerPlugin( 'nelio-popups-frequency-and-cookie', {
		render: PopupBehaviorPanel,
	} );
} )();
