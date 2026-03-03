import { createReduxStore, dispatch, register } from '@wordpress/data'
import { SETTINGS_KEY } from '../data/constants'
import { SettingsActions, SettingsState } from '../data/redux/settings.redux'

const DEFAULT_STATE: SettingsState = { settings: null }

const settingsStore = createReduxStore<SettingsState, SettingsActions, any>('green-pay/settings', {
	reducer(state = DEFAULT_STATE, action): SettingsState {
		switch (action.type) {
			case 'SET_SETTINGS':
				return { ...state, settings: action.payload }
			default:
				return state
		}
	},
	actions: {
		setSettings(payload) {
			return { type: 'SET_SETTINGS', payload }
		},
		loadSettings: async (): Promise<void> => {
			const storeDispatch = dispatch('green-pay/settings') as SettingsActions
			try {
				// Try the blocks checkout settings retrieval
				const { getSetting } = await import('@woocommerce/settings')
				const settings = getSetting(SETTINGS_KEY)
				if (settings) {
					storeDispatch.setSettings(settings)
				} else if (window.greenmoney_wpApiSettings) {
					storeDispatch.setSettings(window.greenmoney_wpApiSettings)
				} else {
					await new Promise((res) => setTimeout(res, 300))
					storeDispatch.setSettings(window.greenmoney_wpApiSettings)
				}
			} catch {
				// Fallback: Classic checkout settings retrieval
				await new Promise((res) => setTimeout(res, 300))
				storeDispatch.setSettings(window.greenmoney_wpApiSettings)
			}
		},
	},
	selectors: {
		getSettings(state: SettingsState) {
			return state.settings
		},
	},
})

register(settingsStore)
