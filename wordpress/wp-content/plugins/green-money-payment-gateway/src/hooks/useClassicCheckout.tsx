import { usePaymentSelect, useValidationDispatch, useValidationSelect } from '@greenpay/redux'
import { Plaid } from '@greenpay/models'
import { useEffect, useState } from '@wordpress/element'

/**
 * Checkout Hook for Classic/Shortcode checkouts
 * Validates payment information and also dispatches errors to the corresponding field
 */
export const useClassicCheckout = () => {
	const [isProcessing, setIsProcessing] = useState<boolean>(false)
	const [error, setError] = useState<string | null>(null)

       const { getMethod, getPlaid, getDebit } = usePaymentSelect()
	const { setPlaidTouched, validatePlaid, setDebitTouched, validateDebit } = useValidationDispatch()
	const { getPlaidError, getDebitError } = useValidationSelect()

	/** Main hook to validate the selected payment method */
	useEffect(() => {
		const handler = async (continueSubmit: () => void) => {
			if (isProcessing) return

			setIsProcessing(true)
			setError(null)

                       const method =
                               getMethod() ||
                               document.querySelector<HTMLInputElement>('input[name="wc_greenpay_payment_method"]')?.value

                        if (method === 'plaid') {
                                const plaidState = getPlaid()
                                const fallbackToken = document.querySelector<HTMLInputElement>(
                                        'input[name="wc_greenpay_token"]',
                                )?.value
                                const fallbackInstitution = document.querySelector<HTMLInputElement>(
                                        'input[name="wc_greenpay_institution"]',
                                )?.value
                                const plaid: Plaid | null =
                                        plaidState ??
                                        (fallbackToken && fallbackInstitution
                                                ? {
                                                          token: fallbackToken,
                                                          institution: fallbackInstitution,
                                                          isTAN: false,
                                                          success: true,
                                                          mask: '',
                                                  }
                                                : null)

                                validatePlaid(plaid)

                                if (getPlaidError()) {
                                        setPlaidTouched(true)
                                        setIsProcessing(false)
                                        setError(getPlaidError())
                                        return
                                }
                        } else if (method === 'debit') {
                                const debitState = getDebit()
                                const debit = {
                                        routingNumber:
                                                debitState?.routingNumber ??
                                                document.querySelector<HTMLInputElement>('input[name="wc_greenpay_routing_number"]')?.value ??
                                                '',
                                        accountNumber:
                                                debitState?.accountNumber ??
                                                document.querySelector<HTMLInputElement>('input[name="wc_greenpay_account_number"]')?.value ??
                                                '',
                                }
                                validateDebit(debit)

				if (getDebitError().trim()) {
					setDebitTouched(true)
					setIsProcessing(false)
					setError(getDebitError())
					return
				}
			} else {
				setIsProcessing(false)
				setError('Invalid payment method')
				return
			}

			/** Callback to trigger continuation of the checkout if no payment validation errors are present */
			continueSubmit()
		}
		/** Uses the global event emitter triggered from the on checkout event */
		window.wc_greenpayEventBus.on('external:placeOrder', handler)
		return () => {
			/** Unsubscribe the event emitter to prevent memory leak  */
			window.wc_greenpayEventBus.off('external:placeOrder', handler)
		}
	}, [])

	return { isProcessing, error }
}
