import { Typography } from '@mui/material'

import styles from './PaymentLabel.module.scss'

/**
 * Define the state of the component properties injected from a parent component
 */
interface PaymentLabelProps {
	/** Name of the payment method */
	title: string
	/** Optional description of the payment method */
	description?: string
	/** Optional message of the payment method */
	extraMessage?: string
}

/**
 * Label component used by Blocks checkout registry
 * @param param Component properties
 */
const PaymentLabelComponent: React.FC<PaymentLabelProps> = ({ title, description, extraMessage }) => {
	return (
		<div className={styles.container}>
			<Typography variant="body1" display="block">
				{title}
			</Typography>
			<Typography color="text.secondary" display="block">
				{description}
			</Typography>
			<Typography sx={{ color: '#8CA869' }} display="block">
				{extraMessage}
			</Typography>
		</div>
	)
}

export default PaymentLabelComponent
