import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const ICON_SVGS = {
	search: (
		<svg role="presentation" strokeWidth="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">
			<circle cx="11" cy="10" r="7" fill="none" stroke="currentColor" />
			<path d="m16 15 3 3" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" />
		</svg>
	),
	account: (
		<svg role="presentation" strokeWidth="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">
			<circle cx="11" cy="7" r="4" fill="none" stroke="currentColor" />
			<path d="M3.5 19c1.421-2.974 4.247-5 7.5-5s6.079 2.026 7.5 5" fill="none" stroke="currentColor" strokeLinecap="round" />
		</svg>
	),
	cart: (
		<svg role="presentation" strokeWidth="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">
			<path d="M11 7H3.577A2 2 0 0 0 1.64 9.497l2.051 8A2 2 0 0 0 5.63 19H16.37a2 2 0 0 0 1.937-1.503l2.052-8A2 2 0 0 0 18.422 7H11Zm0 0V1" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" />
		</svg>
	),
};

const DEFAULT_HREFS = {
	search: '/?s=',
	account: '/my-account',
	cart: '/cart',
};

const TYPE_OPTIONS = [
	{ label: __( 'Search', 'shadcn' ), value: 'search' },
	{ label: __( 'Account', 'shadcn' ), value: 'account' },
	{ label: __( 'Cart', 'shadcn' ), value: 'cart' },
];

export default function Edit( { attributes, setAttributes } ) {
	const { type, href } = attributes;
	const resolvedHref = href || DEFAULT_HREFS[ type ] || '/';

	const blockProps = useBlockProps( {
		className: `molecule-icon-link molecule-header-${ type }`,
		style: { display: 'inline-flex', alignItems: 'center', justifyContent: 'center' },
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Icon Link Settings', 'shadcn' ) }>
					<SelectControl
						label={ __( 'Icon type', 'shadcn' ) }
						value={ type }
						options={ TYPE_OPTIONS }
						onChange={ ( value ) => setAttributes( { type: value, href: '' } ) }
					/>
					<TextControl
						label={ __( 'Custom URL (optional)', 'shadcn' ) }
						help={ __( 'Leave blank to use the default URL for this icon type.', 'shadcn' ) }
						value={ href }
						onChange={ ( value ) => setAttributes( { href: value } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<a { ...blockProps } href={ resolvedHref } aria-label={ type }>
				{ ICON_SVGS[ type ] }
				{ type === 'cart' && (
					<span className="molecule-cart-count" aria-hidden="true">0</span>
				) }
			</a>
		</>
	);
}
