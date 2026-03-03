import { PaymentSelectors, usePaymentDispatch, ValidationSelectors, useValidationDispatch } from '@greenpay/redux'
import { PlaidIcon } from '@greenpay/utils'
import { Button, CircularProgress, Divider, FormHelperText, IconButton, Paper, Typography } from '@mui/material'
import AccountBalanceIcon from '@mui/icons-material/AccountBalance'
import CheckCircleIcon from '@mui/icons-material/CheckCircle'
import { useSelect } from '@wordpress/data'
import { useEffect, useState, useRef, useCallback } from '@wordpress/element'
import PlaidWidget from './PlaidWidget'
import TanFlow from './TanFlow'

import styles from './Plaid.module.scss'

const PlaidComponent: React.FC = () => {
	const plaid = useSelect((select) => (select('green-pay/payment') as PaymentSelectors).getPlaid(), [])
	const debit = useSelect((select) => (select('green-pay/payment') as PaymentSelectors).getDebit(), [])
	const plaidTouched = useSelect(
		(select) => (select('green-pay/validation') as ValidationSelectors).getPlaidTouched(),
		[],
	)
	const plaidError = useSelect(
		(select) => (select('green-pay/validation') as ValidationSelectors).getPlaidError(),
		[],
	)
	const tanStatus = useSelect((select) => (select('green-pay/validation') as ValidationSelectors).getTanStatus(), [])
	const plaidOpen = useSelect((select) => (select('green-pay/validation') as ValidationSelectors).getPlaidOpen(), [])

	const { setMethod, setDebit } = usePaymentDispatch()
	const { setPlaidError, setPlaidTouched, setPlaidOpen } = useValidationDispatch()

	const [accountMask, setAccountMask] = useState('')
	const [showWidget, setShowWidget] = useState(false)
	
	// Debounced method setting to prevent infinite loops from updated_checkout events
	const methodTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null)
	const debouncedSetMethod = useCallback((method: string) => {
		if (methodTimeoutRef.current) {
			clearTimeout(methodTimeoutRef.current)
		}
		methodTimeoutRef.current = setTimeout(() => {
			setMethod(method)
		}, 100) // 100ms delay to prevent rapid updates
	}, [setMethod])
	
	// Cleanup timeout on unmount
	useEffect(() => {
		return () => {
			if (methodTimeoutRef.current) {
				clearTimeout(methodTimeoutRef.current)
			}
		}
	}, [])

	/**
	 * Hook to respond to the Plaid data returned from the widget
	 * Reruns hook on Plaid data change
	 */
	useEffect(() => {
		if (!plaid) return
		
		if (!plaid.success) {
			// Usually a result from a premature widget close
			setPlaidTouched(true)
			setPlaidError('Bank login was unsuccessful, please try again.')
			return
		}

		if (plaid.isTAN && !tanStatus) {
			// Bank account is using TAN. Prompts user to input bank info
			setPlaidTouched(true)
			setPlaidError('Bank account requires additional user verification.')
			return
		}

		if (plaid.success && !plaid.isTAN) {
			// Plaid data is valid to proceed to checkout
			setAccountMask(plaid.mask)
			// Use debounced method setting to prevent infinite loops
			debouncedSetMethod('plaid')
		}
	}, [plaid, tanStatus, setPlaidTouched, setPlaidError, debouncedSetMethod])

	// Hook to handle succesful TAN dialog events
	useEffect(() => {
		if (!tanStatus || !debit) return

		setPlaidError('')
		// Use debounced method setting to prevent infinite loops
		debouncedSetMethod('debit')
		if (debit.accountNumber) {
			setAccountMask(debit.accountNumber.slice(-4))
			setDebit(debit)
		}
	}, [tanStatus, debit, setPlaidError, debouncedSetMethod, setDebit])

	useEffect(() => {
		setShowWidget(plaidOpen)
	}, [plaidOpen])

	const handleWidget = (widgetVisible: boolean) => {
		setPlaidOpen(widgetVisible)
		setShowWidget(widgetVisible)
	}

        // TAN state: Display TAN validation call to action first
        if (plaid && plaid.isTAN && !tanStatus) {
                return <TanFlow setMask={setAccountMask} institution={plaid.institution} helpLink={plaid.helpLink} />
        }

        // Main state: Display the button to trigger opening the Plaid widget
        if (!plaid || !plaid.success) {
                return (
                        <Paper className={styles['button-container']} variant="outlined">
                                <Typography sx={{ fontSize: '0.8rem' }}>Click to securely log in to your bank account</Typography>
                                <Button
                                        variant="contained"
                                        color="primary"
                                        size="large"
                                        fullWidth
                                        onClick={() => setShowWidget(true)}
                                        disabled={showWidget}
                                        className={styles.button}
                                        loadingPosition="start"
                                        startIcon={
                                                showWidget ? (
                                                        <CircularProgress className={styles.loader} size="20px" />
                                                ) : (
                                                        <PlaidIcon className={styles.logo} />
                                                )
                                        }
                                >
                                        Bank Login
                                </Button>
                                <FormHelperText className={styles.error}>{plaidTouched ? plaidError : ''}</FormHelperText>
                                <PlaidWidget showWidget={showWidget} onWidget={handleWidget} />
                        </Paper>
                )
        }

	// Success state: Display the bank information
	return (
		<Paper variant="outlined" className={styles['confirm-container']}>
			<div className={styles.title}>Bank account</div>
			<Divider className={styles.divider} />
			<Paper variant="outlined" className={styles['confirm-inner']}>
				{plaid.logo ? (
					<img
						src={`data:image/png;base64,${plaid.logo}`}
						alt="Bank Logo"
						style={{ width: 50, height: 50 }}
					/>
				) : (
					<IconButton disabled={true} size="large">
						<AccountBalanceIcon />
					</IconButton>
				)}
				<div className={styles.spacer}>
					<div className={styles.title}>{plaid.institution}</div>
					<div>Account ending in {accountMask}</div>
				</div>
				<div className={styles['icon-container']}>
					<CheckCircleIcon className={styles['success-icon']} />
				</div>
			</Paper>
		</Paper>
	)
}

export default PlaidComponent
