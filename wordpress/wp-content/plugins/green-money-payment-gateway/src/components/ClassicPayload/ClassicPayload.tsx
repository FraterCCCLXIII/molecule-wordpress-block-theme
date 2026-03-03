import { PaymentSelectors } from '@greenpay/redux'
import { useEffect } from '@wordpress/element'
import { useSelect } from '@wordpress/data'

const ClassicPayloadComponent: React.FC = () => {
       const method = useSelect((select) => (select('green-pay/payment') as PaymentSelectors).getMethod(), [])
       const plaid = useSelect((select) => (select('green-pay/payment') as PaymentSelectors).getPlaid(), [])
       const debit = useSelect((select) => (select('green-pay/payment') as PaymentSelectors).getDebit(), [])

       useEffect(() => {
               const setValue = (name: string, value: string) => {
                       const input = document.querySelector<HTMLInputElement>(`input[name="${name}"]`)
                       if (input) {
                               input.value = value
                       }
               }

               setValue('wc_greenpay_context', 'classic')
               if (method) {
                       setValue('wc_greenpay_payment_method', method)
               }
               setValue('wc_greenpay_token', method === 'plaid' ? plaid?.token ?? '' : '')
               setValue('wc_greenpay_institution', method === 'plaid' ? plaid?.institution ?? '' : '')
               setValue('wc_greenpay_routing_number', method === 'debit' ? debit?.routingNumber ?? '' : '')
               setValue('wc_greenpay_account_number', method === 'debit' ? debit?.accountNumber ?? '' : '')
       }, [method, plaid, debit])

       return null
}

export default ClassicPayloadComponent
