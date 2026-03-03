import { usePaymentDispatch, useSettingsSelect, useValidationSelect } from '@greenpay/redux'
import FormControl from '@mui/material/FormControl'
import { FormControlLabel, Radio, RadioGroup } from '@mui/material'
import { useEffect } from '@wordpress/element'
import { useNavigate } from 'react-router'

import styles from './PaymentRadio.module.scss'

/**
 * Main component to display the payment method selector
 */
const PaymentRadioComponent: React.FC = () => {
	const { getSettings } = useSettingsSelect()

	const settings = getSettings()
	const navigate = useNavigate()

	// Auto select plaid or debit
	useEffect(() => {
		if (settings) {
			const plaidEnabled = settings.plaidEnabled
			const method = plaidEnabled ? 'plaid' : 'debit'
			navigate(method)
		}
	}, [settings, navigate])

	const { getErrorMessage } = useValidationSelect()

	return (
		<div className={styles['radio-container']}>
			<FormControl component="fieldset" className={styles['radio-form']} fullWidth>
				<RadioGroup
					aria-label="payment-method"
					onChange={(event) => {
						const selected = event.target.value
						navigate(selected)
					}}
				>
					<FormControlLabel value="plaid" control={<Radio />} label="Bank Login" />
					<FormControlLabel value="debit" control={<Radio />} label="Manual Debit Entry" />
				</RadioGroup>
				{getErrorMessage() && <p className={styles['radio-error']}>{getErrorMessage()}</p>}
			</FormControl>
		</div>
	)
}

export default PaymentRadioComponent
