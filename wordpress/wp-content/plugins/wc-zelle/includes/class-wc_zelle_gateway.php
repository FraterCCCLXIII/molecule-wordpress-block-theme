<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
if ( class_exists( 'WC_Payment_Gateway' ) ) {
    class WC_Zelle_Gateway extends WC_Payment_Gateway {
        public function __construct() {
            $this->id = 'zelle';
            // payment gateway plugin ID
            $this->icon = WCZELLE_PLUGIN_DIR_URL . 'assets/images/zelle_35.png';
            // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true;
            // in case you need a custom form
            $this->method_title = 'Zelle';
            $this->method_description = 'Easily receive Zelle payments';
            // will be displayed on the options page
            $this->method_description .= '<br><p>Configure <a href="' . esc_url( admin_url( 'admin.php?page=wc_zelle_automated_status' ) ) . '">automatic order updates</a> when payment is received (emailreceipts.io).</p>';
            $this->init_settings();
            $this->enabled = $this->get_option( 'enabled' );
            $this->title = ( $this->get_option( 'checkout_title' ) ? $this->get_option( 'checkout_title' ) : $this->method_title );
            $this->ReceiverZELLENo = $this->get_option( 'ReceiverZELLENo' );
            $this->ReceiverZelleOwner = $this->get_option( 'ReceiverZelleOwner' );
            $this->ReceiverZelleTag   = $this->get_option( 'ReceiverZelleTag' );
            $this->ReceiverZELLEEmail = $this->get_option( 'ReceiverZELLEEmail' );
            $this->ZelleForwardingURL = wp_kses_post( get_bloginfo( 'url' ) . '/wp-json/wc-zelle/v1/update-zelle-order' );
            $this->update_option( 'ZelleForwardingURL', $this->ZelleForwardingURL );
            $this->ZelleStockManagement = $this->get_option( 'ZelleStockManagement' );
            $this->checkout_description = $this->get_option( 'checkout_description' );
            $this->zelle_notice = $this->get_option( 'zelle_notice' );
            $this->store_instructions = $this->get_option( 'store_instructions' );
            $this->display_zelle = $this->get_option( 'display_zelle' );
            $this->enableQRCode = $this->get_option( 'enableQRCode' );
            $this->ZelleQRCode = $this->get_option( 'ZelleQRCode' );
            $this->enableNote = $this->get_option( 'enableNote' );
            $this->order_note = $this->get_option( 'order_note' );
            $this->disableMenu = $this->get_option( 'disableMenu' ) ?? 'no';
            $this->processOrder = $this->get_option( 'processOrder' ) ?? 'no';
            $this->enable_debug = $this->get_option( 'enable_debug' );
            $this->toggleSupport = $this->get_option( 'toggleSupport' );
            $this->toggleTutorial = $this->get_option( 'toggleTutorial' );
            $this->toggleCredits = $this->get_option( 'toggleCredits' );
            $this->memo_order_number = $this->get_option( 'memo_order_number' );
            $this->memo_template = $this->get_option( 'memo_template' );
            $this->receiver_bank_name = $this->get_option( 'receiver_bank_name' );
            $this->show_zelle_modal = $this->get_option( 'show_zelle_modal' );
            $this->zelle_modal_auto_open = $this->get_option( 'zelle_modal_auto_open' );
            $this->zelle_cancel_unpaid = $this->get_option( 'zelle_cancel_unpaid' );
            $this->zelle_cancel_unpaid_hours = $this->get_option( 'zelle_cancel_unpaid_hours' );
            // hold stock admin_url('admin.php?page=wc-settings&tab=products&section=inventory)
            $new = ' <sup style="color:#0c0">NEW</sup>';
            $newFeature = " <sup style='color:#0c0;'>NEW FEATURE</sup>";
            $improved = " <sup style='color:#0c0;'>IMPROVED</sup>";
            $improvedFeature = " <sup style='color:#0c0;'>IMPROVED FEATURE</sup>";
            $comingSoon = " <sup style='color:#00c;'>COMING SOON</sup>";
            $emrcpts = ' <a href="' . esc_attr( wp_kses_post( admin_url( 'admin.php?page=wc_zelle_automated_status' ) ) ) . '" target="_blank">CONNECT</a>';
            $default_checkout_description = '<p><strong>' . esc_html__( 'Pay with Zelle', WCZELLE_PLUGIN_TEXT_DOMAIN ) . '</strong></p><p>' . esc_html__( 'Send the cart total to the recipient shown below. When you complete checkout, your order number is created—you will use that number (and the memo line on the confirmation page, if enabled) in your Zelle payment.', WCZELLE_PLUGIN_TEXT_DOMAIN ) . '</p>';
            $default_zelle_notice = '<p>' . esc_html__( 'Thank you for your order. Send your Zelle payment using your order number, amount, and the recipient details in the checklist below.', WCZELLE_PLUGIN_TEXT_DOMAIN ) . '</p>';
            $default_store_instructions = esc_html__( 'Send the full order total via Zelle. Use the order number and memo exactly as shown in your payment details so we can match your payment.', WCZELLE_PLUGIN_TEXT_DOMAIN );
            $default_order_note = sprintf(
                /* translators: 1: order number placeholder, 2: order total placeholder */
                esc_html__( 'Order %1$s — total %2$s.', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                '<strong>**order_number**</strong>',
                '<strong>**order_total**</strong>'
            ) . '<br><br>' .
            sprintf(
                /* translators: %s: memo line placeholder */
                esc_html__( 'Zelle memo: %s', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                '**order_memo**'
            ) . '<br><br>' .
            sprintf(
                /* translators: 1: recipient name ph, 2: phone ph, 3: email ph */
                esc_html__( 'Send to: %1$s · %2$s · %3$s', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                '**recipient_name**',
                '**recipient_phone**',
                '**recipient_email**'
            ) . '<br>' .
            sprintf(
                /* translators: %s: Zelle Tag placeholder */
                esc_html__( 'Zelle Tag (if set): %s', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                '**recipient_zelle_tag**'
            ) . '<br><br>' .
            esc_html__( 'We will confirm your order when the payment is received.', WCZELLE_PLUGIN_TEXT_DOMAIN ) . '<br><br>' .
            esc_html__( 'Kindest regards,', WCZELLE_PLUGIN_TEXT_DOMAIN ) . '<br>**shop_name**<br>**shop_email**<br>**shop_url**';
            $payment_url = $this->wc_zelle_url( 1 );
            $qr_code_url = $this->wc_zelle_qrcode_url( 1 );
            $qr_code = $this->wc_zelle_qrcode( 1, 'advanced' );
            // upgrade display_zelle
            if ( $this->display_zelle === 'no' ) {
                $this->update_option( 'display_zelle', '1' );
            } else {
                if ( $this->display_zelle === 'yes' ) {
                    $this->update_option( 'display_zelle', '2' );
                }
            }
            $this->form_fields = array(
                'enabled'              => array(
                    'title'   => 'Enable ZELLE',
                    'label'   => 'Check to Enable / Uncheck to Disable',
                    'type'    => 'checkbox',
                    'default' => 'no',
                ),
                'checkout_title'       => array(
                    'title'       => 'Checkout Title',
                    'type'        => 'text',
                    'description' => 'This is the title which the user sees on the checkout page.',
                    'default'     => 'Zelle',
                    'placeholder' => 'Zelle',
                ),
                'ReceiverZELLENo'      => array(
                    'title'       => 'Receiver Zelle No',
                    'type'        => 'text',
                    'description' => 'This is the phone number associated with your store Zelle/Bank account or your receiving Zelle/Bank account. Customers will send money to this number',
                    'placeholder' => "+1234567890",
                ),
                'ReceiverZelleOwner'   => array(
                    'title'       => "Receiver Zelle Owner's Name",
                    'type'        => 'text',
                    'description' => 'This is the name associated with your store Zelle/Bank account. Customers will send money to this Zelle/Bank account name',
                    'placeholder' => 'Jane D',
                ),
                'ReceiverZelleTag'     => array(
                    'title'       => __( 'Zelle Tag (handle)', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                    'type'        => 'text',
                    'description' => __( 'Optional. Your small-business Zelle Tag from your bank—the custom handle customers can use to pay you (letters, numbers, hyphens). Shown in checkout instructions, order emails, and the payment modal when filled.', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                    'placeholder' => __( 'your-store-tag', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                    'default'     => '',
                ),
                'ReceiverZELLEEmail'   => array(
                    'title'       => "Receiver Zelle Owner's Email",
                    'type'        => 'text',
                    'description' => 'This is the email associated with your store Zelle/Bank account or your receiving Zelle/Bank account. Customers will send money to this email',
                    'default'     => "@gmail.com",
                    'placeholder' => "email@website.com",
                ),
                'ZelleForwardingURL'   => array(
                    'title'       => 'Connect your Email Receipts via emailreceipts.io' . $emrcpts,
                    'type'        => 'text',
                    'description' => 'This is the URL that will be imported to emailreceipts.io while setting up' . $emrcpts,
                    'default'     => $this->ZelleForwardingURL,
                    'placeholder' => $this->ZelleForwardingURL,
                    'css'         => 'width:80%;',
                ),
                'display_zelle'        => array(
                    'title'       => 'Checkout page design templates' . $improved,
                    'label'       => 'Choose how you want customers to see the Zelle info on checkout',
                    'type'        => 'select',
                    'description' => 'Choose how you want customers to see the Zelle info on checkout.
						<p>Designs include <strong>copy to clipboard</strong>, optional <strong>QR code</strong>, and a <strong>Zelle button/link</strong> where applicable.</p>
						<p><strong>Design 1:</strong> compact notice only (uses your checkout notice text).</p>
						<p><strong>Design 2:</strong> full-width layout with Zelle details.</p>
						<p><strong>Design 3:</strong> two-column layout on larger screens.</p>',
                    'default'     => '2',
                    'options'     => array(
                        '1' => '1: compact (checkout notice only)',
                        '2' => '2: full width columns',
                        '3' => '3: half width columns',
                    ),
                ),
                'enableQRCode'         => array(
                    'title'       => 'Show the Zelle QR code and button on the checkout page and the thank you page' . $improved,
                    'label'       => 'Check to show the QR code and button / Uncheck to remove the QR code and button',
                    'type'        => 'select',
                    'description' => "Test the Zelle QR code and button on checkout, the order confirmation page, and inside the payment instructions modal when the modal is enabled.<br><strong>Make sure your institution allows QR codes</strong><br>{$qr_code}",
                    'default'     => 'no',
                    'options'     => array(
                        'yes' => 'Yes, show the QR code and button',
                        'no'  => 'No, do not show the QR code and button',
                    ),
                ),
                'ZelleQRCode'          => array(
                    'title'       => 'Your Zelle QR code URL on the checkout page and the thank you page' . $improved,
                    'label'       => 'This is for your Zelle QR code and button shown on the checkout page and the thank you page',
                    'type'        => 'zelle_qr_media',
                    'description' => 'Paste your Zelle QR URL, or use <strong>Select from Media Library</strong> to pick an uploaded image (the file URL is inserted automatically).' . "<br>Paste a <code>https://enroll.zellepay.com/qr-codes?data=…</code> URL from your bank/Zelle app, or upload a QR image and use the button." . '<br>You can also <a href="' . admin_url( '/media-new.php' ) . '" target="_blank">upload new files</a> in Media, then select them here.',
                    'placeholder' => 'https://enroll.zellepay.com/qr-codes?data=***',
                    'css'         => 'width:min(100%,28rem);max-width:100%;',
                ),
                'ZelleStockManagement' => array(
                    'title'       => 'Reduce Stock ONLY after payment receipt',
                    'label'       => 'Check to to reduce stock when order goes to processing / Uncheck to reduce stock when order goes on-hold',
                    'type'        => 'checkbox',
                    'description' => 'If you want to reduce stock once payment is received, check this box',
                    'default'     => 'no',
                ),
                'checkout_description' => array(
                    'title'       => 'Checkout Page Notice',
                    'type'        => 'textarea',
                    'description' => "This is the text a customer sees in the payment gateway box on the checkout page.<br>Default:<br>{$default_checkout_description}",
                    'default'     => $default_checkout_description,
                    'css'         => 'width:80%;',
                ),
                'zelle_notice'         => array(
                    'title'       => 'Thank You Notice',
                    'type'        => 'textarea',
                    'description' => "This is the text a customer sees on the thank you/order confirmation page after placing an order.<br>Default:<br>{$default_zelle_notice}",
                    'default'     => $default_zelle_notice,
                    'css'         => 'width:80%;',
                ),
                'store_instructions'   => array(
                    'title'       => 'Store Instructions',
                    'type'        => 'textarea',
                    'description' => "Store Instructions that will be added to the thank you page and emails.<br>Default:<br>{$default_store_instructions}",
                    'default'     => $default_store_instructions,
                    'css'         => 'width:80%;',
                ),
                'memo_order_number'    => array(
                    'title'       => 'Zelle memo (order number)',
                    'label'       => 'Show memo instructions including the order number',
                    'type'        => 'checkbox',
                    'description' => __( 'After checkout, tell customers what to enter in the Zelle memo field (see memo template). Shown on the order confirmation page and inside the payment modal when enabled.', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                    'default'     => 'no',
                ),
                'memo_template'        => array(
                    'title'       => 'Memo template',
                    'type'        => 'text',
                    'description' => __( 'Placeholders: <code>{customer_name}</code>, <code>{order_number}</code>, <code>{order_id}</code>', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                    'default'     => '{customer_name} - {order_number}',
                    'css'         => 'width:80%;',
                ),
                'receiver_bank_name'   => array(
                    'title'       => 'Financial institution name (optional)',
                    'type'        => 'text',
                    'description' => __( 'Shown in payment steps after the phone number, e.g. “(Bank of America)”. Leave blank to hide.', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                    'default'     => '',
                ),
                'show_zelle_modal'     => array(
                    'title'       => 'Payment instructions modal',
                    'label'       => 'Show Zelle payment steps in a modal on the order confirmation page',
                    'type'        => 'checkbox',
                    'description' => __( 'Opens a dialog with step-by-step instructions: app, recipient, amount, and memo (when memo option is on).', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                    'default'     => 'no',
                ),
                'zelle_modal_auto_open' => array(
                    'title'       => 'Open modal automatically',
                    'label'       => 'Open the payment instructions modal as soon as the order confirmation page loads',
                    'type'        => 'checkbox',
                    'description' => __( 'If unchecked, customers use a button to open the modal.', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                    'default'     => 'yes',
                ),
                'zelle_cancel_unpaid'   => array(
                    'title'       => __( 'Cancel unpaid Zelle orders', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                    'label'       => __( 'Automatically cancel orders if Zelle payment is not received in time', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                    'type'        => 'checkbox',
                    'description' => __( 'Runs on a schedule (about once per hour). Only applies to this payment method while the order is still awaiting payment (on hold or pending).', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                    'default'     => 'no',
                ),
                'zelle_cancel_unpaid_hours' => array(
                    'title'             => __( 'Hours until unpaid order is cancelled', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                    'type'              => 'number',
                    'description'       => __( 'If payment is not received within this many hours from order placement, the order is cancelled automatically. Allowed range: 1–720.', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                    'default'           => '24',
                    'custom_attributes' => array(
                        'min'  => '1',
                        'max'  => '720',
                        'step' => '1',
                    ),
                ),
                'zelle_payment_received_email' => array(
                    'title'       => __( 'Zelle payment received email', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                    'label'       => __( 'Send a confirmation email when Zelle payment is received', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                    'type'        => 'checkbox',
                    'description' => __( 'Emails the customer when the order is marked paid (manual completion, webhook from emailreceipts.io, etc.). Sent once per order. While this is enabled, the standard WooCommerce “processing order” email is not sent for Zelle orders, so customers are not emailed twice.', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                    'default'     => 'no',
                ),
                'zelle_payment_received_email_subject' => array(
                    'title'       => __( 'Zelle received — email subject', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                    'type'        => 'text',
                    'description' => __( 'Placeholders: {site_title}, {order_number}, {order_date}', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                    'default'     => __( 'We received your Zelle payment — order {order_number}', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                    'css'         => 'width:80%;',
                ),
                'zelle_payment_received_email_heading' => array(
                    'title'       => __( 'Zelle received — email heading', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                    'type'        => 'text',
                    'description' => __( 'Shown as the main title inside the email.', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                    'default'     => __( 'Your payment was received', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                    'css'         => 'width:80%;',
                ),
                'enableNote'           => array(
                    'title'       => 'Enable/Disable adding a note to orders',
                    'label'       => 'Check to enable sending note / Uncheck to disable sending note',
                    'type'        => 'checkbox',
                    'description' => 'A note will be added to your order and an email about that note will be sent to your email',
                    'default'     => 'yes',
                ),
                'order_note'           => array(
                    'title'       => 'Admin Order Note',
                    'type'        => 'textarea',
                    'description' => 'Placeholders: <code>**order_number**</code>, <code>**order_id**</code>, <code>**order_total**</code>, <code>**order_memo**</code>, <code>**recipient_name**</code>, <code>**recipient_zelle_tag**</code>, <code>**recipient_phone**</code>, <code>**recipient_email**</code>, <code>**shop_name**</code>, <code>**shop_email**</code>, <code>**shop_url**</code>.<br>Default:<br>' . $default_order_note,
                    'default'     => $default_order_note,
                    'css'         => 'width:80%;',
                ),
                'processOrder'         => array(
                    'title'       => 'Enable/Disable processing orders automatically',
                    'label'       => 'Check to enable processing orders / Uncheck to disable processing orders',
                    'type'        => 'checkbox',
                    'description' => '<p>When checked, orders will automatically be processed after checkout (whether payment was sent or not).</p>
							<p>When unchecked, orders will be put on-hold until you manually process them or use emailreceipts.io to auto-process them</p>',
                    'default'     => 'no',
                ),
                'enable_debug'         => array(
                    'title'       => 'Enable Debug',
                    'label'       => 'Check to Enable / Uncheck to Disable',
                    'type'        => 'checkbox',
                    'description' => 'This will enable debug mode to help you troubleshoot issues. <a href="' . admin_url( 'admin.php?page=wc-status&tab=logs' ) . '" target="_blank">Access Logs here</a>',
                    'default'     => 'no',
                ),
                'toggleSupport'        => array(
                    'title'       => 'Enable Support message',
                    'label'       => 'Check to Enable / Uncheck to Disable',
                    'type'        => 'checkbox',
                    'description' => 'Help your customers checkout with ease by letting them know how to contact you',
                    'default'     => 'yes',
                ),
                'toggleTutorial'       => array(
                    'title'       => 'Enable Tutorial to display 1min video link',
                    'label'       => 'Check to Enable / Uncheck to Disable',
                    'type'        => 'checkbox',
                    'description' => 'Help your customers checkout with ease by showing this tutorial link',
                    'default'     => 'no',
                ),
                'toggleCredits'        => array(
                    'title'       => 'Enable Credits to display Powered by The African Boss',
                    'label'       => 'Check to Enable / Uncheck to Disable',
                    'type'        => 'checkbox',
                    'description' => 'Help us spread the word about this plugin by sharing that we made this plugin',
                    'default'     => 'no',
                ),
            );
            // Gateways can support subscriptions, refunds, saved payment methods
            $this->supports = array('products');
            // This action hook saves the settings
            add_action( "woocommerce_update_options_payment_gateways_{$this->id}", array($this, 'process_admin_options') );
            // We need custom JavaScript to obtain a token
            add_action( 'wp_enqueue_scripts', array($this, 'wc_zelle_payment_scripts') );
            add_action( 'wp_enqueue_scripts', array( $this, 'wc_zelle_thankyou_assets' ), 25 );
            // Thank you page
            add_action( "woocommerce_thankyou_{$this->id}", array($this, 'wc_zelle_thankyou_page') );
            add_action(
                'woocommerce_checkout_order_processed',
                array($this, 'wc_zelle_processed'),
                10,
                3
            );
            // Customer Emails
            add_action(
                'woocommerce_email_order_details',
                array($this, 'wc_zelle_email_instructions'),
                10,
                3
            );
            if ( wc_zelle_is_pro() && is_plugin_active( 'zoho-mail/zohoMail.php' ) ) {
                add_action( 'woocommerce_order_status_pending_to_on-hold_notification', array($this, 'wczelle_zoho_notification') );
            }
            // WooCommerce Blocks support
            add_action( 'woocommerce_blocks_loaded', array($this, 'zelle_woocommerce_blocks_support') );
            // Admin: Media Library picker for Zelle QR URL field
            add_action( 'admin_enqueue_scripts', array( $this, 'wc_zelle_admin_enqueue_qr_media' ) );
        }

        /**
         * Scripts for “Select from Media Library” on the Zelle QR URL field.
         *
         * @param string $hook_suffix Current admin screen.
         */
        public function wc_zelle_admin_enqueue_qr_media( $hook_suffix ) {
            if ( empty( $_GET['page'] ) || 'wc-settings' !== $_GET['page'] ) {
                return;
            }
            if ( empty( $_GET['tab'] ) || 'checkout' !== $_GET['tab'] || empty( $_GET['section'] ) || 'zelle' !== $_GET['section'] ) {
                return;
            }
            wp_enqueue_media();
            wp_enqueue_script(
                'wc-zelle-admin-qr',
                WCZELLE_PLUGIN_DIR_URL . 'assets/js/admin-qr-media.js',
                array( 'jquery' ),
                WCZELLE_PLUGIN_VERSION,
                true
            );
            wp_localize_script(
                'wc-zelle-admin-qr',
                'wcZelleQrMedia',
                array(
                    'title'  => __( 'Choose file for Zelle QR', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                    'button' => __( 'Use this file URL', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                )
            );
        }

        /**
         * Text field + “Select from Media Library” for Zelle QR URL.
         *
         * @param string $key Field key.
         * @param array  $data Field args.
         * @return string
         */
        public function generate_zelle_qr_media_html( $key, $data ) {
            $field_key = $this->get_field_key( $key );
            $defaults  = array(
                'title'             => '',
                'disabled'          => false,
                'class'             => '',
                'css'               => '',
                'placeholder'       => '',
                'desc_tip'          => false,
                'description'       => '',
                'custom_attributes' => array(),
            );
            $data = wp_parse_args( $data, $defaults );

            ob_start();
            ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data ); // WPCS: XSS ok. ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
                    <p class="wc-zelle-qr-media-row" style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin:0 0 8px;max-width:100%;">
                        <input class="input-text regular-input <?php echo esc_attr( $data['class'] ); ?>" type="text" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" value="<?php echo esc_attr( $this->get_option( $key ) ); ?>" placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data ); // WPCS: XSS ok. ?> />
                        <button type="button" class="button wc-zelle-select-qr-media"><?php esc_html_e( 'Select from Media Library', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?></button>
                    </p>
                    <?php echo $this->get_description_html( $data ); // WPCS: XSS ok. ?>
                </fieldset>
            </td>
        </tr>
            <?php
            return ob_get_clean();
        }

        public function zelle_woocommerce_blocks_support() {
            if ( class_exists( 'WC_Payment_Gateway' ) && class_exists( 'Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType' ) ) {
                require_once WCZELLE_PLUGIN_DIR . 'includes/class-wc_zelle_gateway_blocks.php';
                add_action( 'woocommerce_blocks_payment_method_type_registration', function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
                    $payment_method_registry->register( new WC_Zelle_Gateway_Blocks_Support() );
                } );
            }
        }

        public function wc_zelle_url( $amount = 0, $note = '' ) {
            $payment_url = "";
            if ( !empty( $this->ZelleQRCode ) ) {
                $payment_url = $this->ZelleQRCode;
            }
            return esc_attr( trim( $payment_url ) );
            // if ( !empty($this->ZelleQRCode) && strpos($this->ZelleQRCode, wp_upload_dir()['baseurl']) !== false ) {
            // 	// try {
            // 	// 	$api_response = wp_remote_get( 'https://api.qrserver.com/v1/read-qr-code/?fileurl=' . urlencode(trim($this->ZelleQRCode)) );
            // 	// 	$response = ! is_wp_error( $api_response ) ? wp_remote_retrieve_body( $api_response ) : null;
            // 	// 	$result = $response ? json_decode( $response, true ) : null;
            // 	// 	if ( !empty($result) && json_last_error() === JSON_ERROR_NONE ) {
            // 	// 		// $data = $result['data'];
            // 	// 		// if ( !empty($data) && strpos($data, 'https://enroll.zellepay.com/qr-codes') !== false ) {
            // 	// 		// 	$payment_url = esc_attr(trim($data));
            // 	// 		// }
            // 	// 	}
            // 	// } catch (\Throwable $th) {
            // 	// 	// // Executed only in PHP 7, will not match in PHP 5.x
            // 	// 	// print_r($th);
            // 	// 	$this->wcz_log( "zelle_url: " . $th->getMessage(), 'error' );
            // 	// } catch (\Exception $e) {
            // 	// 	// // Executed only in PHP 5.x, will not be reached in PHP 7
            // 	// 	// print_r($e);
            // 	// 	$this->wcz_log( "zelle_url: " . $e->getMessage(), 'error' );
            // 	// }
            // 	$payment_url = !empty($payment_url) ? $payment_url : $this->ZelleQRCode;
            // } else if ( !empty($this->ZelleQRCode) ) {
            // 	$payment_url = $this->ZelleQRCode;
            // }
            // return esc_attr(trim($payment_url));
            // if ( empty($this->ReceiverZelleOwner) ) return esc_attr(trim($payment_url));
            // if (!empty(trim($this->ReceiverZELLENo))) {
            // 	// $payment_url = esc_attr( 'https://enroll.zellepay.com/qr-codes'. esc_attr( trim($this->ReceiverZELLENo) ) );
            // 	$data = array("token" => esc_attr( substr(filter_var(str_replace("-", "", trim($this->ReceiverZELLENo) ), FILTER_SANITIZE_NUMBER_INT), -10) ), "action" => "payment", "name" => esc_attr( strtoupper( explode(" ",trim($this->ReceiverZelleOwner))[0] ) ) );
            // }
            // if (!empty(trim($this->ReceiverZELLEEmail))) {
            // 	// $payment_url = esc_attr( 'https://enroll.zellepay.com/qr-codes'. esc_attr( trim($this->ReceiverZELLEEmail) ) );
            // 	$data = array("token" => esc_attr( trim($this->ReceiverZELLEEmail) ), "action" => "payment", "name" => esc_attr( strtoupper( explode(" ",trim($this->ReceiverZelleOwner))[0] ) ) );
            // }
            // // // unset($data['name']);
            // $data['amount'] = floatval($amount);
            // $payment_url = esc_attr( 'https://enroll.zellepay.com/qr-codes'. base64_encode(json_encode($data)) );
            // // wp_die(json_encode($data));
            // return esc_attr(trim($payment_url));
        }

        public function wc_zelle_qrcode_url( $amount = 0, $note = '' ) {
            $qr_code_url = "";
            $payment_url = $this->wc_zelle_url( $amount );
            if ( strpos( $payment_url, 'https://enroll.zellepay.com/qr-codes' ) === false ) {
                $qr_code_url = $payment_url;
            } else {
                if ( !empty( $payment_url ) ) {
                    $qr_code_url = "https://emailreceipts.io/qr?d=150&t=" . urlencode( $payment_url );
                }
            }
            return esc_attr( $qr_code_url );
        }

        public function wc_zelle_qrcode( $amount = 0, $type = "simple", $note = '' ) {
            $qr_code = "";
            $payment_url = $this->wc_zelle_url( $amount );
            $qr_code_url = $this->wc_zelle_qrcode_url( $amount );
            if ( empty( trim( $qr_code_url ) ) ) {
                return $qr_code;
            }
            if ( strpos( $payment_url, 'https://enroll.zellepay.com/qr-codes' ) === false ) {
                $qr_code = '<img style="float: none!important; min-height:250px; min-width:250px; max-height:auto!important; max-width:250px!important;" alt="' . esc_attr( $this->method_title ) . ' link" src="' . esc_attr( $qr_code_url ) . '">';
                $qr_code = '<a class="qr" href="' . esc_url( $payment_url ) . '" target="_blank">' . $qr_code . '</a>';
                return wp_kses_post( $qr_code );
            }
            if ( $type === "advanced" ) {
                $qr_code .= '<a href="' . esc_url( $payment_url ) . '" target="_blank">';
                // $qr_code .= '<p>' . esc_html__( 'If using the Zelle app, scan/click below', WCZELLE_PLUGIN_TEXT_DOMAIN ) . ':</p>';
                $default_qrcode = '<img class="logo-qr mb-1" width="150px" height="150px" src="' . esc_attr( $qr_code_url ) . '" />';
                $qr_code .= '<div id="wc_zelle_qrcode">' . $default_qrcode . '</div>';
                $qr_code .= '</a><p class="text-center mb-1">' . esc_html__( 'Scan with your Zelle/Bank app', WCZELLE_PLUGIN_TEXT_DOMAIN ) . '<br />' . esc_html__( 'or click the button below', WCZELLE_PLUGIN_TEXT_DOMAIN ) . '</p>
				<a class="btn btn-dark" role="button" href="' . esc_url( $payment_url ) . '" target="_blank" style="padding: 10px 35px;border-radius: 30px;">Pay with Zelle  <img width="30px" height="30px" alt="Zelle logo" src="' . esc_attr( WCZELLE_PLUGIN_DIR_URL . 'assets/images/zelle_35.png' ) . '" /></a>';
            } else {
                $qr_code = '<a class="logo-qr" href="' . esc_url( $payment_url ) . '" target="_blank"><img style="float: none!important; max-height:150px!important; max-width:100px!important;" alt="' . esc_attr( $this->method_title ) . ' link" src="' . esc_attr( $qr_code_url ) . '"></a>';
                // // or with local QR code JS, no logo, no button
                // $qr_code .= '<a href="'. esc_url( $payment_url ) . '" target="_blank">';
                // // $qr_code .= '<p>' . esc_html__( 'If using the Zelle app, scan/click below', WCZELLE_PLUGIN_TEXT_DOMAIN ) . ':</p>';
                // $default_qrcode = '<img class="logo-qr mb-1" width="150px" height="150px" src="'. esc_attr( $qr_code_url ) . '" />';
                // $qr_code .= '<div id="wc_zelle_qrcode">' . $default_qrcode . '</div>';
                // $qr_code .= '</a>';
            }
            return wp_kses_post( $qr_code );
        }

        public function wc_zelle_emrcpts_connect_url() {
            $emrcpts_connect_url = '';
            if ( !is_admin() ) {
                return $emrcpts_connect_url;
            }
            global $current_user;
            $first_name = '';
            $last_name = '';
            $phone = '';
            if ( $current_user && is_php_version_compatible( '7.0' ) ) {
                $first_name = $current_user->user_firstname ?? get_user_meta( get_current_user_id(), 'first_name', true ) ?? '';
                $last_name = $current_user->user_lastname ?? get_user_meta( get_current_user_id(), 'last_name', true ) ?? '';
                $phone = get_user_meta( get_current_user_id(), 'billing_phone', true ) ?? '';
            } else {
                if ( $current_user ) {
                    $first_name = ( $current_user->user_firstname ? $current_user->user_firstname : get_user_meta( get_current_user_id(), 'first_name', true ) );
                    $last_name = ( $current_user->user_lastname ? $current_user->user_lastname : get_user_meta( get_current_user_id(), 'last_name', true ) );
                    $phone = get_user_meta( get_current_user_id(), 'billing_phone', true );
                }
            }
            $sn = urlencode( get_bloginfo( "name" ) );
            $su = urlencode( get_site_url() );
            $fn = urlencode( $first_name );
            $ln = urlencode( $last_name );
            $ph = urlencode( $phone );
            $em = urlencode( get_bloginfo( "admin_email" ) );
            $th = urlencode( get_site_icon_url() );
            $_wpnonce = urlencode( wp_create_nonce( 'connect_store_to_emailreceipts' ) );
            $ref = WCZELLE_PLUGIN_SLUG;
            $zn = urlencode( $this->ReceiverZelleOwner );
            $zp = urlencode( $this->ReceiverZELLENo );
            $ze = urlencode( $this->ReceiverZELLEEmail );
            // $square = ' <a href="https://emailreceipts.io/store/connect?sn=' . urlencode(get_bloginfo("name")) . '&su=' . urlencode(get_site_url()) . '&fn=' . urlencode($first_name) . '&ln=' . urlencode($last_name) . '&em=' . urlencode(get_bloginfo("admin_email")) . '&ph=' . urlencode($phone) . '&th=' . urlencode(get_site_icon_url()) . '&_wpnonce=' . urlencode(wp_create_nonce( 'connect_store_to_emailreceipts' )) . '&ref=' . WCZELLE_PLUGIN_SLUG . '" target="_blank">Get it here</a>';
            $emrcpts_connect_url = "https://emailreceipts.io/store/connect?sn={$sn}&su={$su}&fn={$fn}&ln={$ln}&em={$em}&ph={$ph}&th={$th}&_wpnonce={$_wpnonce}&ref={$ref}";
            return $emrcpts_connect_url;
        }

        /**
         * Logging method.
         *
         * @param string $message Log message.
         * @param string $level Optional. Default 'info'
         * Possible values: emergency|alert|critical|error|warning|notice|info|debug.
         */
        protected function wcz_log( $message, $level = 'info' ) {
            // logs at admin.php?page=wc-status&tab=logs
            if ( !empty( $message ) && $this->enable_debug == 'yes' && wc_zelle_is_pro() ) {
                $logger = wc_get_logger();
                // $logger->debug( 'Detailed debug information', $context );
                // $logger->info( 'Interesting events', $context );
                // $logger->notice( 'Normal but significant events', $context );
                // $logger->warning( 'Exceptional occurrences that are not errors', $context );
                // $logger->error( 'Runtime errors that do not require immediate', $context );
                // $logger->critical( 'Critical conditions', $context );
                // $logger->alert( 'Action must be taken immediately', $context );
                // $logger->emergency( 'System is unusable', $context );
                // // The `log` method accepts any valid level as its first argument.
                // // $context may hold arbitrary data.
                // // If you provide a "source", it will be used to group your logs.
                // $context = array( 'source' => 'my-extension-name' );
                // $logger->log( 'debug', '<- Provide a level', $context );
                $logger->log( $level, wp_strip_all_tags( wp_kses_post( $message ) ), array(
                    'source' => $this->id,
                ) );
            }
        }

        /**
         * Logging method.
         * @param string $message
         */
        // public static function log($message) {
        //     if (TRUE || self::$log_enabled) {
        //         if (empty(self::$log)) {
        //             self::$log = new WC_Logger();
        //         }
        //         self::$log->add($this->id, $message);
        //     }
        // }
        // // Check if we are forcing SSL on checkout pages
        // public function do_ssl_check() {
        //     if (( function_exists('wc_site_is_https') && !wc_site_is_https() ) && ( 'no' === get_option('woocommerce_force_ssl_checkout') && !class_exists('WordPressHTTPS') )) {
        //         echo '<div class="error"><p>' . sprintf(__('<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href="%s">forcing the checkout pages to be secured.</a>', WCZELLE_PLUGIN_TEXT_DOMAIN), $this->method_title, admin_url('admin.php?page=wc-settings&tab=checkout')) . '</p></div>';
        //     }
        // }
        // // Check if this gateway is enabled
        // public function is_available() {
        //     if (empty($this->ReceiverZELLEEmail) && empty($this->ReceiverZELLENo)) return false;
        //     return true;
        // }
        // Checkout page
        public function payment_fields() {
            require_once WCZELLE_PLUGIN_DIR . 'includes/pages/checkout.php';
        }

        // Payment Custom JS and CSS
        public function wc_zelle_payment_scripts() {
            if ( 'no' === $this->enabled || empty( $this->ReceiverZELLEEmail ) && empty( $this->ReceiverZELLENo ) ) {
                return;
            }
            require_once WCZELLE_PLUGIN_DIR . 'includes/functions/payment_scripts.php';
        }

        /**
         * Memo line for Zelle (customer name, order number, etc.).
         *
         * @param WC_Order|null $order Order object.
         * @return string
         */
        public function wc_zelle_get_memo_text( $order ) {
            if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
                return '';
            }
            $template = $this->get_option( 'memo_template' );
            if ( $template === '' || false === $template ) {
                $template = '{customer_name} - {order_number}';
            }
            $name = trim( $order->get_formatted_billing_full_name() );
            if ( $name === '' ) {
                $name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
            }
            $replacements = array(
                '{customer_name}' => $name,
                '{order_number}'  => $order->get_order_number(),
                '{order_id}'      => (string) $order->get_id(),
            );
            $out = str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
            return apply_filters( 'wc_zelle_memo_text', $out, $order, $template );
        }

        /**
         * Memo line for display (modal, etc.): always includes order number when template is empty or yields only separators.
         *
         * @param WC_Order|null $order Order object.
         * @return string
         */
        public function wc_zelle_get_memo_text_resolved( $order ) {
            if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
                return '';
            }
            $memo = trim( (string) $this->wc_zelle_get_memo_text( $order ) );
            $memo = preg_replace( '/^[\s\-–—]+/u', '', $memo );
            if ( $memo === '' ) {
                $memo = sprintf(
                    /* translators: %s: WooCommerce order number */
                    __( 'Order %s', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                    $order->get_order_number()
                );
            }
            return apply_filters( 'wc_zelle_memo_text_resolved', $memo, $order );
        }

        /**
         * Hours after order placement before auto-cancellation (sanitized).
         *
         * @return int
         */
        public function wc_zelle_get_payment_deadline_hours() {
            $h = absint( $this->get_option( 'zelle_cancel_unpaid_hours', 24 ) );
            if ( $h < 1 ) {
                $h = 24;
            }
            return min( 720, $h );
        }

        /**
         * Customer-facing sentence about payment deadline (empty if feature off).
         *
         * @return string
         */
        public function wc_zelle_get_payment_deadline_customer_notice() {
            if ( 'yes' !== $this->get_option( 'zelle_cancel_unpaid' ) ) {
                return '';
            }
            $h = $this->wc_zelle_get_payment_deadline_hours();
            return sprintf(
                _n(
                    'If we do not receive your Zelle payment within %d hour, this order may be cancelled.',
                    'If we do not receive your Zelle payment within %d hours, this order may be cancelled.',
                    $h,
                    WCZELLE_PLUGIN_TEXT_DOMAIN
                ),
                $h
            );
        }

        /**
         * Scripts/styles for thank-you memo copy and optional modal.
         */
        public function wc_zelle_thankyou_assets() {
            if ( 'no' === $this->enabled ) {
                return;
            }
            if ( ! function_exists( 'is_order_received_page' ) || ! is_order_received_page() ) {
                return;
            }
            if ( empty( $_GET['key'] ) ) {
                return;
            }
            $order_id = absint( wc_get_order_id_by_order_key( wc_clean( wp_unslash( $_GET['key'] ) ) ) );
            $order = wc_get_order( $order_id );
            if ( ! $order || $order->get_payment_method() !== $this->id ) {
                return;
            }
            $need_modal = ( 'yes' === $this->show_zelle_modal );
            $need_memo = ( 'yes' === $this->memo_order_number );
            $ver = WCZELLE_PLUGIN_VERSION;
            wp_enqueue_style( 'wc-zelle-thankyou', WCZELLE_PLUGIN_DIR_URL . 'assets/css/thankyou.css', array(), $ver );
            if ( $need_modal || $need_memo ) {
                wp_enqueue_script( 'wc-zelle-thankyou', WCZELLE_PLUGIN_DIR_URL . 'assets/js/thankyou-modal.js', array(), $ver, true );
                wp_localize_script(
                    'wc-zelle-thankyou',
                    'wcZelleThankyou',
                    array(
                        'autoOpenModal' => ( $need_modal && 'yes' === $this->zelle_modal_auto_open ),
                        'i18n'          => array(
                            'copied'     => __( 'Copied', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                            'copyFailed' => __( 'Could not copy', WCZELLE_PLUGIN_TEXT_DOMAIN ),
                        ),
                    )
                );
            }
        }

        // Thank you page
        public function wc_zelle_thankyou_page( $order_id ) {
            if ( !$order_id ) {
                return;
            }
            $order = wc_get_order( $order_id );
            // deprecated since WC 3.0.0
            // $order = new WC_Order( $order_id ); // For WC 2.5.0+
            if ( $order && $this->id === $order->get_payment_method() ) {
                require_once WCZELLE_PLUGIN_DIR . 'includes/pages/thankyou.php';
            }
        }

        public function wc_zelle_processed( $order_id, $posted_data, $order ) {
            if ( !$order_id || !$order ) {
                return;
            }
            if ( $this->id === $order->get_payment_method() ) {
                require_once WCZELLE_PLUGIN_DIR . 'includes/functions/order_processed.php';
            }
        }

        // Add content to the WC emails
        public function wc_zelle_email_instructions( $order, $sent_to_admin, $plain_text = false ) {
            if ( !$sent_to_admin && 'on-hold' === $order->get_status() && $this->id === $order->get_payment_method() ) {
                $order_id = ( method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id );
                require_once WCZELLE_PLUGIN_DIR . 'includes/notifications/email.php';
            }
        }

        // Zoho support
        public function wczelle_zoho_notification( $order_id ) {
            if ( !$order_id ) {
                return;
            }
            $order = wc_get_order( $order_id );
            if ( !$order ) {
                return;
            }
        }

        // validate zelle_email
        public function validate_fields() {
            if ( isset( $_POST['zelle_email'] ) ) {
                $zelle_email = sanitize_text_field( trim( $_POST['zelle_email'] ) );
                if ( !$zelle_email || filter_var( $zelle_email, FILTER_VALIDATE_EMAIL ) == false ) {
                    wc_add_notice( esc_html( __( 'Invalid/Missing Zelle email', WCZELLE_PLUGIN_TEXT_DOMAIN ) ), 'error' );
                    $this->wcz_log( esc_html( __( 'Checkout: Invalid/Missing Zelle email', WCZELLE_PLUGIN_TEXT_DOMAIN ) ), 'error' );
                }
            }
            if ( isset( $_POST['zelle_no'] ) ) {
                $zelle_no = sanitize_text_field( trim( $_POST['zelle_no'] ) );
                if ( !$zelle_no || filter_var( $zelle_no, FILTER_SANITIZE_NUMBER_INT ) == false ) {
                    wc_add_notice( esc_html( __( 'Invalid/Missing Zelle phone number', WCZELLE_PLUGIN_TEXT_DOMAIN ) ), 'error' );
                    $this->wcz_log( esc_html( __( 'Checkout: Invalid/Missing Zelle phone number', WCZELLE_PLUGIN_TEXT_DOMAIN ) ), 'error' );
                }
            }
            if ( isset( $_POST['zelle_sender'] ) ) {
                $accountid_meta = sanitize_text_field( trim( $_POST['zelle_sender'] ) );
                // Validate Zelle email/phone
                if ( empty( $accountid_meta ) ) {
                    wc_add_notice( esc_html( __( 'Missing Zelle sender information', WCZELLE_PLUGIN_TEXT_DOMAIN ) ), 'error' );
                    $this->wcz_log( esc_html( __( 'Checkout: Missing Zelle sender information', WCZELLE_PLUGIN_TEXT_DOMAIN ) ), 'error' );
                } else {
                    if ( filter_var( $accountid_meta, FILTER_VALIDATE_EMAIL ) !== false ) {
                        //  && $accountid_meta == filter_var($accountid_meta, FILTER_VALIDATE_EMAIL)
                        // echo("$accountid_meta is a valid email address");
                        // wc_add_notice( esc_html( __("$accountid_meta is a valid email address", WCZELLE_PLUGIN_TEXT_DOMAIN ) ), 'success' );
                    } else {
                        if ( filter_var( $accountid_meta, FILTER_SANITIZE_NUMBER_INT ) !== false ) {
                            //  && $accountid_meta == filter_var($accountid_meta, FILTER_SANITIZE_NUMBER_INT)
                            // echo("$accountid_meta is a valid phone number");
                            // wc_add_notice( esc_html( __("$accountid_meta is a valid phone number", WCZELLE_PLUGIN_TEXT_DOMAIN ) ), 'success' );
                        } else {
                            // echo("$accountid_meta is not a valid phone number nor email");
                            wc_add_notice( esc_html( __( "Invalid Zelle sender information: {$accountid_meta} is not a valid phone number nor email", WCZELLE_PLUGIN_TEXT_DOMAIN ) ), 'error' );
                            $this->wcz_log( esc_html( __( "Checkout: Invalid Zelle sender information: {$accountid_meta} is not a valid phone number nor email", WCZELLE_PLUGIN_TEXT_DOMAIN ) ), 'error' );
                        }
                    }
                }
            }
            if ( isset( $_POST['do_not_checkout'] ) ) {
                wc_add_notice( esc_html( __( 'Please try another payment method', WCZELLE_PLUGIN_TEXT_DOMAIN ) ), 'error' );
                $this->wcz_log( esc_html( __( 'Checkout: Please try another payment method', WCZELLE_PLUGIN_TEXT_DOMAIN ) ), 'error' );
            }
        }

        // Process Order
        public function process_payment( $order_id ) {
            try {
                if ( !$order_id ) {
                    wc_add_notice( '<p>Something went terribly wrong.</p><p>Order information is missing</p>', 'error' );
                    $this->wcz_log( 'Checkout: Something went terribly wrong. Order information is missing</p>', 'error' );
                    return;
                }
                $order = wc_get_order( $order_id );
                if ( !$order ) {
                    wc_add_notice( '<p>Something went terribly wrong.</p><p>Order information is missing</p>', 'error' );
                    $this->wcz_log( 'Checkout: Something went terribly wrong. Order information is missing', 'error' );
                    return;
                }
                if ( !is_wp_error( $order ) && $this->id === $order->get_payment_method() ) {
                    if ( isset( $_POST['zelle_sender'] ) ) {
                        $accountid_meta = sanitize_text_field( trim( $_POST['zelle_sender'] ) );
                        if ( $accountid_meta ) {
                            // update_post_meta($order_id, 'zelle_sender', $accountid_meta);
                            if ( filter_var( $accountid_meta, FILTER_VALIDATE_EMAIL ) !== false ) {
                                // echo("$accountid_meta is a valid email address");
                                $order->update_meta_data( 'zelle_sender', filter_var( $accountid_meta, FILTER_VALIDATE_EMAIL ) );
                                $order->save();
                            } else {
                                if ( filter_var( $accountid_meta, FILTER_SANITIZE_NUMBER_INT ) !== false ) {
                                    // echo("$accountid_meta is a valid phone number");
                                    $order->update_meta_data( 'zelle_sender', filter_var( $accountid_meta, FILTER_SANITIZE_NUMBER_INT ) );
                                    $order->save();
                                }
                            }
                            $this->wcz_log( "Checkout: {$accountid_meta}", 'info' );
                        }
                    }
                    // reduce inventory
                    if ( wc_zelle_is_pro() && $this->ZelleStockManagement == 'yes' ) {
                    } else {
                        // $order->reduce_order_stock(); // deprecated
                        wc_reduce_stock_levels( $order_id );
                    }
                    // Mark as on-hold (we're awaiting the payment).
                    if ( wc_zelle_is_pro() && $this->processOrder == 'yes' ) {
                        $order->payment_complete();
                    } else {
                        $order->update_status( apply_filters( 'woocommerce_zelle_process_payment_order_status', 'on-hold', $order ), __( "Waiting for the {$this->method_title} payment", WCZELLE_PLUGIN_TEXT_DOMAIN ) );
                    }
                    if ( wc_zelle_is_pro() && 'yes' == $this->enableNote ) {
                        require_once WCZELLE_PLUGIN_DIR . 'includes/notifications/note.php';
                    }
                    global $woocommerce;
                    $woocommerce->cart->empty_cart();
                    // Redirect to the thank you page
                    return array(
                        'result'   => 'success',
                        'redirect' => $this->get_return_url( $order ),
                    );
                } else {
                    wc_add_notice( 'Connection error.', 'error' );
                    $this->wcz_log( 'Checkout: Connection error.', 'error' );
                    return;
                }
            } catch ( \Throwable $th ) {
                // print_r($th);
                wc_add_notice( "Something went wrong. {$th}", 'error' );
                $this->wcz_log( "Checkout: Something went wrong. {$th}", 'error' );
                return;
            }
        }

        // Webhook
        public function webhook() {
            return;
            // $order = wc_get_order( $_GET['id'] );
            // $order->payment_complete();
            // update_option('webhook_debug', $_GET);
        }

    }

} else {
    require_once WCZELLE_PLUGIN_DIR . 'includes/notifications/woocommerce.php';
}