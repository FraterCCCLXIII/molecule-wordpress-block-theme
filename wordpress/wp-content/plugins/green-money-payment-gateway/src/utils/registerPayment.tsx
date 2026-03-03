import { Settings } from '@greenpay/models'
import { decodeEntities } from '@wordpress/html-entities'
import { PAYMENT_METHOD_NAME } from '../data/constants'
import GreenPay from '../app/GreenPay'
import PaymentLabelComponent from '../components/PaymentLabel/PaymentLabel'
import { CircularProgress } from '@mui/material'

/**
 * Blocks payment registeration helper.
 * Doesn't need a implicit React render as the woocommerce function mounts the app
 * @param settings Plugin settings
 */
export const registerPayment = async (settings?: Settings): Promise<void> => {
	try {
		const { registerPaymentMethod } = await import('@woocommerce/blocks-registry')
		const uniqueKey = Date.now().toString()
		if (settings) {
			registerPaymentMethod({
				name: PAYMENT_METHOD_NAME,
				label: (
					<PaymentLabelComponent
						context={settings.context}
						title={decodeEntities(settings.title ?? 'GreenPay')}
						description={settings.description}
						extraMessage={settings.extraMessage}
						key={uniqueKey}
					/>
				),
				// Ignore these syntax errors. Woocommerce on runtime auto injects the component props
				content: <GreenPay key={uniqueKey} />,
				edit: <GreenPay key={uniqueKey} />,
				canMakePayment: () => true,
				ariaLabel: decodeEntities(settings.title ?? 'GreenPay'),
				supports: { features: settings.supports },
			})
		} else {
			// Loading block
			registerPaymentMethod({
				name: PAYMENT_METHOD_NAME,
				label: <CircularProgress color="primary" />,
				content: <></>,
				edit: <></>,
				canMakePayment: () => false,
				ariaLabel: 'Loading',
			})
		}
	} catch {
		// Silent failure
	}
}
