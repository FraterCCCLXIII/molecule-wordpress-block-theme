import { PAYMENT_METHOD_NAME } from '../data/constants'

/**
 * Woocommerce classic checkout event dispatcher
 */
export function bindIntercept(): () => void {
	const form =
		document.querySelector<HTMLFormElement>('form.checkout') ??
		document.querySelector<HTMLFormElement>('form#order_review')
	if (!form) return () => {}

	const btn = form.querySelector<HTMLButtonElement>('button[type="submit"]')
	if (!btn) return () => {}

	function continueCheckout() {
		if (!btn) return

		// Remove the intercept listeners
		btn.removeEventListener('click', startValidation)
		document.body.removeEventListener('updated_checkout', attachHandler)

		btn.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true, view: window }))

		btn.addEventListener('click', startValidation)
		document.body.addEventListener('updated_checkout', attachHandler)
	}

	function startValidation(e: Event) {
		const selected = form?.querySelector<HTMLInputElement>('input[name="payment_method"]:checked')?.value

		if (selected !== PAYMENT_METHOD_NAME) {
			return
		}

		e.preventDefault()
		window.wc_greenpayEventBus.emit('external:placeOrder', continueCheckout)
	}

	function attachHandler() {
		if (!btn) return

		btn.removeEventListener('click', startValidation)
		btn.addEventListener('click', startValidation)
	}

	attachHandler()

	document.body.removeEventListener('updated_checkout', attachHandler)
	document.body.addEventListener('updated_checkout', attachHandler)

	return () => {
		btn.removeEventListener('click', startValidation)
		document.body.removeEventListener('updated_checkout', attachHandler)
	}
}
