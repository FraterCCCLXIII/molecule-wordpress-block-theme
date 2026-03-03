/**
 * Plugin settings data model
 */
export interface Settings {
	/** greenmoney endpoint that could be greenbyphone.com, cpsandbox.com or localhost */
	endpoint: string
	/** greenmoney client id */
	clientId: string
	/** Checkout context. Can be Blocks, Classic or OrderPay */
	context: string
	/** If Plaid widget is enabled */
	plaidEnabled: boolean
	/** Payment method title */
	title: string
	/** Payment method description */
	description?: string
	/** Payment method extra message */
	extraMessage?: string
	/** Supported features of the plugin */
	supports?: string[]
}
