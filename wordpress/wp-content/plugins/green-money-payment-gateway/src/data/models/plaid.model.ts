/**
 * Plaid data model
 * Maps to the data sent by our plaid iframe
 */
export interface Plaid {
	/** Plaid link token */
	token: string
	/** Bank name */
	institution: string
	/** If the plaid institution enforces tokenized account numbers */
	isTAN: boolean
	/** Plaid response status */
	success: boolean
	/** Last 4 digits of the account number */
	mask: string
	/** Bank logo in base64 provided by Plaid API */
	logo?: string
	/** Optional guide for locating bank account info for a specific bank */
	helpLink?: string
}
