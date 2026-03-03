import { Settings } from '@greenpay/models'
import { bindIntercept } from '@greenpay/utils'
import { ThemeProvider } from '@mui/material/styles'
import { useEffect } from '@wordpress/element'
import { Routes, Route, MemoryRouter } from 'react-router'
import ClassicPayloadComponent from '../components/ClassicPayload/ClassicPayload'
import DebitComponent from '../components/Debit/Debit'
import PaymentLabelComponent from '../components/PaymentLabel/PaymentLabel'
import PaymentRadioControl from '../components/PaymentRadio/PaymentRadio'
import PlaidComponent from '../components/Plaid/Plaid'
import { useClassicCheckout } from '../hooks/useClassicCheckout'

import theme from '../styles/theme'

interface ClassicProps {
	title: string
	description?: string
	extraMessage?: string
}

/**
 * Woocommerce classic checkout App
 */
const GreenPayClassic: React.FC<ClassicProps> = ({ title, description, extraMessage }) => {
	useClassicCheckout()

	useEffect(() => {
		const unsubscribe = bindIntercept()
		return () => unsubscribe()
	}, [])

	return (
		<ThemeProvider theme={theme}>
			<PaymentLabelComponent title={title} description={description} extraMessage={extraMessage} />
			<MemoryRouter>
				<Routes>
					<Route key="index" index element={<PaymentRadioControl />} />
					<Route key="plaid" path="plaid" element={<PlaidComponent />} />
					<Route key="debit" path="debit" element={<DebitComponent />} />
				</Routes>
			</MemoryRouter>
			<ClassicPayloadComponent />
		</ThemeProvider>
	)
}

export default GreenPayClassic
