import SvgIcon, { SvgIconProps } from '@mui/material/SvgIcon'
import { ReactComponent as RawIcon } from '../../assets/plaid-icon.svg'

export function PlaidIcon(props: SvgIconProps) {
	return <SvgIcon component={RawIcon} inheritViewBox {...props} />
}
