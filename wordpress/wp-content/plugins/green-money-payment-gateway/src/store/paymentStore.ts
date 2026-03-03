import { createReduxStore, register } from '@wordpress/data'
import type { PaymentState, PaymentActions } from '@greenpay/redux'

const DEFAULT_STATE: PaymentState = {
	method: null,
	plaid: null,
	debit: null,
}

/**
 * Redux store to have stable data regarding payments
 */
const paymentStore = createReduxStore<PaymentState, PaymentActions, any>('green-pay/payment', {
	reducer(state = DEFAULT_STATE, action): PaymentState {
		switch (action.type) {
			case 'SET_METHOD':
				return { ...state, method: action.payload }
			case 'SET_PLAID':
				return { ...state, plaid: action.payload }
			case 'SET_DEBIT':
				return { ...state, debit: action.payload }
			default:
				return state
		}
	},
	actions: {
		setMethod(method) {
			return { type: 'SET_METHOD', payload: method }
		},
		setPlaid(payload) {
			return { type: 'SET_PLAID', payload }
		},
		setDebit(payload) {
			return { type: 'SET_DEBIT', payload }
		},
	},
	selectors: {
		getMethod(state: PaymentState) {
			return state.method
		},
		getPlaid(state: PaymentState) {
			return state.plaid
		},
		getDebit(state: PaymentState) {
			return state.debit
		},
	},
})

register(paymentStore)
