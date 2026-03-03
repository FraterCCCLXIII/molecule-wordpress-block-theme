import { PlaidIcon } from '@greenpay/utils'
import { Button, Dialog, DialogActions, DialogContent, DialogTitle, IconButton, Link, Paper } from '@mui/material'
import CloseIcon from '@mui/icons-material/Close'
import HelpIcon from '@mui/icons-material/Help'
import DebitComponent from '../Debit/Debit'

import styles from './Plaid.module.scss'

/**
 * Define state of the component properties injected from a parent component
 */
interface DialogProps {
	/** Boolean control to display the dialog */
	open: boolean
	/** Plaid institution name used for modal display */
	institution: string
	/** Link to guide on locating bank info */
	helpLink?: string
	/** Handler to close the dialog */
	onClose: () => void
	/** Handler to submit the TAN dialog */
	onSubmit: () => void
}

/**
 * Component for the TAN dialog. Prompts user to enter bank information manually
 * @param param Component properties
 */
const TanDialog: React.FC<DialogProps> = ({ open, institution, helpLink, onClose, onSubmit }) => {
	return (
		<Dialog open={open} onClose={() => onClose()} maxWidth="xs" fullWidth className={styles.dialog}>
			<IconButton aria-label="close" onClick={() => onClose()} className={styles.closeBtn}>
				<CloseIcon />
			</IconButton>
			<div>
				<PlaidIcon className={styles.logo} />
			</div>
			<DialogTitle className={styles.title}>Verify your {institution} account</DialogTitle>
			<DialogContent>
				<div className={styles.title}></div>
				<div className={styles.body}>
					To connect to your bank account and process this transaction securely, please provide the routing
					and account number associated with this account.
				</div>
				<Paper className={styles.form} variant="outlined">
					<DebitComponent />
				</Paper>
				{helpLink && (
					<Link href={helpLink} underline="hover" target="_blank" rel="noopener" className={styles.link}>
						<HelpIcon color="primary" fontSize="small" />
						<div>How to find my routing and account numbers</div>
					</Link>
				)}
			</DialogContent>
			<DialogActions className={styles.actions}>
				<Button variant="contained" onClick={() => onSubmit()} fullWidth size="large">
					Verify
				</Button>
			</DialogActions>
		</Dialog>
	)
}

export default TanDialog
