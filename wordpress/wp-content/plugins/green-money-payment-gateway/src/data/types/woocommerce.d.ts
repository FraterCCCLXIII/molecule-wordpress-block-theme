// Type definitions to simulate WooCommerce Blocks APIs

/**
 * Provides access to global settings injected by WordPress/WooCommerce.
 *
 * Example usage:
 * ```ts
 * import { getSetting } from '@woocommerce/settings';
 * const title = getSetting<string>('wc_admin_title', 'Default Title');
 * ```
 *
 * @template T - Expected return type for the setting value.
 * @param key - The settings key to retrieve.
 * @param fallback - Value to return if the key is not found.
 * @returns The setting value or the fallback.
 */
declare module '@woocommerce/settings' {
	export function getSetting<T = any>(key: string, fallback?: T): T
}

/**
 * Allows registering a custom payment method with the WooCommerce Blocks checkout.
 *
 * Example usage:
 * ```tsx
 * import { registerPaymentMethod } from '@woocommerce/blocks-registry';
 * registerPaymentMethod(myPaymentMethodConfig);
 * ```
 *
 * @param method - Configuration object defining your payment method.
 */
declare module '@woocommerce/blocks-registry' {
	export function registerPaymentMethod(method: any): void
}

/**
 * Emulates the WooCommerce Blocks Checkout interface for integrating payment methods.
 * Components passed as `content` or `edit` receive this interface as props.
 */
declare module '@woocommerce/blocks-checkout' {
	/**
	 * Available response types when handling payment processing events.
	 */
	export interface ResponseTypes {
		SUCCESS: string
		ERROR: string
	}

	/**
	 * Contains the response types so you can emit SUCCESS or ERROR.
	 */
	export interface EmitResponse {
		responseTypes: ResponseTypes
	}

	/**
	 * Handlers provided to your payment method component for hooking into
	 * the checkout flow.
	 */
	export interface EventRegistration {
		/**
		 * Called when the user clicks "Place order". Your handler should
		 * return a promise that resolves with either a SUCCESS or ERROR
		 * response object.
		 *
		 * @param handler - Async function validating your custom data.
		 * @returns A function to unsubscribe.
		 */
		onPaymentSetup: (
			handler: () => Promise<
				| { type: ResponseTypes['SUCCESS']; meta: { paymentMethodData: Record<string, unknown> } }
				| { type: ResponseTypes['ERROR']; message?: string }
			>,
		) => () => void
	}

	/**
	 * Props passed into your payment method React component.
	 */
	export interface PaymentMethodInterface {
		/** Methods to register event handlers. */
		eventRegistration: EventRegistration
		/** Helper to emit SUCCESS or ERROR back to the checkout flow. */
		emitResponse: EmitResponse
	}

	/**
	 * React hook to consume the payment method interface within your
	 * component.
	 *
	 * Example:
	 * ```tsx
	 * const { eventRegistration, emitResponse } = usePaymentMethodInterface();
	 * ```
	 *
	 * @returns The eventRegistration and emitResponse helpers.
	 */
	export function usePaymentMethodInterface(): PaymentMethodInterface
}
