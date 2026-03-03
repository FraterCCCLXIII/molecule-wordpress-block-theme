import { Debit, Plaid } from '@greenpay/models'
import { dispatchStore, selectStore, useDispatchStore, useSelectStore } from '@greenpay/utils'
import { ActionCreator } from '@wordpress/data/build-types/types'

// Define the name of the WP-Data store namespace
const STORE_NAME = 'green-pay/payment'

/**
 * Shape of the payment-related state
 */
export interface PaymentState {
	method: string | null
	plaid: Plaid | null
	debit: Debit | null
}

/**
 * Actions available for the payment store
 */
export interface PaymentActions extends Record<string, ActionCreator> {
	/**
	 * Set the current payment method
	 * @param method - 'plaid' or 'debit'
	 */
	setMethod: (method: string) => { type: string; payload: string }
	/**
	 * Store the Plaid response data
	 * @param payload - The Plaid object containing token, institution, etc.
	 */
	setPlaid: (payload: Plaid) => {
		type: string
		payload: Plaid
	}
	/**
	 * Store the manual debit account details
	 * @param routing - The bank routing number
	 * @param account  - The bank account number
	 */
	setDebit: (payload: Debit) => { type: string; payload: Debit }
}

/**
 * Selectors available for the payment store
 */
export interface PaymentSelectors {
	/**
	 * Get the currently selected payment method
	 * @returns 'plaid', 'debit' or null
	 */
	getMethod: () => string | null
	/**
	 * Get the stored Plaid response data
	 * @returns Plaid or null
	 */
	getPlaid: () => Plaid | null
	/**
	 * Get the stored manual debit details
	 * @returns routing and account number
	 */
	getDebit: () => Debit | null
}

/**
 * Direct dispatch helper: use outside React components.
 * @returns PaymentActions bound to select
 */
export const paymentDispatch = () => dispatchStore<PaymentActions>(STORE_NAME)
/**
 * Direct selector helper: use outside React components.
 * @returns PaymentSelectors bound to select
 */
export const paymentSelect = () => selectStore<PaymentSelectors>(STORE_NAME)
/**
 * React hook to access payment store actions.
 * @returns PaymentActions from useDispatch
 */
export const usePaymentDispatch = () => useDispatchStore<PaymentActions>(STORE_NAME)
/**
 * React hook to access payment store selectors.
 * @returns PaymentSelectors from useSelect
 */
export const usePaymentSelect = () => useSelectStore<PaymentSelectors>(STORE_NAME)
