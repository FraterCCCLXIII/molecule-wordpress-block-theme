import { ThemeProvider } from '@mui/material/styles'
import { PaymentMethodInterface } from '@woocommerce/blocks-checkout'
import { Routes, Route, MemoryRouter } from 'react-router'
import PaymentRadioControl from '../components/PaymentRadio/PaymentRadio'
import DebitComponent from '../components/Debit/Debit'
import PlaidComponent from '../components/Plaid/Plaid'
import { useBlocksCheckout } from '../hooks/useBlocksCheckout'
import theme from '../styles/theme'

/**
 * Woocommerce block checkout app
 * @param props Component properties
 */
const GreenPayApp: React.FC<PaymentMethodInterface> = (props) => {
	const uniqueKey = Date.now().toString()
	useBlocksCheckout(props)

	return (
		<ThemeProvider theme={theme} key={`theme-${uniqueKey}`}>
			<MemoryRouter key={`router-${uniqueKey}`}>
				<Routes key={`routes-${uniqueKey}`}>
					<Route
						key={`index-${uniqueKey}`}
						index
						element={<PaymentRadioControl key={`index-${uniqueKey}`} />}
					/>
					<Route
						key={`plaid-${uniqueKey}`}
						path="plaid"
						element={<PlaidComponent key={`plaid-${uniqueKey}`} />}
					/>
					<Route
						key={`debit-${uniqueKey}`}
						path="debit"
						element={<DebitComponent key={`debit-${uniqueKey}`} />}
					/>
				</Routes>
			</MemoryRouter>
		</ThemeProvider>
	)
}

export default GreenPayApp
