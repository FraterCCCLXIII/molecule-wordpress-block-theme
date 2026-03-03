=== GreenPay(tm) by Green.Money ===
Plugin Name: GreenPay(tm) by Green.Money
Description: GreenPay(tm) gateway for WooCommerce
Author: Green.Money
Contributors: greenmoney
Version: 3.3.3
Author URI: http://www.green.money/
Copyright: � 2024 Green.Money
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Tested up to: 6.8.3
WC requires at least: 4.2.0
WC tested up to: 10.3.4


== DESCRIPTION ==
This is a plugin that extends WooCommerce with a GreenPay(tm) payment gateway option that can be used to process payment. When enabled
a GreenPay(tm) payment option is displayed on checkout giving users the ability to pay via a GreenPay(tm) eCheck that will be processed by the GreenPay(tm) system.
Installation documents: https://www.green.money/woo


== INSTALLATION ==
1. Download plugin documentation at http://www.green.money/woo

2. Download and unzip the latest release zip file.

3. If you use the WordPress plugin uploader to install this plugin skip to step 5.

4. Upload the entire plugin directory to your /wp-content/plugins/ directory.

5. Activate the plugin through the 'Plugins' menu in WordPress Administration.

6. Go to --> GreenPay(tm) in the dashboard left panel and configure your GreenPay(tm) settings.

== API Credentials ==
Email support@green.money with a subject of "API Credentials" and give a brief explanation of your needs and a representative will get back to you with API URL and password.

==  3.3.3  ==
* Fixed mobile browser dynamic height causing bank login issues 

==  3.3.2  ==
* Fixed website URL encoding issue

==  3.3.1  ==
* Fixed duplicate order payment issue causing order failures

==  3.3.0  ==
* Added support for WooCommerce HPOS

==  3.2.4  ==
* Corrected bug

==  3.2.3  ==
* Resolved race conditions and stabilized DOM handling

==  3.2.2  ==
* Fixed validation issue with manual entry

==  3.2.1  ==
* Fixed issue affecting Elementor and Classic checkouts

==  3.2.0  ==
* Introduction of recurring / subscription feature
* Fixed issue affecting runtime of the plugin after installation

==  3.1.11  ==
* Addressed bug affecting input controls due to conflicting Wordpress themes

==  3.1.10  ==
* Fixed remaining timing issues due to large asset loads

==  3.1.9  ==
* Substituted a built-in WooCommerce function to enhance compatibility across versions and custom setups

==  3.1.8  ==
* Added clean remounts for every updated_checkout event cycle

==  3.1.7  ==
* Fixed UI issue causing other html elements to overlay on top of the bank login widget

==  3.1.6  ==
* Fixed timing issue on Elementor checkout pages
* Phone field is now required for checkout

==  3.1.5  ==
* Fixed timing issue on BeBuilder checkout pages

==  3.1.4  ==
* Fixed loading timing issue on WooCommerce Classic Checkout pages
* Updated plugin compatibility versions 

==  3.1.3  ==
* Fixed timing issue on WooCommerce Classic Checkout pages

==  3.1.2  ==
* Fixed issue affecting other payment methods in classic checkout

==  3.1.1  ==
* Fixed issue affecting other payment methods

==  3.1.0  ==
* Reorganized front-end components for clearer structure, easier maintenance, and faster load times
* Introduced a validation step for certain bank institutions during payment setup to reduce failed transactions
* Updated the bank login button to include the Plaid logo for clearer identification
* Resolved issue where payment data would be cleared whenever the checkout form was updated

==  3.0.11  ==
* Fixed timing issue on WooCommerce Block Checkout pages

==  3.0.10  ==
* Security updates

==  3.0.9  ==
* Fixed Greenpay script injection for elementor checkout page
* Renamed build output files to prevent conflicts

==  3.0.8  ==
* UI update on shortcode to prevent duplicate title display
* Added error notifications on checkout blocks
* Added general workaround to avoid conflicts with other plugins

==  3.0.7  ==
* Updated to work with WC 9.5.1
* Minor UI update on the checkout method controls 
* Fixed issue with exiting the Plaid widget and having to double click the bank login button to reopen
* Updated order note for non risky checks being overrrided as not risky

==  3.0.6  ==
* Updated script execution order for shortcode checkout
* Fixed issue with displaying no available gateway in the blocks editor page

==  3.0.5  ==
* Updated payment method selector to automatically select the most applicable payment form

==  3.0.4  ==
* Fixed issue with shortcode checkout repeating logic
* Encapsulated plugin in the shadow DOM to prevent global style conflicts

==  3.0.3  ==
* Fixed a conflict with a 3rd party plugin 

==  3.0.2  ==
* Added support for the shortcode woocommerce checkout pages

==  3.0.1  ==
* Corrected issue that was affecting customer payment page flow

==  3.0.0  ==
* Updated plugin that is compatible with Wordpress Gutenberg blocks

==  2.2.0  ==
* Added support for merchants that are setup to require Verde(tm) on checkout
* Corrected a bug that was duplicating adminstrative notifications on the Orders screen

==  2.1.9  ==
* Performed a full system compatibility test with WooCommerce 5.0.0 and WordPress 5.6.1

==  2.1.8  ==
* Another update the the CSS to bring our Bank Login section back to intended styling

==  2.1.7  ==
* Updated CSS to fix a styling bug affecting shipping and payment methods on checkout

==  2.1.6  ==
* Switching back to the intended improvements from 2.1.4
* Improved the user experience when using our Bank Login Widget that addresses confusion some users were having
* Fixed unforseen issues with user experience upgrade and made further improvements

==  2.1.5  ==
* Reverting back to 2.1.3 effectively as there were unforseen issues with 2.1.4

==  2.1.4  ==
* Updated user experience when using our Bank Login Widget to address confusion that some users were having

==  2.1.3  ==
* Corrected a typo in our bank login widget

==  2.1.2  ==
* Added support for Live and Test API keys that can emulate a testing environment without needing access to our Sandbox system. Contact Green customer service support@green.money with subject of "Test API Credentials" with any questions!
* Did a full support test for WC 4.5.2 and WP 5.5.1

==  2.1.1  ==
* Increased security!
* Fixed a few bugs affecting refunds, overrides, and status updates
* Did a full compatibility testing with WC 4.3.0 and WP 5.4.2

==  2.1.0  ==
* Added a new API URL field into the settings menu to specify the URL to use for REST API calls
* GreenPay(tm) now automatically attempts to cancel the transaction in Green if the customer cancelled the order on their end
* Updated the emails that get sent out to use get_order_number to conform with order manager plugins
* Added a priority ranking to order statuses that is checked before GreenPay(tm) will automatically update the order status, which was requested by many of our users
* Did a full compatibility testing with WC 4.1.0 and WP 5.4.1

==  2.0.11  ==
* Updated the order note that gets created from specific status update results to avoid confusion

==  2.0.10  ==
* Fixed the Order Status Update feature to return the correct order status

==  2.0.9  ==
* Added new features to our debug log and error catching to help troubleshoot in the future

==  2.0.8  ==
* Fixed the double new order notification so now only 1 new order email should be sent and updated the order note that is created

==  2.0.7  ==
* Tested with WooCommerce 3.9.1 and updating to show compatibility

==  2.0.6  ==
* Stopped the plugin from reducing order stock as this was conflicting with WooCommerce order stock controls

== 2.0.5 ==
* Tested compatibility with WooCommerce version 3.8.1

== 2.0.4 ==
* Corrected a log message

== 2.0.3 ==
* Removed the enable/disable checkbox from the settings to further conform to WordPress standards. If you wish to disable the plugin, do so from the Plugins menu in WordPress dashboard

== 2.0.2 ==
* Squashing a tiny php bug

== 2.0.1 ==
* Fixed bug that was validating routing numbers incorrectly

== 2.0 ==
* Overhauled the plugin to follow WooCommerce order status standard
* Added support for WooCommerce REST API that allows live order updates
* Added support for Verde tokenization service
* Installation docs: https://www.green.money/woo

==  1.2.17  ==
* Changed how the order amount is pulled from the order to stop a very rare bug from creating orders with incorrect amounts

== 1.2.16 ==
* Added support for multi-site installations

== 1.2.15 ==
* Added a more descriptive order note when the check is accepted and created in Green Payment Processing

== 1.2.14 ==
*Fixed an error related to the settings page URL

== 1.2.13 ==
*Updated "Tested up to" and "WC tested up to" values

== 1.2.12 ==
*Tiny order status change and testing for new WordPress version

== 1.2.11 ==
*Rebranded the plugin to GreenPay(tm) by Green Payment Processing

== 1.2.10 ==
*Fixed issue that wasnt allowing some users to activate the plugin

== 1.2.9 ==
*Fixed an issue with testing Client_ID and API Password was always returning false for some users.

== 1.2.8 ==
*Added another error message on check creation failure that can help with debugging some network issues

== 1.2.7 ==
*Fixed order refunds incorrectly showing a full refund when only a partial refund was done

== 1.2.6 ==
*Fixed php notice affecting some users

== 1.2.4 ==
*Fixed bug where order stock was reduced incorrectly

== 1.2.4 ==
*Fixed description error having an extra period when it shouldnt

== 1.2.3 ==
*Added better error reporting

== 1.2.2==
*Small bug fix

== 1.2.1 ==
*Fixed issue where saved title was not showing correctly on checkout

== 1.2 ==
* Split the settings code away from everything else to make development easier.
* Reduced the total number of files the plugin is using and eliminated redundant code.
* Added the option to choose between two verification modes.
     - Legacy mode: everything functions as it always has. If Risky/Bad checks are entered, they will fail and be deleted from the Green Payment Processing system.
     - Permissive mode: If Risky/Bad checks are entered, and permissive mode has been activated via the options menu, they will be marked 'On-Hold' and can be manually overridden using the order actions dropdown.

== 1.1 ==
* Added Status Update All button to WooCommerce Orders page that will get a status update for all orders using GreenPay(tm) by Green Payment Processing.
* Added GreenPay(tm) Status Update custom order action when viewing an individual order which will return a status update on any order using the GreenPay(tm) by Green Payment Processing.
* Created a proper options/settings page for this plugin accessible via the WordPress dashboard left panel.
* Random bug fixes

== 1.0 ==
* Launch version that creates a GreenPay(tm) by Green Payment Processing for use upon checkout.