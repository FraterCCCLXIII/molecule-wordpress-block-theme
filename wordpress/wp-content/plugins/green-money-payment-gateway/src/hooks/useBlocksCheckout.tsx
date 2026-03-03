import { usePaymentSelect, useValidationDispatch, useValidationSelect } from '@greenpay/redux'
import { Plaid } from '@greenpay/models'
import { PaymentMethodInterface } from '@woocommerce/blocks-checkout'
import { useEffect, useState } from '@wordpress/element'

/**
 * Hook for handling checkouts using Blocks
 * Validates the selected payment method which dispatches validation events control
 * touched and control error message.
 * Builds the error response using the response bubbled from the Green API
 * @param props Auto injected PaymentMethodInterface that handles the on checkout event
 */
export const useBlocksCheckout = (props: PaymentMethodInterface) => {
	const [isProcessing, setIsProcessing] = useState(false)
	const [error, setError] = useState<string | null>(null)
	const { eventRegistration, emitResponse } = props

       const { getMethod, getPlaid, getDebit } = usePaymentSelect()
	const { validatePlaid, validateDebit } = useValidationDispatch()
	const { getPlaidError, getDebitError } = useValidationSelect()

	const errorResponse = (error: string) => {
		setIsProcessing(false)
		setError(error)
		return {
			type: emitResponse.responseTypes.ERROR,
			message: error,
		}
	}

	/** Main hook logic to validate and return the checkout response */
	useEffect(() => {
		const unsubscribe = eventRegistration.onPaymentSetup(async () => {
			setIsProcessing(true)

                        let paymentMethodData = {}
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
                                        return errorResponse(getPlaidError())
                                }

                                paymentMethodData = {
                                        payment_method: method,
                                        token: plaid?.token,
                                        institution: plaid?.institution,
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

				if (getDebitError()) {
					return errorResponse(getDebitError())
				}

				paymentMethodData = {
					payment_method: method,
                                        routing_number: debit.routingNumber,
                                        account_number: debit.accountNumber,
				}
			} else {
				return errorResponse('Invalid payment method')
			}

			setIsProcessing(false)

			return {
				type: emitResponse.responseTypes.SUCCESS,
				meta: {
					paymentMethodData,
				},
			}
		})

		return unsubscribe
	}, [eventRegistration.onPaymentSetup, emitResponse.responseTypes.ERROR, emitResponse.responseTypes.SUCCESS])

	return { isProcessing, error }
}
