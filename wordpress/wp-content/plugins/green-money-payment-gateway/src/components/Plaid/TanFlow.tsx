import { PaymentSelectors, usePaymentDispatch, useValidationDispatch } from '@greenpay/redux'
import { useSelect } from '@wordpress/data'
import { useState } from '@wordpress/element'
import TanDialog from './TanDialog'
import TanValidation from './TanValidation'

/**
 * Define state of the component properties injected from a parent component
 */
interface TanFlowProps {
	/** Dispatcher to set the account mask */
	setMask: React.Dispatch<React.SetStateAction<string>>
	/** Plaid institution name for modal display */
	institution: string
	/** Link to guide on locating bank info */
	helpLink?: string
}

/**
 * TAN logic controller. Routes the corresponding TAN components needed to complete verification
 * @param param Component properties
 */
const TanFlow: React.FC<TanFlowProps> = ({ setMask, institution, helpLink }) => {
	const debit = useSelect((select) => (select('green-pay/payment') as PaymentSelectors).getDebit(), [])
	const { setTanStatus, validateDebit } = useValidationDispatch()
	const [dialogOpen, setDialogOpen] = useState(true)
	const [error, setError] = useState(false)

	/**
	 * Validates the debit details and closes the dialog on success
	 * Dispatches tanHandled to the parent to display a good standing bank view
	 */
	const handleSubmit = async () => {
		const isValid = validateDebit(debit)

		if (!isValid) {
			setTanStatus(false)
			return
		}

                setError(false)
                setDialogOpen(false)
                setTanStatus(true)
                setMask(debit?.accountNumber?.slice(-4) ?? '')
                setTimeout(() => window.greenpayBoot && window.greenpayBoot(), 50)
        }

	/** Handler to open the TAN dialog again */
	const handleRetry = () => {
		setError(false)
		setDialogOpen(true)
	}

	/** Handler for closing the dialog prematurely. Renders the retry panel */
	const handleClose = () => {
		setError(true)
		setDialogOpen(false)
	}

	return (
		<>
			<TanDialog
				open={dialogOpen}
				institution={institution}
				helpLink={helpLink}
				onClose={handleClose}
				onSubmit={handleSubmit}
			/>
			<TanValidation error={error} onRetry={handleRetry} />
		</>
	)
}

export default TanFlow
