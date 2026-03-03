import { Plaid } from '@greenpay/models'
import { usePaymentDispatch, useSettingsSelect, useValidationDispatch } from '@greenpay/redux'
import { useEffect, useRef } from '@wordpress/element'

interface PlaidWidgetProps {
	showWidget: boolean
	onWidget: (value: boolean) => void
}

const PlaidWidget: React.FC<PlaidWidgetProps> = ({ showWidget, onWidget }) => {
	const settings = useSettingsSelect().getSettings()
	const { setPlaid } = usePaymentDispatch()
	const iframeRef = useRef<HTMLIFrameElement | null>(null)
	const uuid = typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function'
    	? crypto.randomUUID()
    	: `${Date.now()}-${Math.random().toString(36).slice(2, 10)}`

	// Set up message handler once
	useEffect(() => {
		const handler = (event: MessageEvent) => {
			try {
				const data = JSON.parse(event.data) as Plaid
				setPlaid(data)
				onWidget(false)
				if (iframeRef.current) {
					document.body.removeChild(iframeRef.current)
				}
			} catch (e) {
				console.warn('Non-Plaid message received', e)
			}
		}
		window.addEventListener('message', handler)
		return () => window.removeEventListener('message', handler)
	}, [setPlaid, onWidget])

	// Add/remove iframe when showWidget changes
	useEffect(() => {
		if (!settings) return
		if (!showWidget) return

		const iframe = document.createElement('iframe')
		iframe.src = `${settings.endpoint}/Plaid/Woocommerce?client_id=${settings.clientId}&secret=${uuid}`
		iframe.title = 'Bank Login'
		iframe.style.position = 'fixed'
		iframe.style.top = 'env(safe-area-inset-top, 0)'
		iframe.style.left = 'env(safe-area-inset-left, 0)'
		iframe.style.right = 'env(safe-area-inset-right, 0)'
		iframe.style.bottom = 'env(safe-area-inset-bottom, 0)'
		iframe.style.width = '100%'
		iframe.style.height = '100dvh'
		iframe.style.zIndex = '99999'
		iframe.style.border = 'none'

		document.body.appendChild(iframe)
		iframeRef.current = iframe

		return () => {
			if (iframeRef.current && document.body.contains(iframeRef.current)) {
				document.body.removeChild(iframeRef.current)
				showWidget = false
			}
		}
	}, [showWidget])

	return null // no longer render an inline iframe
}

export default PlaidWidget
