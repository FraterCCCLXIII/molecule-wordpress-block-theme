import { Settings } from '@greenpay/models'
import { dispatchStore, selectStore, useDispatchStore, useSelectStore } from '@greenpay/utils'
import { ActionCreator } from '@wordpress/data/build-types/types'

// Define the name of the WP-Data store namespace
const STORE_NAME = 'green-pay/settings'

/**
 * Shape of the settings state
 */
export interface SettingsState {
	settings: Settings | null
}

/**
 * Actions available for the settings store
 */
export interface SettingsActions extends Record<string, ActionCreator> {
	/**
	 * Set the settings data
	 * @param payload - Settings
	 */
	setSettings: (payload: Settings) => { type: string; payload: Settings }
	/**
	 * Load the plugin settings via wp_settings
	 */
	loadSettings: () => Promise<void>
}

/**
 * Selectors available for the settings store
 */
export interface SettingsSelectors {
	/**
	 * Get the stored settings data
	 * @returns Settings or null
	 */
	getSettings(): Settings | null
}

/**
 * Direct dispatch helper: use outside React components.
 * @returns SettingsActions bound to select
 */
export const settingsDispatch = () => dispatchStore<SettingsActions>(STORE_NAME)
/**
 * Direct selector helper: use outside React components.
 * @returns SettingsSelectors bound to select
 */
export const settingsSelect = () => selectStore<SettingsSelectors>(STORE_NAME)
/**
 * React hook to access settings store actions.
 * @returns SettingsActions from useDispatch
 */
export const useSettingsDispatch = () => useDispatchStore<SettingsActions>(STORE_NAME)
/**
 * React hook to access settings store selectors.
 * @returns SettingsSelectors from useSelect
 */
export const useSettingsSelect = () => useSelectStore<SettingsSelectors>(STORE_NAME)
