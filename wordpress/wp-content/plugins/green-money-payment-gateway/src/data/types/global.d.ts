import { Settings } from '@greenpay/models'
import jQuery from '@types/jquery'
import EventEmitter from 'eventemitter3'

/**
 * Defines the global window object used in the plugin
 * Makes these objects available and typed for typescript
 */
declare global {
	interface Window {
		/** Settings from localized script hook from the backend */
		greenmoney_wpApiSettings: Settings
		/** Singleton event emitter */
		wc_greenpayEventBus: EventEmitter
		/** Typing the available jQuery module */
		jQuery: typeof jQuery
		greenpayRepaint: () => void
		greenpayBoot: () => void
	}
}

export {}
