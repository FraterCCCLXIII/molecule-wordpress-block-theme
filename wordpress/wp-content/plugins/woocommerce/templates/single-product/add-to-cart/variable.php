<?php
/**
 * Variable product add to cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/variable.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

$attribute_keys  = array_keys( $attributes );
$variations_json = wp_json_encode( $available_variations );
$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );

do_action( 'woocommerce_before_add_to_cart_form' ); ?>

<form class="variations_form cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data' data-product_id="<?php echo absint( $product->get_id() ); ?>" data-product_variations="<?php echo $variations_attr; // WPCS: XSS ok. ?>">
	<?php do_action( 'woocommerce_before_variations_form' ); ?>

	<?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>
		<p class="stock out-of-stock"><?php echo esc_html( apply_filters( 'woocommerce_out_of_stock_message', __( 'This product is currently out of stock and unavailable.', 'woocommerce' ) ) ); ?></p>
	<?php else : ?>
		<table class="variations" cellspacing="0" role="presentation">
			<tbody>
				<?php foreach ( $attributes as $attribute_name => $options ) : ?>
					<?php
					$attribute_label = wc_attribute_label( $attribute_name );
					$attribute_key   = wc_variation_attribute_name( $attribute_name );
					ob_start();
					wc_dropdown_variation_attribute_options(
						array(
							'options'   => $options,
							'attribute' => $attribute_name,
							'product'   => $product,
						)
					);
					$dropdown_markup = ob_get_clean();
					?>
					<tr>
						<th class="label"><label for="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>"><?php echo esc_html( $attribute_label ); ?></label></th>
						<td class="value">
							<div class="wc-variation-select-native" style="display:none;">
								<?php echo $dropdown_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
							<div class="wc-variation-option-group" data-attribute-name="<?php echo esc_attr( $attribute_key ); ?>">
								<div class="wc-variation-option-buttons">
									<?php foreach ( $options as $option ) : ?>
										<?php
										if ( '' === $option ) {
											continue;
										}
										$option_label = $option;
										if ( taxonomy_exists( $attribute_name ) ) {
											$term = get_term_by( 'slug', $option, $attribute_name );
											if ( $term && ! is_wp_error( $term ) ) {
												$option_label = $term->name;
											}
										}
										?>
										<button type="button" class="wc-variation-option-button" data-testid="option-button" data-option-value="<?php echo esc_attr( $option ); ?>">
											<?php echo esc_html( $option_label ); ?>
										</button>
									<?php endforeach; ?>
								</div>
							</div>
							<?php
							/**
							 * Filters the reset variation button.
							 *
							 * @since 2.5.0
							 *
							 * @param string  $button The reset variation button HTML.
							 */
							echo end( $attribute_keys ) === $attribute_name ? wp_kses_post( apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations" href="#" aria-label="' . esc_attr__( 'Clear options', 'woocommerce' ) . '"><span class="screen-reader-text">' . esc_html__( 'Clear options', 'woocommerce' ) . '</span></a>' ) ) : '';
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<div class="reset_variations_alert screen-reader-text" role="alert" aria-live="polite" aria-relevant="all"></div>
		<style type="text/css">
		.wc-variation-option-group {
			margin: 0;
		}
		.variations th.label {
			vertical-align: middle;
			text-align: left;
			padding-left: 0;
			padding-inline-start: 0;
		}
		.variations th.label label {
			margin: 0;
			color: var(--foreground);
			font-size: 0.875rem;
			line-height: 1.25;
			font-weight: 600;
			display: inline-block;
		}
		.wc-variation-option-buttons {
			display: flex;
			flex-wrap: wrap;
			gap: 8px;
		}
		.wc-variation-option-button {
			flex: 1 1 calc(33.333% - 8px);
			min-width: 88px;
			height: 40px;
			padding: 0 16px;
			border: 1px solid #d1d5db;
			border-radius: 9999px;
			background: #f9fafb;
			color: #111827;
			font-size: 14px;
			line-height: 1;
			transition: all 0.2s ease;
			cursor: pointer;
		}
		.wc-variation-option-button:hover:not(:disabled) {
			border-color: #111827;
		}
		.wc-variation-option-button.is-selected {
			border-color: #111827;
			background: #111827;
			color: #ffffff;
		}
		.wc-variation-option-button:disabled,
		.wc-variation-option-button.is-disabled {
			opacity: 0.45;
			cursor: not-allowed;
			box-shadow: none;
		}
		.reset_variations {
			display: none !important;
		}
		.single_variation_wrap {
			margin-top: 0 !important;
			padding-top: 0 !important;
		}
		.single_variation_wrap .single_variation {
			margin: 0 !important;
			padding: 0 !important;
			min-height: 0 !important;
		}
		.single_variation .woocommerce-variation-price {
			display: none !important;
		}
		.single_variation .woocommerce-variation-description,
		.single_variation .woocommerce-variation-availability {
			display: none !important;
			margin: 0 !important;
			padding: 0 !important;
		}
		@media (max-width: 767px) {
			.wc-variation-option-button {
				flex-basis: calc(50% - 8px);
			}
		}
		</style>
		<script type="text/javascript">
		jQuery( function( $ ) {
			$( '.variations_form' ).each( function() {
				var $form = $( this );
				var $priceTarget = $form.closest( '.product' ).find( '.wc-block-components-product-price.wc-block-grid__product-price' ).first();
				var originalPriceHtml = $priceTarget.length ? $priceTarget.html() : '';
				var singleOptionInteractionKey = 'single-option-user-selected';

				function updateGroupState( $group ) {
					var attributeName = $group.data( 'attribute-name' );
					var $select = $form.find( 'select[name="' + attributeName + '"]' );

					if ( ! $select.length ) {
						return;
					}

					var selectedValue = $select.val();

					$group.find( '.wc-variation-option-button' ).each( function() {
						var $button = $( this );
						var optionValue = $button.data( 'option-value' );
						var $option = $select.find( 'option' ).filter( function() {
							return $( this ).val() === optionValue;
						} ).first();
						var isEnabled = $option.length && optionValue !== '' && ! $option.prop( 'disabled' );
						var isSelected = selectedValue === optionValue;

						$button.toggleClass( 'is-selected', isSelected );
						$button.toggleClass( 'is-disabled', ! isEnabled );
						$button.prop( 'disabled', ! isEnabled );
						$button.attr( 'aria-pressed', isSelected ? 'true' : 'false' );
					} );
				}

				function normalizeSingleOptionSelection( $group ) {
					var attributeName = $group.data( 'attribute-name' );
					var $select = $form.find( 'select[name="' + attributeName + '"]' );
					var userSelected = !! $group.data( singleOptionInteractionKey );

					if ( ! $select.length || userSelected ) {
						return;
					}

					var enabledValues = $select.find( 'option' ).filter( function() {
						var value = $( this ).val();
						return value !== '' && ! $( this ).prop( 'disabled' );
					} );

					// Keep single-option groups visible but unselected until user clicks.
					if ( 1 === enabledValues.length ) {
						var singleValue = enabledValues.first().val();
						if ( $select.val() === singleValue ) {
							$select.val( '' );
						}
					}
				}

				function updateAllGroups() {
					$form.find( '.wc-variation-option-group' ).each( function() {
						var $group = $( this );
						normalizeSingleOptionSelection( $group );
						updateGroupState( $group );
					} );
				}

				function updateTopPrice( variation ) {
					if ( ! $priceTarget.length ) {
						return;
					}

					if ( variation && variation.price_html ) {
						$priceTarget.html( variation.price_html );
						return;
					}

					if ( originalPriceHtml ) {
						$priceTarget.html( originalPriceHtml );
					}
				}

				$form.on( 'click', '.wc-variation-option-button', function() {
					var $button = $( this );
					var $group = $button.closest( '.wc-variation-option-group' );
					var attributeName = $group.data( 'attribute-name' );
					var $select = $form.find( 'select[name="' + attributeName + '"]' );
					$group.data( singleOptionInteractionKey, true );

					if ( $select.length ) {
						var optionValue = $button.data( 'option-value' );
						var nextValue = $select.val() === optionValue ? '' : optionValue;
						$select.val( nextValue ).trigger( 'change' );
					}
				} );

				$form.on(
					'change woocommerce_update_variation_values found_variation reset_data hide_variation show_variation',
					'select',
					function() {
						updateAllGroups();
					}
				);
				$form.on( 'found_variation show_variation', function( event, variation ) {
					updateTopPrice( variation );
				} );
				$form.on( 'reset_data hide_variation', function() {
					updateTopPrice();
				} );

				$form.on( 'click', '.reset_variations', function() {
					$form.find( '.wc-variation-option-group' ).removeData( singleOptionInteractionKey );
					window.setTimeout( updateAllGroups, 0 );
				} );

				updateAllGroups();
			} );
		} );
		</script>
		<?php do_action( 'woocommerce_after_variations_table' ); ?>

		<div class="single_variation_wrap">
			<?php
				/**
				 * Hook: woocommerce_before_single_variation.
				 */
				do_action( 'woocommerce_before_single_variation' );

				/**
				 * Hook: woocommerce_single_variation. Used to output the cart button and placeholder for variation data.
				 *
				 * @since 2.4.0
				 * @hooked woocommerce_single_variation - 10 Empty div for variation data.
				 * @hooked woocommerce_single_variation_add_to_cart_button - 20 Qty and cart button.
				 */
				do_action( 'woocommerce_single_variation' );

				/**
				 * Hook: woocommerce_after_single_variation.
				 */
				do_action( 'woocommerce_after_single_variation' );
			?>
		</div>
	<?php endif; ?>

	<?php do_action( 'woocommerce_after_variations_form' ); ?>
</form>

<?php
do_action( 'woocommerce_after_add_to_cart_form' );
