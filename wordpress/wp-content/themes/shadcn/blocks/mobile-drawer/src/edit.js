import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

const ALLOWED_BLOCKS = [
	'core/navigation-link',
	'core/navigation-submenu',
];

const TEMPLATE = [
	[ 'core/navigation-link', { label: 'Home', url: '/' } ],
	[ 'core/navigation-link', { label: 'Catalog', url: '/shop' } ],
	[ 'core/navigation-link', { label: 'Peptide Guide', url: '/peptide-guide' } ],
	[ 'core/navigation-link', { label: 'Research', url: '/research' } ],
];

export default function Edit() {
	const blockProps = useBlockProps( {
		className: 'molecule-mobile-drawer-editor',
	} );

	return (
		<div { ...blockProps }>
			<div className="molecule-mobile-drawer-editor__header">
				<button
					className="molecule-mobile-menu-button"
					type="button"
					disabled
					aria-label={ __( 'Menu', 'shadcn' ) }
				>
					<svg
						role="presentation"
						strokeWidth="2"
						focusable="false"
						width="22"
						height="22"
						viewBox="0 0 22 22"
						aria-hidden="true"
					>
						<path
							d="M1 5h20M1 11h20M1 17h20"
							stroke="currentColor"
							strokeLinecap="round"
						/>
					</svg>
				</button>
				<span className="molecule-mobile-drawer-editor__label">
					{ __( 'Mobile Drawer Navigation', 'shadcn' ) }
				</span>
			</div>
			<div className="molecule-mobile-drawer-editor__nav">
				<InnerBlocks
					allowedBlocks={ ALLOWED_BLOCKS }
					template={ TEMPLATE }
					templateLock={ false }
				/>
			</div>
		</div>
	);
}
