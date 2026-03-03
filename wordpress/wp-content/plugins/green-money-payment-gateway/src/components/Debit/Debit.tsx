import { usePaymentDispatch, usePaymentSelect, useValidationDispatch, ValidationSelectors } from '@greenpay/redux'
import { visibilityToggle } from '@greenpay/utils'
import {
	FormControl,
	InputLabel,
	IconButton,
	InputAdornment,
	OutlinedInput,
	FormHelperText,
	Grid2,
} from '@mui/material'
import { Visibility, VisibilityOff } from '@mui/icons-material'
import { useSelect } from '@wordpress/data'
import { useId, useState, useEffect } from '@wordpress/element'

import styles from './Debit.module.scss'
import React, { useRef, useLayoutEffect, useState as useReactState } from 'react'
import ReactDOM from 'react-dom'
import { CacheProvider } from '@emotion/react'
import createCache from '@emotion/cache'

// Wrap the input controls in a Shadow DOM to isolate styles from global WordPress styles

const DebitComponent: React.FC = () => {
	const { validateRouting, validateAccount, setRoutingTouched, setAccountTouched } = useValidationDispatch()
	const { setDebit, setMethod } = usePaymentDispatch()
	const { getDebit } = usePaymentSelect()

	const getRoutingError = useSelect(
		(select) => (select('green-pay/validation') as ValidationSelectors).getRoutingError(),
		[],
	)

	const getAccountError = useSelect(
		(select) => (select('green-pay/validation') as ValidationSelectors).getAccountError(),
		[],
	)

	const getRoutingTouched = useSelect(
		(select) => (select('green-pay/validation') as ValidationSelectors).getRoutingTouched(),
		[],
	)

	const getAccountTouched = useSelect(
		(select) => (select('green-pay/validation') as ValidationSelectors).getAccountTouched(),
		[],
	)

	const [showRouting, setShowRouting] = visibilityToggle(false)
	const [showAccount, setShowAccount] = visibilityToggle(false)

	const [values, setValues] = useState({
		routing: getDebit()?.routingNumber ?? '',
		account: getDebit()?.accountNumber ?? '',
	})

	// Debounced Redux update to prevent triggering WooCommerce events on every keystroke
	const debounceTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null)
	const methodTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null)

	// Sync local state with Redux state changes
	useEffect(() => {
		const debitData = getDebit()
		setValues({
			routing: debitData?.routingNumber ?? '',
			account: debitData?.accountNumber ?? '',
		})
	}, [getDebit])

	// Debounced function to update Redux state
	const debouncedSetDebit = (routing: string, account: string) => {
		if (debounceTimeoutRef.current) {
			clearTimeout(debounceTimeoutRef.current)
		}
		debounceTimeoutRef.current = setTimeout(() => {
			setDebit({ routingNumber: routing, accountNumber: account })
		}, 300) // 300ms delay
	}
	
	// Debounced method setting to prevent infinite loops from updated_checkout events
	const debouncedSetMethod = (method: string) => {
		if (methodTimeoutRef.current) {
			clearTimeout(methodTimeoutRef.current)
		}
		methodTimeoutRef.current = setTimeout(() => {
			setMethod(method)
		}, 100) // 100ms delay to prevent rapid updates
	}

	const idRouting = useId()
	const idAccount = useId()

	// Allow only numeric characters
	const handleOnlyNumerical = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
		const { name, value } = e.target
		if (/^\d*$/.test(value)) {
			setValues((prev) => {
				const newValues = { ...prev, [name]: value }
				
				// Debounced Redux update to prevent triggering WooCommerce events on every keystroke
				debouncedSetDebit(newValues.routing, newValues.account)
				
				return newValues
			})
		}
	}

	const handleBlur = (name: 'routing' | 'account') => {
		let isValid = true

		if (name === 'routing') {
			isValid = validateRouting(values.routing)
			setRoutingTouched(true)
		} else {
			isValid = validateAccount(values.account)
			setAccountTouched(true)
		}

		// Always update the Redux state, even if validation fails
		// This ensures empty values are properly stored and cleared
		setDebit({ routingNumber: values.routing, accountNumber: values.account })
		
		// Only set method if we're in the main checkout form, not in a dialog
		// Check if we're inside a dialog by looking for a parent dialog element
		const isInDialog = shadowHostRef.current?.closest('[role="dialog"]') !== null
		
		if (isValid && !isInDialog) {
			debouncedSetMethod('debit')
		}
	}

	// Shadow DOM logic
	const shadowHostRef = useRef<HTMLDivElement>(null)
	const shadowRootRef = useRef<ShadowRoot | null>(null)
	const [ready, setReady] = useReactState(false)
	const [emotionCache, setEmotionCache] = useReactState<any>(null)

	useLayoutEffect(() => {
		if (shadowHostRef.current && !shadowRootRef.current) {
			shadowRootRef.current = shadowHostRef.current.attachShadow({ mode: 'open' })
		}
	}, [])

	useLayoutEffect(() => {
		if (shadowRootRef.current) {
			let mountPoint = shadowRootRef.current.getElementById('debit-shadow-root')
			if (!mountPoint) {
				mountPoint = document.createElement('div')
				mountPoint.id = 'debit-shadow-root'
				shadowRootRef.current.appendChild(mountPoint)
			}

			// Create an emotion cache targeting the shadow root
			if (!emotionCache) {
				setEmotionCache(createCache({ key: 'mui', container: shadowRootRef.current }))
			}

			setReady(true)
		}
	}, [emotionCache])

	// Cleanup debounce timeout on unmount
	useEffect(() => {
		return () => {
			if (debounceTimeoutRef.current) {
				clearTimeout(debounceTimeoutRef.current)
			}
			if (methodTimeoutRef.current) {
				clearTimeout(methodTimeoutRef.current)
			}
		}
	}, [])

	// Render the form into the ShadowRoot using a portal
	const form = (
		<Grid2 className={styles['debit-container']} container spacing={1} padding={1}>
			<FormControl variant="outlined" error={getRoutingTouched && Boolean(getRoutingError)} fullWidth={true}>
				<InputLabel htmlFor={idRouting}>Routing Number</InputLabel>
				<OutlinedInput
					id={idRouting}
					name="routing"
					value={values.routing}
					type={showRouting ? 'text' : 'password'}
					onChange={handleOnlyNumerical}
					onBlur={() => handleBlur('routing')}
					inputProps={{ maxLength: 9 }}
					autoComplete="off"
					endAdornment={
						<InputAdornment position="end">
							<IconButton
								onClick={setShowRouting}
								edge="end"
								tabIndex={-1}
								aria-label={showRouting ? 'hide routing number' : 'show routing number'}
							>
								{showRouting ? <VisibilityOff /> : <Visibility />}
							</IconButton>
						</InputAdornment>
					}
					label="Routing Number"
				/>
				<FormHelperText>{getRoutingTouched ? getRoutingError : ''}</FormHelperText>
			</FormControl>
			<FormControl variant="outlined" error={getAccountTouched && Boolean(getAccountError)} fullWidth={true}>
				<InputLabel htmlFor={idAccount}>Account Number</InputLabel>
				<OutlinedInput
					id={idAccount}
					name="account"
					value={values.account}
					type={showAccount ? 'text' : 'password'}
					onChange={handleOnlyNumerical}
					onBlur={() => handleBlur('account')}
					autoComplete="off"
					endAdornment={
						<InputAdornment position="end">
							<IconButton
								onClick={setShowAccount}
								edge="end"
								tabIndex={-1}
								aria-label={showAccount ? 'hide account number' : 'show account number'}
							>
								{showAccount ? <VisibilityOff /> : <Visibility />}
							</IconButton>
						</InputAdornment>
					}
					label="Account Number"
				/>
				<FormHelperText>{getAccountTouched ? getAccountError : ''}</FormHelperText>
			</FormControl>
		</Grid2>
	)

	return (
		<div ref={shadowHostRef} id="What">
			{/* Hidden marker to prevent :empty from matching */}
			<span style={{ display: 'none' }} />
			{ready && shadowRootRef.current && emotionCache
				? ReactDOM.createPortal(
						<CacheProvider value={emotionCache}>{form}</CacheProvider>,
						shadowRootRef.current.getElementById('debit-shadow-root')!,
					)
				: null}
		</div>
	)
}

export default DebitComponent
