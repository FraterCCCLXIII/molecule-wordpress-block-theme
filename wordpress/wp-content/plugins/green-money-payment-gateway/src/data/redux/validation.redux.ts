import { Debit, Plaid } from '@greenpay/models'
import { dispatchStore, selectStore, useDispatchStore, useSelectStore } from '@greenpay/utils'
import { validationActions } from '@greenpay/store'

// Define the name of the WP-Data store namespace
const STORE_NAME = 'green-pay/validation'

/**
 * Shape of the validation state
 */
export interface ValidationState {
	/** Manages validation error strings per control */
	errors: {
		plaid: string
		routing: string
		account: string
	}
	/** Manage validation pristine/touch status */
	touched: {
		plaid: boolean
		routing: boolean
		account: boolean
	}
	status: {
		tan: boolean
		plaid: boolean
	}
	/** General error message */
	errorMessage: string
}

/**
 * Actions available for the validation store
 */
export interface ValidationActions {
	/**
	 * Set the general error message
	 * @param message error message
	 */
	setErrorMessage(message: string): void
	/**
	 * Set the plaid error message
	 * @param message error message
	 */
	setPlaidError(message: string): void
	/**
	 * Set the plaid validation control as touched
	 */
	setPlaidTouched(touched: boolean): void
	/**
	 * Set the manual debit routing error message
	 * @param message error message
	 */
	setRoutingError(message: string): void
	/**
	 * Set the routing number control as touched
	 * @param touched boolean true => touched false => pristine
	 */
	setRoutingTouched(touched: boolean): void
	/**
	 * Set the manual debit account error message
	 * @param message error message
	 */
	setAccountError(message: string): void
	/**
	 * Set the account number control as touched
	 * @param touched boolean true => touched false => pristine
	 */
	setAccountTouched(touched: boolean): void
	/**
	 * Sets both routing and account number as touched
	 * @param touched boolean true => touched false => pristine
	 */
	setDebitTouched(touched: boolean): void
	/**
	 * Sets the status of the TAN verification
	 * @param status boolean true => Tan handled false => Tan not handled
	 */
	setTanStatus(status: boolean): void
	/**
	 * Sets the status of the plaid widget visibility
	 * @param status boolean true => Plaid widget opened false => not opened
	 */
	setPlaidOpen(status: boolean): void
	/**
	 * Validate the provided Plaid data
	 * @param data Plaid response data
	 */
	validatePlaid(data: Plaid | null): boolean
	/**
	 * Validate the provided debit data
	 * @param data Debit data routing and account number
	 * @returns boolean
	 */
	validateDebit(data: Debit | null): boolean
	/**
	 * Valdate the debit routing number
	 * @param routing Routing number
	 * @returns boolean
	 */
	validateRouting(routing?: string): boolean
	/**
	 * Validate the debit account number
	 * @param account Account number
	 * @returns boolean
	 */
	validateAccount(account?: string): boolean
}

/**
 * Selectors available for the validation store
 */
export interface ValidationSelectors {
	/**
	 * Get the general error message
	 * @returns string
	 */
	getErrorMessage(): string
	/**
	 * Get the Plaid error message
	 * @returns string
	 */
	getPlaidError(): string
	/**
	 * Get the debit error message
	 * @returns string
	 */
	getDebitError(): string
	/**
	 * Get the debit routing number error message
	 * @returns string
	 */
	getRoutingError(): string
	/**
	 * Get the debit account number error message
	 * @returns string
	 */
	getAccountError(): string
	/**
	 * Get the touched status of the Plaid data component
	 * @returns boolean true => touched false => pristine
	 */
	getPlaidTouched(): boolean
	/**
	 * Get the touched status of the routing number control
	 * @returns boolean true => touched false => pristine
	 */
	getRoutingTouched(): boolean
	/**
	 * Get the touched status of the account number control
	 * @returns boolean true => touched false => pristine
	 */
	getAccountTouched(): boolean
	/**
	 * Get the tan handled status
	 * @returns boolean true => Tan handled false => Tan not handled
	 */
	getTanStatus(): boolean
	/**
	 * Get the Plaid widget visibility status
	 * @return boolean true => visible false => hidden
	 */
	getPlaidOpen(): boolean
}

/**
 * Direct dispatch helper: use outside React components.
 * @returns ValidationActions bound to select
 */
export const validationDispatch = (): ValidationActions => {
	const dispatch = dispatchStore<any>(STORE_NAME)
	const actions = validationActions
	return {
		...dispatch,
		validatePlaid: (data: Plaid | null): boolean => actions.validatePlaid(data)(),
		validateDebit: (data: Debit | null): boolean => actions.validateDebit(data)(),
		validateRouting: (routing?: string): boolean => actions.validateRouting(routing)(),
		validateAccount: (account?: string): boolean => actions.validateAccount(account)(),
	}
}
/**
 * Direct selector helper: use outside React components.
 * @returns ValidationSelectors bound to select
 */
export const validationSelect = () => selectStore<ValidationSelectors>(STORE_NAME)
/**
 * React hook to access validation store actions.
 * @returns ValidationActions from useDispatch
 */
export const useValidationDispatch = (): ValidationActions => {
	const dispatch = dispatchStore<any>(STORE_NAME)
	const actions = validationActions
	return {
		...dispatch,
		validatePlaid: (data: Plaid | null): boolean => actions.validatePlaid(data)(),
		validateDebit: (data: Debit | null): boolean => actions.validateDebit(data)(),
		validateRouting: (routing?: string): boolean => actions.validateRouting(routing)(),
		validateAccount: (account?: string): boolean => actions.validateAccount(account)(),
	}
}
/**
 * React hook to access validation store selectors.
 * @returns ValidationSelectors from useSelect
 */
export const useValidationSelect = () => useSelectStore<ValidationSelectors>(STORE_NAME)
