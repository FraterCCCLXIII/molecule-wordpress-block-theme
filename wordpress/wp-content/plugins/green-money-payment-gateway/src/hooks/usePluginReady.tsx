import { SettingsSelectors, useSettingsDispatch, useSettingsSelect } from '@greenpay/redux'
import { Settings } from '@greenpay/models'
import { useEffect, useState } from '@wordpress/element'
import { useSelect } from '@wordpress/data'

/**
 * Custom React hook that loads WooCommerce plugin settings into
 * your Redux store on initial mount, and returns the current settings.
 *
 * Internally, it dispatches the `loadSettings` action once, and
 * then always returns whatever the selector `getSettings` holds.
 *
 * @returns {Settings | null}
 *   The current plugin settings, or `null` if not yet loaded.
 */
export const usePluginReady = (): Settings | null => {
	// Action to fetch and store the settings from the global `wcSettings`.
	const loadSettings = useSettingsDispatch().loadSettings

	const settings = useSelect((select) => (select('green-pay/settings') as SettingsSelectors).getSettings(), [])
	// Local flag to ensure we only dispatch once.
	const [hasDispatched, setHasDispatched] = useState(false)
	const [isReady, setIsReady] = useState<Settings | null>(null)

	useEffect(() => {
		const tryLoad = async () => {
			if (!hasDispatched) {
				try {
					await loadSettings()
				} catch (e) {
					console.error('Settings load failed', e)
				} finally {
					setHasDispatched(true)
				}
			}
		}

		tryLoad()
	}, [hasDispatched, loadSettings])

	useEffect(() => {
		const currentSettings = settings
		if (currentSettings) setIsReady(currentSettings)
	}, [settings])

	return isReady
}
