import { createReduxStore, register } from '@wordpress/data'
import { validationDispatch, ValidationState } from '../data/redux/validation.redux'
import { Debit, Plaid } from '@greenpay/models'

const DEFAULT_STATE: ValidationState = {
	errors: {
		plaid: '',
		routing: '',
		account: '',
	},
	touched: {
		plaid: false,
		routing: false,
		account: false,
	},
	status: {
		tan: false,
		plaid: false,
	},
	errorMessage: '',
}

const storeConfig = {
	reducer(state: ValidationState = DEFAULT_STATE, action: any): ValidationState {
		switch (action.type) {
			case 'SET_ERROR_MESSAGE':
				return { ...state, errorMessage: action.payload.errorMessage }
			case 'SET_PLAID_ERROR':
				return {
					...state,
					errors: { ...state.errors, plaid: action.payload },
				}
			case 'SET_ROUTING_ERROR':
				return {
					...state,
					errors: { ...state.errors, routing: action.payload },
				}
			case 'SET_ACCOUNT_ERROR':
				return {
					...state,
					errors: { ...state.errors, account: action.payload },
				}
			case 'SET_PLAID_TOUCHED':
				return {
					...state,
					touched: { ...state.touched, plaid: action.payload },
				}
			case 'SET_ROUTING_TOUCHED':
				return {
					...state,
					touched: { ...state.touched, routing: action.payload },
				}
			case 'SET_ACCOUNT_TOUCHED':
				return {
					...state,
					touched: { ...state.touched, account: action.payload },
				}
			case 'SET_DEBIT_TOUCHED':
				return {
					...state,
					touched: { ...state.touched, routing: action.payload, account: action.payload },
				}
			case 'SET_TAN_STATUS':
				return {
					...state,
					status: { ...state.status, tan: action.payload },
				}
			default:
				return state
		}
	},
	actions: {
		setErrorMessage(errorMessage: string) {
			return { type: 'SET_ERROR_MESSAGE', payload: { errorMessage } }
		},
		setPlaidError(message: string) {
			return { type: 'SET_PLAID_ERROR', payload: message }
		},
		setRoutingError(message: string) {
			return { type: 'SET_ROUTING_ERROR', payload: message }
		},
		setAccountError(message: string) {
			return { type: 'SET_ACCOUNT_ERROR', payload: message }
		},
		setPlaidTouched(touched: boolean) {
			return { type: 'SET_PLAID_TOUCHED', payload: touched }
		},
		setRoutingTouched(touched: boolean) {
			return { type: 'SET_ROUTING_TOUCHED', payload: touched }
		},
		setAccountTouched(touched: boolean) {
			return { type: 'SET_ACCOUNT_TOUCHED', payload: touched }
		},
		setDebitTouched(touched: boolean) {
			return { type: 'SET_DEBIT_TOUCHED', payload: touched }
		},
		setTanStatus(status: boolean) {
			return { type: 'SET_TAN_STATUS', payload: status }
		},
		setPlaidOpen(status: boolean) {
			return { type: 'SET_PLAID_OPEN', payload: status }
		},
		validatePlaid(data: Plaid | null) {
			return () => {
				const { setPlaidError, setPlaidTouched } = validationDispatch()

				setPlaidTouched(true)
				if (!data || !data.success) {
					setPlaidError('Please use the bank login to register your payment.')
					return false
				} else if (!data.token || !data.institution) {
					setPlaidError('Bank login was unsuccessful, please try again.')
					return false
				} else if (data.isTAN && data.isTAN === true) {
					setPlaidError('Please verify your bank account information.')
					return false
				} else {
					setPlaidError('')
					return true
				}
			}
		},
		validateDebit(data: Debit | null) {
			return () => {
				const { validateRouting, validateAccount, setAccountError, setDebitTouched } = validationDispatch()

				if (!data) {
					setAccountError('Please enter your bank information.')
					setDebitTouched(true)
					return false
				}

				return validateRouting(data.routingNumber) && validateAccount(data.accountNumber)
			}
		},
		validateRouting(routing?: string) {
			return () => {
				const { setRoutingError, setRoutingTouched } = validationDispatch()
				setRoutingTouched(true)
				if (!routing || routing.length !== 9) {
					setRoutingError('Please enter a valid 9-digit routing number.')
					return false
				}

				setRoutingError('')
				return true
			}
		},
		validateAccount(account?: string) {
			return () => {
				const { setAccountError, setAccountTouched } = validationDispatch()
				setAccountTouched(true)
				if (!account || account.length < 5 || account.length > 17) {
					setAccountError('Please enter a valid account number. (must be between 5 and 17 digits)')
					return false
				}

				setAccountError('')
				return true
			}
		},
	},
	selectors: {
		getErrorMessage(state: ValidationState) {
			return state.errorMessage
		},
		getPlaidError(state: ValidationState) {
			return state.errors.plaid
		},
		getPlaidTouched(state: ValidationState) {
			return state.touched.plaid
		},
		getRoutingTouched(state: ValidationState) {
			return state.touched.routing
		},
		getAccountTouched(state: ValidationState) {
			return state.touched.account
		},
		getDebitError(state: ValidationState) {
			return `${state.errors.routing} ${state.errors.account}`.trim()
		},
		getRoutingError(state: ValidationState) {
			return state.errors.routing
		},
		getAccountError(state: ValidationState) {
			return state.errors.account
		},
		getTanStatus(state: ValidationState) {
			return state.status.tan
		},
		getPlaidOpen(state: ValidationState) {
			return state.status.plaid
		},
	},
}

const validationStore = createReduxStore('green-pay/validation', storeConfig)
export const validationActions = storeConfig.actions

register(validationStore)
