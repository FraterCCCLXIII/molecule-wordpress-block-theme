import { useBlockProps, InspectorControls, InnerBlocks } from '@wordpress/block-editor';
import { PanelBody, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Default inner-blocks template: flex row with heading + "Shop All" button.
 * Mirrors the original Next.js header. Fully editable — any blocks can replace it.
 */
const TEMPLATE = [
	[
		'core/group',
		{
			layout: {
				type: 'flex',
				justifyContent: 'space-between',
				verticalAlignment: 'center',
				flexWrap: 'wrap',
			},
			style: { spacing: { margin: { bottom: '2rem' } } },
		},
		[
			[
				'core/heading',
				{
					level: 2,
					content: 'Featured Peptides',
					style: { typography: { fontWeight: '700' } },
				},
			],
			[
				'core/buttons',
				{},
				[
					[
						'core/button',
						{
							text: 'Shop All Peptides →',
							url: '/shop',
							className: 'is-style-ghost',
						},
					],
				],
			],
		],
	],
];

/**
 * Realistic sample products shown in the editor skeleton.
 * Uses actual product names/prices to give an accurate size impression.
 */
const SKELETON_PRODUCTS = [
	{ name: 'BPC-157',     price: '$50.00 – $180.00' },
	{ name: 'Ipamorelin',  price: '$80.00' },
	{ name: 'TB-500',      price: '$75.00 – $140.00' },
	{ name: 'GHK-Cu',      price: '$70.00 – $130.00' },
	{ name: 'Semax',       price: '$50.00' },
];

export default function Edit( { attributes, setAttributes } ) {
	const { perPage } = attributes;

	const blockProps = useBlockProps( { className: 'molecule-product-carousel' } );

	const visibleCards  = SKELETON_PRODUCTS.slice( 0, Math.min( perPage, 5 ) );
	const overflowCount = Math.max( 0, perPage - 5 );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Carousel Settings', 'shadcn' ) }>
					<RangeControl
						label={ __( 'Products to show', 'shadcn' ) }
						help={ __( 'Number of WooCommerce products pulled into the carousel.', 'shadcn' ) }
						value={ perPage }
						onChange={ ( val ) => setAttributes( { perPage: val } ) }
						min={ 4 }
						max={ 24 }
					/>
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				<div className="molecule-product-carousel__inner">

					{/* Fully editable header — users can replace/add any blocks here */}
					<InnerBlocks
						template={ TEMPLATE }
						templateLock={ false }
					/>

					{/* Product track — skeleton cards mirroring the real markup */}
					<div className="molecule-product-carousel__wrap">
						<div className="molecule-product-carousel__track molecule-product-carousel__track--editor">

							{ visibleCards.map( ( product, i ) => (
								<div key={ i } className="molecule-product-carousel__item">
									<div className="molecule-product-carousel__card-group">

										{/* Image: shimmer placeholder replicates the aspect-ratio container */}
										<div className="molecule-product-carousel__image-wrap">
											<div className="molecule-product-carousel__img-placeholder" />
										</div>

										{/* Info row: real text so sizing feels accurate */}
										<div className="molecule-product-carousel__info">
											<div className="molecule-product-carousel__meta">
												<p className="molecule-product-carousel__name">
													{ product.name }
												</p>
												<span className="molecule-product-carousel__price">
													{ product.price }
												</span>
											</div>
										</div>

									</div>
								</div>
							) ) }

							{ overflowCount > 0 && (
								<div className="molecule-product-carousel__item">
									<div className="molecule-product-carousel__more">
										+{ overflowCount } { __( 'more', 'shadcn' ) }
									</div>
								</div>
							) }

						</div>
					</div>

				</div>
			</section>
		</>
	);
}
