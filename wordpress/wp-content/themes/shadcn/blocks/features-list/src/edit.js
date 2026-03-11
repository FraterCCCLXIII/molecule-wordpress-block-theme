import { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { PanelBody, TextControl, TextareaControl, SelectControl, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const ICON_OPTIONS = [
	{ label: __( 'Shield Check', 'shadcn' ), value: 'shield-check' },
	{ label: __( 'Truck / Delivery', 'shadcn' ), value: 'truck' },
	{ label: __( 'Microscope', 'shadcn' ), value: 'microscope' },
];

const ICON_SVGS = {
	'shield-check': (
		<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
			stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
			<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>
			<path d="m9 12 2 2 4-4"/>
		</svg>
	),
	truck: (
		<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
			stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
			<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/>
			<path d="M15 18H9"/>
			<path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/>
			<circle cx="17" cy="18" r="2"/>
			<circle cx="7" cy="18" r="2"/>
		</svg>
	),
	microscope: (
		<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
			stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
			<path d="M6 18h8"/><path d="M3 22h18"/>
			<path d="M14 22a7 7 0 1 0 0-14h-1"/><path d="M9 14h2"/>
			<path d="M9 12a2 2 0 0 1-2-2V6h6v4a2 2 0 0 1-2 2Z"/>
			<path d="M12 6V3a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v3"/>
		</svg>
	),
};

const EDITOR_STYLES = {
	section: {
		background: 'rgba(248,248,248,0.9)',
		padding: '2rem',
		borderRadius: '0.5rem',
	},
	heading: {
		fontSize: '2rem',
		fontWeight: '700',
		marginBottom: '1rem',
		color: '#111827',
	},
	btn: {
		display: 'flex',
		alignItems: 'center',
		gap: '12px',
		padding: '16px 20px',
		marginBottom: '12px',
		background: '#ffffff',
		borderRadius: '8px',
		border: '1px solid transparent',
		width: '100%',
		textAlign: 'left',
		opacity: 0.5,
	},
	btnActive: {
		border: '1px solid #111827',
		opacity: 1,
		background: '#eaeaea',
	},
	iconWrap: {
		color: '#111827',
		flexShrink: 0,
	},
	label: {
		margin: 0,
		fontWeight: 600,
		fontSize: '1.1rem',
	},
	hint: {
		fontSize: '0.75rem',
		color: '#888',
		marginTop: '0.5rem',
		fontStyle: 'italic',
	},
};

export default function Edit( { attributes, setAttributes } ) {
	const { heading, features } = attributes;
	const blockProps = useBlockProps();

	const updateFeature = ( index, key, value ) => {
		const updated = features.map( ( f, i ) => ( i === index ? { ...f, [ key ]: value } : f ) );
		setAttributes( { features: updated } );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Section Settings', 'shadcn' ) }>
					<TextControl
						label={ __( 'Heading', 'shadcn' ) }
						value={ heading }
						onChange={ ( val ) => setAttributes( { heading: val } ) }
					/>
				</PanelBody>

				{ features.map( ( feature, index ) => (
					<PanelBody
						key={ feature.id }
						title={ `Feature ${ index + 1 }: ${ feature.title || __( '(untitled)', 'shadcn' ) }` }
						initialOpen={ index === 0 }
					>
						<SelectControl
							label={ __( 'Icon', 'shadcn' ) }
							value={ feature.iconType }
							options={ ICON_OPTIONS }
							onChange={ ( val ) => updateFeature( index, 'iconType', val ) }
						/>
						<TextControl
							label={ __( 'Title', 'shadcn' ) }
							value={ feature.title }
							onChange={ ( val ) => updateFeature( index, 'title', val ) }
						/>
						<TextareaControl
							label={ __( 'Description', 'shadcn' ) }
							value={ feature.description }
							onChange={ ( val ) => updateFeature( index, 'description', val ) }
							rows={ 4 }
						/>
						<TextControl
							label={ __( 'Image URL', 'shadcn' ) }
							help={ __( 'Or use the button below to pick from the Media Library.', 'shadcn' ) }
							value={ feature.imageUrl }
							onChange={ ( val ) => updateFeature( index, 'imageUrl', val ) }
						/>
						<MediaUploadCheck>
							<MediaUpload
								onSelect={ ( media ) => {
									updateFeature( index, 'imageUrl', media.url );
									updateFeature( index, 'imageAlt', media.alt || media.title || '' );
								} }
								allowedTypes={ [ 'image' ] }
								render={ ( { open } ) => (
									<Button
										onClick={ open }
										variant="secondary"
										style={ { marginBottom: '12px', width: '100%' } }
									>
										{ feature.imageUrl
											? __( 'Replace Image', 'shadcn' )
											: __( 'Select Image', 'shadcn' ) }
									</Button>
								) }
							/>
						</MediaUploadCheck>
						<TextControl
							label={ __( 'Image Alt Text', 'shadcn' ) }
							value={ feature.imageAlt }
							onChange={ ( val ) => updateFeature( index, 'imageAlt', val ) }
						/>
					</PanelBody>
				) ) }
			</InspectorControls>

			<div { ...blockProps }>
				<div style={ EDITOR_STYLES.section }>
					<h2 style={ EDITOR_STYLES.heading }>{ heading }</h2>

					{ features.map( ( feature, index ) => (
						<div
							key={ feature.id }
							style={ {
								...EDITOR_STYLES.btn,
								...( index === 0 ? EDITOR_STYLES.btnActive : {} ),
							} }
						>
							<div style={ EDITOR_STYLES.iconWrap }>
								{ ICON_SVGS[ feature.iconType ] || ICON_SVGS['shield-check'] }
							</div>
							<div style={ { flex: 1 } }>
								<p style={ EDITOR_STYLES.label }>{ feature.title }</p>
								{ index === 0 && feature.description && (
									<p style={ { fontSize: '0.875rem', color: '#4b5563', margin: '6px 0 0' } }>
										{ feature.description }
									</p>
								) }
							</div>
							{ feature.imageUrl && (
								<img
									src={ feature.imageUrl }
									alt={ feature.imageAlt }
									style={ { width: 48, height: 48, objectFit: 'cover', borderRadius: 6, flexShrink: 0 } }
								/>
							) }
						</div>
					) ) }

					<p style={ EDITOR_STYLES.hint }>
						{ __( '⚡ Image crossfade + accordion expand are active on the frontend.', 'shadcn' ) }
					</p>
				</div>
			</div>
		</>
	);
}
