import { Button, Paper, CircularProgress } from '@mui/material'
import ErrorOutlineIcon from '@mui/icons-material/ErrorOutline'

import styles from './Plaid.module.scss'

/**
 * Defines state of the component properties injected from a parent component
 */
interface TanValidationProps {
	/** Boolean to configure TAN validation state */
	error: boolean
	/** Dispatcher to open the TAN dialog again */
	onRetry: () => void
}

/**
 * TAN validation display
 * @param param Component properties
 * @returns
 */
const TanValidation: React.FC<TanValidationProps> = ({ error, onRetry }) => {
	if (!error) {
		// Default state: Display pending validation
		return (
			<Paper variant="outlined" className={styles['tan-container']}>
				<CircularProgress color="primary" />
				<div>
					<div className={styles.title}>Waiting on verification...</div>
					<div className={styles.body}>Please complete the verification form</div>
				</div>
			</Paper>
		)
	} else {
		// Error state: From premature dialog close. Displays the retry button
		return (
			<Paper variant="outlined" className={styles['tan-container']}>
				<ErrorOutlineIcon fontSize="large" />
				<div>
					<div className={styles.title}>Verification failed</div>
					<div className={styles.body}>There was a problem processing your request, please try again.</div>
					<Button variant="contained" onClick={() => onRetry()}>
						Try again
					</Button>
				</div>
			</Paper>
		)
	}
}

export default TanValidation
