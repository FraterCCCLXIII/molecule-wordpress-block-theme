<?php

/**
 * This filter ads the Green.Money Status Update All button to the top of WooCommerce Orders page
 */
add_filter('views_edit-shop_order', function ($args) {
    echo '<button class="button wc-action-button wc-action-button-view view status_update_all" href="#" aria-label="Status Update">Green.Money Status Update All</button><br />';
    return $args;
});

add_filter('woocommerce_checkout_fields', 'enable_phone_required');

function enable_phone_required($fields)
{
    $fields['billing']['billing_phone']['required'] = true;
    $fields['shipping']['shipping_phone']['required'] = true;
    return $fields;
}

add_action('admin_enqueue_scripts', 'green_money_js_enqueue');

/**
 * Enqueue the js
 *
 * @param  string $hook
 */
function green_money_js_enqueue($hook)
{
    wp_enqueue_script('status-update-script', plugins_url('../js/gmpg_scripts.js', __FILE__), array('jquery'));
    wp_localize_script('status-update-script', 'ajax_object_name', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'security' => wp_create_nonce("status_update_nonce")
    ));
}

/**
 * Script to run status updates on ALL orders under current user
 */
function gmpg_status_update_all()
{
    check_ajax_referer('status_update_nonce', 'security');
    require_once('class-wc-gateway.php');
    $gateway = new WC_GreenPay_Gateway();

    //Get user id
    $current_account = wp_get_current_user();
    $current_customer = $current_account->ID;

    $args = array(
        'customer' => $current_customer,
        'limit' => -1,
    );

    $orders = wc_get_orders($args);

    foreach ($orders as $order) {
        if ($order->get_status() == 'completed' || $order->get_payment_method() != 'greenmoney' || $order->get_status() == 'failed' || $order->get_status() == 'refunded' || $order->get_status() == 'cancelled') {
            continue;
        }

        //Get $order and $results
        $order_id = $order->get_id();
        $results = $gateway->check_status($order_id);

        if ($results) {
            $resultCode = $results->Result->Result;
            $resultDescription = $results->Result->ResultDescription;
            $verifyResultCode = $results->Check->VerifyResult;
            $verifyResultDescription = $results->Check->VerifyResultDescription;

            if ($resultCode == "0") {
                if ($verifyResultCode == '0') { //Success
                    $rejected = (strtolower($results->Check->Rejected) == "true");
                    if (!$rejected) {
                        $deleted = (strtolower($results->Check->Deleted) == "true");
                        if (!$deleted) {
                            $processed = (strtolower($results->Check->Processed) == "true");
                            if ($processed) {
                                $order->update_status('processing');
                                $order->add_order_note('Check has been processed by Green.Money');
                            } else {
                                $order->update_status('on-hold');
                                $order->add_order_note('Verification process completed by Green.Money and check is in queue to be processed. Once the check has processed at Green, we will update your order status from On-Hold to Processing.');
                            }
                        } else { //Deleted
                            $order->update_status('failed');
                            $order->add_order_note('Verification process completed by Green.Money and verification status returned is: Deleted by Green.Money or by merchant.');
                        }
                    } else { //Rejected
                        $order->update_status('failed');
                        $order->add_order_note('Verification process completed by Green.Money and verification status returned is: Rejected by Green.Money.');
                    }
                } else if ($verifyResultCode == '1') { //This case should probably never happen
                    $order->update_status('on-hold');
                    $order->add_order_note('Verification process completed by Green.Money and verification status returned is: Will be verified on next batch.');
                } else if ($verifyResultCode == '2') {
                    if ($gateway->verification_mode == 'permissive') { //Permissive mode
                        $args = array(
                            'post_id' => $order->get_id(),
                            'orderby' => 'comment_ID',
                            'order'   => 'DESC',
                            'approve' => 'approve',
                            'type'    => 'order_note',
                            'number'  => 1
                        );

                        remove_filter('comments_clauses', array('WC_Comments', 'exclude_order_comments'), 10, 1);
                        $notes = get_comments($args);
                        add_filter('comments_clauses', array('WC_Comments', 'exclude_order_comments'), 10, 1);
                        //Check order notes to see if last note was a manual override
                        if ($notes[0]->comment_content == 'Risky/Bad check found in Green.Moneym and overridden. Check will be processed by Green.Money.') {
                            $order->add_order_note('Order marked Risky/Bad and previously overridden.');
                        } else if ($notes[0]->comment_content == 'Order marked Risky/Bad and previously overridden.') {
                            $order->add_order_note('Order marked Risky/Bad and previously overridden.');
                        } else {
                            $order->update_status('on-hold');
                            $order->add_order_note('Verification process completed by Green.Money and verification status returned is: Risky or Bad. Can be manually overridden by selecting the \'Green.Money Override Risky/Bad\' order action from the Order actions dropdown.');
                        }
                    } else { //Legacy mode
                        $order->update_status('failed');
                        $order->add_order_note('Verification process completed by Green.Money and verification status returned is: Risky or Bad. Will require manual override in Green.Money Processing portal.');
                    }
                } else if ($verifyResultCode == '3') {
                    $order->update_status('failed');
                    $order->add_order_note('Verification process completed by Green.Money and verification status returned is: Risky or Bad. Cannot be overridden.');
                } else if ($verifyResultCode == '4') {
                    $order->add_order_note('Verification process completed by Green.Money and verification status returned is: Verification system offline. Please try again later.');
                } else {
                    $order->add_order_note('Verification process completed by Green.Money and verification status returned is: Unknown failure.');
                    echo "Verification not completed.<br/>Error Code: {$verifyResultCode}<br/>Error: {$resultDescription}<br/>";
                }
            } else if ($resultCode == '1' || $resultCode == '2') { //client id not in database
                $order->add_order_note("WooCommerce ran a Green.Money Status Update on this check and it appears that it was created in a different API than your current verification mode: " . gmpg_get_mode($endpoint) . ". Please ensure you're using the correct API mode before running another status update or delete this order!");
            } else if ($resultCode == '24') {
                $order->add_order_note(__('Green.Money check is not accepted (Error code: 24, Description: Routing number not found).', 'woocommerce-gateway-green-money'));
            } else if ($resultCode == '51') {
                $order->add_order_note('Error: ' . $resultDescription . ' This may be caused by incorrect API credentials, this order was created in a different API mode than ' . gmpg_get_mode($endpoint) . ', or there was some unknown error.');
            } else { //Check not found in Green system
                $order->update_status('failed');
                $order->add_order_note('Verification process could not be determined due to the following error: Check not found.');
                echo "Verification not completed.<br/>Error Code: {$resultCode}<br/>Error: {$resultDescription}<br/>";
            }
        } else {
            echo "GATEWAY ERROR: " . $gateway->green_money_getLastError();
        }
    } //END foreach($orders as $order)
} //END gmpg_status_update_all()

add_action("wp_ajax_status_update_all_hook", "gmpg_status_update_all");

add_action("wp_ajax_start_session", "gmpg_start_session");
add_action("wp_ajax_nopriv_start_session", "gmpg_start_session");
function gmpg_start_session()
{
    require_once('class-wc-gateway.php');
    $gateway = new WC_GreenPay_Gateway();
    $s = $_POST["s"];
    check_ajax_referer('start_session');

    $options = get_option('gmpg_settings');
    $client_id = $options['gmpg_client_id'];
    $api_pass = $options['gmpg_api_password'];
    $endpoint = $options['gmpg_api_endpoint'];

    if ($client_id && $api_pass && $endpoint) {
        $gateway->log("Attempting to call start_session with id: {$s}.");
        echo $gateway->start_session($s);
    } else {
        echo false;
    }

    die();
}

/**
 * Add the custom order action 'GreenPay™ Status Update' in the WooCommerce Orders menu
 * The 'GreenPay™ Status Update' action is in an 'Order actions' dropdown
 * Select the 'GreenPay™ Status Update' option and click the execute button to run the status update on the individual order
 *
 * @param array $actions
 */
function gmpg_single_status_update_order_action($actions)
{
    $actions['gmpg_single_status_update_order_action'] = __('GreenPay™ Status Update', 'woocommerce-gateway-green-money');
    return $actions;
}
add_action('woocommerce_order_actions', 'gmpg_single_status_update_order_action');

/**
 * Script to run status updates on ONE order
 *
 * @param WC_Order $order
 */
function gmpg_single_status_update($order)
{
    require_once('class-wc-gateway.php');

    if ($order->get_status() == 'completed' || $order->get_payment_method() != 'greenmoney' || $order->get_status() == 'failed' || $order->get_status() == 'refunded' || $order->get_status() == 'cancelled') {
        return;
    }

    $options = get_option('gmpg_settings');
    $verification_mode = $options['gmpg_override_risky_option'];
    $gateway = new WC_GreenPay_Gateway();
    $results = $gateway->check_status($order->get_id());

    if ($results) { //The call succeeded, time to parse
        if ($results->Result->Result == '0') { //Check was found in the system
            if ($results->Check->VerifyResult == '0') { //Success
                if ($results->Check->Rejected != 'True') {
                    if ($results->Check->Deleted != 'True') {
                        if ($results->Check->Processed != 'True') {
                            $order->update_status('on-hold');
                            $order->add_order_note('Verification process completed by Green.Money and check is in queue to be processed. Once the check has processed at Green, we will update your order status from On-Hold to Processing.');
                        } else {
                            $order->update_status('processing');
                            $order->add_order_note('Check has been processed by Green.Money.');
                        }
                    } else { //Deleted
                        $order->update_status('failed');
                        $order->add_order_note('Verification process completed by Green.Money and verification status returned is: Deleted by Green.Money or by merchant.');
                    }
                } else { //Rejected
                    $order->update_status('failed');
                    $order->add_order_note('Verification process completed by Green.Money and verification status returned is: Rejected by Green.Money.');
                }
            } else if ($results->Check->VerifyResult == '1') { //This case should never happen
                $order->update_status('on-hold');
                $order->add_order_note('Verification process completed by Green.Money and verification status returned is: Will be verified on next batch.');
            } else if ($results->Check->VerifyResult == '2') {
                if ($verification_mode == 'permissive') { //Permissive mode
                    $args = array(
                        'post_id' => $order->get_id(),
                        'orderby' => 'comment_ID',
                        'order'   => 'DESC',
                        'approve' => 'approve',
                        'type'    => 'order_note',
                        'number'  => 1
                    );

                    remove_filter('comments_clauses', array('WC_Comments', 'exclude_order_comments'), 10, 1);
                    $notes = get_comments($args);
                    add_filter('comments_clauses', array('WC_Comments', 'exclude_order_comments'), 10, 1);

                    //Check order notes to see if last note was a manual override
                    if ($notes[0]->comment_content == 'Risky/Bad check found in Green.Money system and overridden. Check will be processed by Green.Money.') {
                        $order->add_order_note('Order marked Risky/Bad and previously overridden.');
                    } else if ($notes[0]->comment_content == 'Order marked Risky/Bad and previously overridden.') {
                        $order->add_order_note('Order marked Risky/Bad and previously overridden.');
                    } else {
                        $order->update_status('on-hold');
                        $order->add_order_note('Verification process completed by Green.Money and verification status returned is: Risky or Bad. Can be manually overridden by selecting the \'Green.Money Override Risky/Bad\' order action from the Order actions dropdown.');
                    }
                } else { //Legacy mode
                    $order->update_status('failed');
                    $order->add_order_note('Verification process completed by Green.Money and verification status returned is: Risky or Bad. Will require manual override in Green.Money portal if need processed. Must be in permissive mode (this can be changed from the GreenPay™ options menu). Once in permissive mode, click the GreenPay™ status update all button, or do an individual status update, and then do the \'GreenPay™ Override Risky/Bad\' order action to override.');
                }
            } else if ($results->Check->VerifyResult == '3') {
                $order->update_status('failed');
                $order->add_order_note('Verification process completed by Green.Money and verification status returned is: Risky or Bad. Cannot be overridden.');
            } else if ($results->Check->VerifyResult == '4') {
                $order->update_status('failed');
                $order->add_order_note('Verification process completed by Green.Money and verification status returned is: Verification system offline. Please try again later.');
            } else {
                $order->update_status('failed');
                $order->add_order_note('Verification process completed by Green.Money and verification status returned is: Unknown failure.');
                echo "Verification not completed.<br/>Error Code: {$results->Check->VerifyResult}<br/>Error: {$results->Result->ResultDescription}<br/>";
            }
        } else if ($results->Result->Result == '1' || $results->Result->Result == '2') { //wrong api
            $order->add_order_note("WooCommerce ran a Green.Money Status Update on this check and it appears that it was created in a different API than your current verification mode: " . gmpg_get_mode($endpoint) . ". Please ensure you're using the correct API mode before running another status update or delete this order!");
        } else if ($results->Result->Result == '24') {
            $order->add_order_note(__('Green.Money check is not accepted (Error code: 24, Description: Routing number not found).', 'woocommerce-gateway-green-money'));
        } else if ($results->Result->Result == '51') {
            $order->add_order_note('Error: ' . $results->Result->ResultDescription . ' This may be caused by incorrect API credentials, this order was created in a different API mode than ' . gmpg_get_mode($endpoint) . ', or there was some unknown error.');
        } else { //Check not found in Green system
            $order->update_status('failed');
            $order->add_order_note('Verification process completed by Green.Money and verification status returned is: check not found.');
            echo "Verification not completed.<br/>Error Code: {$results->Result->Result}<br/>Error: {$results->Result->ResultDescription}<br/>";
        }
    } else { //The call failed!
        echo "GATEWAY ERROR: " . $gateway->green_money_getLastError();
    }
} //END gmpg_single_status_update()

add_action('woocommerce_order_action_gmpg_single_status_update_order_action', 'gmpg_single_status_update');

/**
 * Add the custom order action 'GreenPay™ Override Risky/Bad' in the WooCommerce Orders menu
 * The 'GreenPay™ Override Risky/Bad' action is in an 'Order actions' dropdown
 * Select the 'GreenPay™ Override Risky/Bad' option and click the execute button to run the override
 * on the selected check
 *
 * @param array $actions
 * @return array $actions
 */
function gmpg_override_risky_order_action($actions)
{
    global $theorder;
    $order = wc_get_order($theorder->get_id());
    $options = get_option('gmpg_settings');
    $verification_mode = $options['gmpg_override_risky_option'];

    if ($order->get_status() != 'on-hold') { //bail if order not on-hold or not in permissive mode
        return $actions;
    }
    if ($verification_mode != 'permissive') {
        return $actions;
    }
    $actions['gmpg_override_risky_order_action'] = __('GreenPay™ Override Risky/Bad', 'woocommerce-gateway-green-money');
    return $actions;
}
add_action('woocommerce_order_actions', 'gmpg_override_risky_order_action');

/**
 * Script to run override Risky/Bad checks
 *
 * @param WC_Order $order
 */
function gmpg_override_risky($order)
{
    require_once('class-wc-gateway.php');

    $gateway = new WC_GreenPay_Gateway();
    $options = get_option('gmpg_settings');

    $data = array(
        "Client_ID"    => $options['gmpg_client_id'],
        "ApiPassword" => $options['gmpg_api_password'],
        "Store" => untrailingslashit($gateway->useStoreURL), // Use gateway's configured URL (from settings)
        "OrderID" => $order->get_id()
    );

    $results = $gateway->callGreenAPI('VerificationOverride', $data, "WooCommerce.asmx");

    if ($results->Result->Result == '0') { //check found and overridden
        $order->update_status('on-hold');
        $order->add_order_note('Risky/Bad check found in Green.Money system and overridden. Check will be processed by Green.Money.');
    } else if ($results->Result->Result == '55' || $results->Result->Result == '89') {
        $order->update_status('on-hold');
        $order->add_order_note('Verification passed, override not necessary. Check will be processed by Green.Money.');
    } else if ($results->Result->Result == '56') {
        $order->update_status('failed');
        $order->add_order_note('Verification failed and cannot be overridden. Check will not be processed by Green.Money.');
    } else { //check not found in system. Mark as Failed
        $order->add_order_note('Check is not risky/bad and no override was performed.');
    }
} //END gmpg_override_risky()

add_action('woocommerce_order_action_gmpg_override_risky_order_action', 'gmpg_override_risky');

/**
 * Simply return what verification mode we're in
 * @param string $endpoint  either "Live" or "Test"
 */
function gmpg_get_mode($endpoint)
{
    if ($endpoint == 'https://greenbyphone.com/') {
        $mode = 'Live';
    } else {
        $mode = 'Test';
    }
    return $mode;
}
