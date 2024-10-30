<?php
/**
 * Plugin Name: WooCommerce Heliumpay Payment Gateway
 * Plugin URI: https://wordpress.org/plugins/heliumpay-payment-gateway/
 * Description: NOT LONGER ACTIVE: Take Helium (HNT) cryptocurrency payments on your store using Heliumpay.
 * Author: Heliumpay
 * Author URI: https://heliumpay.net/
 * Version: 1.0.6
 * Text Domain: heliumpay-payment-gateway
 * Domain Path: /languages
 */

defined('ABSPATH') or die('Direct access not allowed');

if (is_admin() && isset($_REQUEST['WoocommerceGatewayHeliumpayDebug'])) {
	defined('WP_DEBUG') or define( 'WP_DEBUG', true );
	ini_set('display_startup_errors', 'On');
	error_reporting(2147483647); // max future error values
	ini_set('display_errors', 'On');
}

/**
 * Required minimums and constants
 */
define( 'WC_HELIUMPAY_VERSION', '1.0.6' );
define( 'WC_HELIUMPAY_MIN_PHP_VER', '5.6.0' );
define( 'WC_HELIUMPAY_MIN_WC_VER', '3.0' );
define( 'WC_HELIUMPAY_FUTURE_MIN_WC_VER', '3.3' );
define( 'WC_HELIUMPAY_MAIN_FILE', __FILE__ );
define( 'WC_HELIUMPAY_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
define( 'WC_HELIUMPAY_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

function woocommerce_heliumpay_missing_wc_notice() {
	/* translators: 1. URL link. */
	echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Heliumpay requires WooCommerce to be installed and active. You can download %s here.', 'heliumpay-payment-gateway' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
}
function woocommerce_heliumpay_wc_not_supported() {
	/* translators: $1. Minimum WooCommerce version. $2. Current WooCommerce version. */
	echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Heliumpay requires WooCommerce %1$s or greater to be installed and active. WooCommerce %2$s is not supported.', 'heliumpay-payment-gateway' ), WC_HELIUMPAY_MIN_WC_VER, WC_VERSION ) . '</strong></p></div>';
}

// remove on conditional payment gateways
add_filter('woocommerce_available_payment_gateways', function($available_gateways) {
	// Not in backend (admin)
    if( is_admin() ) return $available_gateways;

	$prod_variable = $prod_simple = $prod_subscription = false;
	foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
        // Get the WC_Product object
        $product = wc_get_product($cart_item['product_id']);
        // Get the product types in cart (example)
        if($product->is_type('simple')) $prod_simple = true;
        if($product->is_type('variable')) $prod_variable = true;
        if($product->is_type('subscription')) $prod_subscription = true;

		$ret = get_post_meta( $cart_item['product_id'], 'wc_heliumpay_exclude_payment', true );
		if ($ret == 'yes') unset($available_gateways['heliumpay']);
    }
    // Remove Cash on delivery (cod) payment gateway for simple products
    /*
    if($prod_simple) unset($available_gateways['cod']); // unset 'cod'
    // Remove Paypal (paypal) payment gateway for variable products
    if($prod_variable) unset($available_gateways['paypal']); // unset 'paypal'
    // Remove Bank wire (Bacs) payment gateway for subscription products
	if($prod_subscription) unset($available_gateways['bacs']); // unset 'bacs'
    */
    if($prod_subscription) unset($available_gateways['heliumpay']); // unset 'bacs'

	if (isset($available_gateways['heliumpay'])) unset($available_gateways['heliumpay']);

    return $available_gateways;

}, 10, 1);

function woocommerce_gateway_heliumpay() {
	class WC_Gateway_Heliumpay extends WC_Payment_Gateway {
		private $SERVICE_URL;
		private $SERVICE_API = "api/v1/";
		private $option_prefix = "heliumpay-payment-gateway_";
		private $testmode = false;
		private $devmode = false;

		function __construct() {
			$this->id = 'heliumpay';
			$this->icon = plugin_dir_url(__FILE__).'/images/logo_heliumpay_146x30.jpg';
			$this->has_fields = false; // for type of payment: direct
			$this->method_title = "Heliumpay";
			$this->method_description = "NO LONGER ACTIVE. STOPP IT AND DELETE THE PLUGIN FOR YOUR SAFETY. Take HNT (native cryptocurrency of the Helium blockchain) payments on your store using Heliumpay.";

			$this->supports = array(
				'products',
				'pre-orders'
			);

			$this->init_form_fields();
			$this->init_settings();

			$this->testmode = 'yes' === $this->get_option( 'testmode' ) || defined('WC_HELIUMPAY_VERSION_TESTMODE');
			$this->devmode = defined('WC_HELIUMPAY_VERSION_DEVMODE');

			$this->title = $this->get_option( 'title' ).($this->testmode ? ' TESTMODE Active' : '');
			$this->description = $this->get_option( 'description' );

			if ($this->testmode) {
				$this->SERVICE_URL = "https://api-test.heliumpay.net/";
			} else {
				$this->SERVICE_URL = "https://api.heliumpay.net/";
			}
			if ($this->devmode) {
				$this->SERVICE_URL = WC_HELIUMPAY_VERSION_DEV_SERVICE_URL;
			}
			$this->SERVICE_URL = ""; // deaktiviert da nun down

			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'heliumpay_process_admin_options' ) );
			//add_action('woocommerce_api_wc_gateway_heliumpay', array($this, 'callback_ipn_response')); // deaktiviert da nun down

			//add_action( 'woocommerce_order_status_refunded', [$this, 'woocommerce_order_status_refunded'], 10, 1 ); // deaktiviert da nun down
		}

		function init_form_fields() {
			$admin_email = get_option('admin_email');
			$this->form_fields = [
			    'enabled' => array(
			        'title' => __( 'Enable/Disable', 'woocommerce' ),
			        'type' => 'checkbox',
			        'label' => __( 'Enable Payment with HNT (Helium cryptocurrency)', 'woocommerce' ),
			        'default' => 'yes'
			    ),
			    'title' => array(
			        'title' => __( 'Title', 'woocommerce' ),
			        'type' => 'text',
			        'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
			        'default' => __( 'Pay with helium cryptocurrency (HNT)', 'woocommerce' ),
			        'desc_tip' => true,
			    ),
				'description' => array(
        			'title' => __( 'Description', 'woocommerce' ),
        			'type' => 'textarea',
        			'default' => 'You will be redirected to a page to see the payment information and a QR code to finalize your HTN payment with Heliumpay.'
    			),
				'wallet_address' => array(
			        'title' => __( 'You Helium wallet address', 'woocommerce' ),
			        'type' => 'text',
			        'description' => __( 'This wallet will receive the payments. Need a helium wallet? <a target="_blank" href="https://heliumpay.net/how-to-create-a-hellium-wallet/">Click here to learn how to create a HNT wallet.</a>', 'woocommerce' ),
			        'default' => '',
			        'desc_tip' => false,
			    ),
			    'email' => array(
			        'title' => __( 'Your email address to receive payment notifications', 'woocommerce' ),
			        'type' => 'text',
			        'description' => __( 'If left empty, the admin email address will be used.', 'woocommerce' ),
			        'default' => $admin_email,
			        'placeholder'=> 'email@address',
			        'desc_tip' => false,
				),
			    'is_refundable' => array(
			        'title' => __( 'Refunds', 'woocommerce' ),
			        'type' => 'checkbox',
			        'label' => __( 'Allow refunds. The payout to you will be on hold for the given days. If you trigger a refund within the days, your customer will receive her payment. Fees are deducted from the payment amount.', 'woocommerce' ).'<p><a href="https://heliumpay.net/fees/" target="_blank">Check out the low fees</a></p>',
			        'default' => 'no'
			    ),
				'refund_days' => [
					'title' => 'Days refund',
					'type' => 'select',
					'description' => 'You payout will be blocked for that amount of days, to be able to execute a refund on behalf of you. You customer will see our wallet address not yours.',
					'options' => [
						'' => 'Please select',
						'8' => '8 days',
						'15' => '15 days',
						'31' => '31 days',
						'46' => '46 days',
						'61' => '61 days',
						'91' => '91 days',
						'181' => '181 days',
						'366' => '366 days'
					]
				]
			];

			if (defined('WC_HELIUMPAY_VERSION_TESTMODE')) {
				$this->form_fields['testmode'] = array(
					'title'       => 'Test mode',
					'label'       => 'Enable Test Mode',
					'type'        => 'checkbox',
					'description' => 'Place the payment gateway in test mode using Helium testnet.',
					'default'     => 'no',
					'desc_tip'    => true
				);
			}
		}

		function heliumpay_process_admin_options($array) {
			$domain = parse_url( get_site_url(), PHP_URL_HOST );
			$email = sanitize_email(trim($_REQUEST['woocommerce_'.$this->id.'_email']));
			$is_refundable = intval($_REQUEST['woocommerce_'.$this->id.'_is_refundable']);
			$refund_days = intval($_REQUEST['woocommerce_'.$this->id.'_refund_days']);
			if (empty($email)) {
				$email = get_option('admin_email');
			}
			$wallet_address = $_REQUEST['woocommerce_'.$this->id.'_wallet_address'];

		    $data = [
		    	"wallet_address"=>$wallet_address,
		    	"email"=>$email,
		    	"domain"=>$domain,
		    	"type"=>0,
				"is_refundable"=>$is_refundable,
				"refund_days"=>$refund_days
		    	];

			// check if a client_wallet_secret exists and is not empty
			$token = trim(get_option($this->option_prefix.'token', ''));
			if (empty($token)) {
				//$this->_createClientWalletInformation($data); // deaktiviert da nun down
			} else {
				try {
					$data["secret"] = $token;
					//$this->_updateClientWalletInformation($data); // deaktiviert da nun down
				} catch (Exception $e) {
					//$this->_createClientWalletInformation($data); // deaktiviert da nun down
				}
			}

			$this->process_admin_options($array);
		}

		private function _createClientWalletInformation($data) {
		    $url = $this->getServiceUrl()."client/";
			$response = Requests::post( $url,  array(),  ['data'=>wp_json_encode($data)] );
			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				add_action('admin_notices', function() {
					echo '<div class="notice notice-warning is-dismissible"><p>';
					echo 'Error to store some information with heliumpay';
					echo '</p></div>';
				});
			} else {
				$answer = $response->body;
				$answerObj = json_decode($answer, true);
				if (isset($answerObj['error'])) {
					$this->_updateClientWalletInformation($data);
				} else {
					update_option($this->option_prefix.'token', $answerObj['secret'], false);
				}
			}
		}

		private function _updateClientWalletInformation($data) {
			$url = $this->getServiceUrl()."client/";
			$response = Requests::put( $url,  array(),  ['debug'=>1,'data'=>wp_json_encode($data)] );
			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				add_action('admin_notices', function() {
					echo '<div class="notice notice-warning is-dismissible"><p>';
					echo 'Error to store some information with heliumpay';
					echo '</p></div>';
				});
			} else {
				$answer = $response->body;
				$answerObj = json_decode($answer, true);
				if ($answerObj != null) {
					if (isset($answerObj['error'])) {
						if (is_array($answerObj['error'])) {
							switch ($answerObj['error']['error_code']) {
								case 3312:
									add_action('admin_notices', function() {
										echo '<div class="notice notice-warning is-dismissible"><p>';
										echo 'Error you need to add your wallet_address.';
										echo '</p></div>';
									});
									break;
								case 3306:
									add_action('admin_notices', function() {
										echo '<div class="notice notice-warning is-dismissible"><p>';
										echo 'Your email seems not to work for heliumpay. Please add another.';
										echo '</p></div>';
									});
									break;
								case 3307:
									add_action('admin_notices', function() {
										echo '<div class="notice notice-warning is-dismissible"><p>';
										echo 'Error to store some information with heliumpay. Your secret is missing. Please contact Heliumpay support via email.';
										echo '</p></div>';
									});
									break;
								default:
									add_action('admin_notices', function() {
										echo '<div class="notice notice-warning is-dismissible"><p>';
										echo 'Error to store some information with heliumpay';
										echo '</p></div>';
									});
							}
						} else {
							add_action('admin_notices', function() {
								echo '<div class="notice notice-warning is-dismissible"><p>';
								echo 'Error to store some information with heliumpay';
								echo '</p></div>';
							});
						}
					} else {
						if (!empty($answerObj['secret'])) {
							update_option($this->option_prefix.'token', $answerObj['secret'], false);
						}
					}
				} else {
					add_action('admin_notices', function() {
						echo '<div class="notice notice-warning is-dismissible"><p>';
						echo 'Error while storing some information with heliumpay';
						echo '</p></div>';
					});
				}
			}
		}

		function getServiceUrl() {
			//return $this->SERVICE_URL.$this->SERVICE_API; // deaktiviert da nun down
			return "";
		}

		function getIPNCallbackURL($order_id) {
			// http://yoursite.com/wc-api/wc_gateway_heliumpay/
			//return str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Gateway_Paypal', home_url( '/' ) ) );
			//return get_site_url()."/wc-api/wc_gateway_heliumpay/?order_id=".intval($order_id); // deaktiviert da nun down
			return "";
		}

		function callback_ipn_response() {
			if (!isset($_GET['order_id'])) die("order id missing");
			if (!isset($_GET['data'])) die("txn data missing");
			if (!isset($_GET['secret'])) die("order secret missing");
			if (!isset($_GET['success'])) die("txn status missing");
			$order_id = intval($_GET['order_id']);
			if ($order_id < 1) die("order id does not exists");
			if (intval($_GET['success']) < 4 || intval($_GET['success']) == 9) die("txn not paid yet");
			echo date("YmdHis")." - passed checks.";

		    $order = new WC_Order( $order_id );
			$secret = $order->get_meta( 'wc_gateway_heliumpay_secret', true);
			if ($secret != $_GET['secret']) die("secret mismatch");
			$trx_id = $order->get_meta( 'wc_gateway_heliumpay_trx_id', true);
			if ($trx_id != $_GET['trx_id']) die("transaction id mismatch");

			echo date("YmdHis")." - passed secret check."; // not displayed in wp
			echo date("YmdHis")." - order status is ".esc_html($order->get_status())."."; // not displayed in wp

			$data_json = sanitize_text_field(base64_decode($_GET['data']));
			if (empty($data_json)) die("no data");
			$data = json_decode($data_json, true);

			// check if this is a incoming payment notification or just an notification update
			if (isset($_GET['_type']) && $_GET['_type'] == "notification") {
				$noteAdded = false;
				if (isset($data['note'])) {
					$order->add_order_note( "Notification: ".$data['note']);
					$noteAdded = true;
				}
				//$order->add_order_note( "Notification: ".$data_json);
				if (isset($data['partial_payment'])) {
					//if ($order->get_status() == "wc-cancelled") {
					if ($order->get_status() != "wc-pending") {
						$order->update_status( 'wc-pending', 'Received partial payment. Waiting for further payments.');
					}
					$order->add_order_note( "Received Partial payment of ".esc_html($data['partial_payment']));
					$noteAdded = true;
				}
				if ($noteAdded == false) {
					$order->add_order_note( "Received Heliumpay notification ".print_r($data, true));
				}

			} else {
				// check if processed already - prevent double processing
				//if ($order->get_status() == "wc-pending") {
				if ($order->needs_payment() || $order->get_status() == "wc-cancelled" || $order->get_status() == "cancelled") { // pending oder failed and money > 0
					echo date("YmdHis")." - order payment will be processed."; // not displayed in wp

					if ($order->get_status() == "wc-cancelled" || $order->get_status() == "cancelled") {
						$note = "Receiving Helium payment, but the order is cancelled. Setting back to pending.";
						$order->update_status( 'wc-pending', $note );
					}
					if (isset($data['status']) && $data['status'] == 7) {
						$cancelled_text = "Transaction is not paid yet. Transaction is treated as cancelled";
						$order->add_order_note( $cancelled_text );
						$order->update_status( 'cancelled', $cancelled_text );
						echo date("YmdHis")." - txn is is cancelled."; // not displayed in wp
					} else if (isset($data['paid_at']) && !empty($data['paid_at'])) {
						echo date("YmdHis")." - txn is paid out.";
						$order->add_order_note( "Helium TXN details: ".$data_json ); // zum hinzufügen von order notes
						$order->add_order_note( "Helium payment received at ".$data['paid_at'] ); // zum hinzufügen von order notes
						$order->payment_complete();
						echo date("YmdHis")." - payment completed.\n"; // not displayed in wp
						echo "OK";
					} else {
						$order->add_order_note( "Received Helium payment webhook call. Processed, but payout to you is ongoing.");
						echo date("YmdHis")." - txn is not paid out yet."; // not displayed in wp
					}
				} else {
					$order->add_order_note( "Received Helium payment webhook call. Not processed, because order is paid already.");
				}
			}
			exit; // damit was sehen auf dem (server)
		}

		function process_payment( $order_id ) {
			return; // deaktiviert da nun down
		    global $woocommerce;
			if ( !function_exists( 'wc_add_notice' ) ) {
				require_once ABSPATH . PLUGINDIR . '/woocommerce/includes/wc-notice-functions.php';
			}
			// call the helium api to add a new trx
		    $data = [
				"order_id"=>$order_id,
				"webhook_url"=>$this->getIPNCallbackURL($order_id)
			];
		    $data['wallet_receiver_address'] = trim($this->get_option( 'wallet_address' ));
		    $data['wallet_receiver_email'] = trim($this->get_option( 'email' ));
			$data['is_refundable'] = $this->get_option( 'is_refundable' ) == "yes" ? 1 : 0;
			if ($data['is_refundable'] == 1) {
				$data['refund_days'] = intval($this->get_option( 'refund_days' ));
			}
		    if (empty($data['wallet_receiver_email'])) {
		    	$data['wallet_receiver_email'] = get_option('admin_email');
		    }
		    $data['order_items'] = [];

			$currency = get_woocommerce_currency();

			// get all orders
		    $order = new WC_Order( $order_id );
			foreach ( $order->get_items() as $item_id => $item ) {
				if( $item['product_id'] ){
					$order_item = ["order_item_id"=>$item_id];
					if (strtoupper($currency) == "HNT") {
						$order_item["amount_crypto"] = $item['total'];
					} else { // money
						$order_item["amount_money"] = $item['total'];
						$order_item["amount_money_currency"] = $currency;
					}

					$data['order_items'][] = $order_item;
				}
			}

		    $url = $this->getServiceUrl()."trx/";
			$response = Requests::post( $url,  array(),  ['data'=>wp_json_encode($data)] );
			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				$order->add_order_note("Heliumpay transaction creation failed. Customer was asked to use another payment solution.");
				wc_add_notice('Error with the payment creation, please choose another option or contact us.', 'error');
			} else {
				$answer = $response->body;
				$answerObj = json_decode($answer, true);
				if (isset($answerObj['error'])) {
					$order->add_order_note( "Heliumpay transaction creation failed. Error: ".sanitize_text_field($answerObj['error']['error']) );
					//if (str_starts_with($answerObj['error']['error'], '#9999 ')) {
					if ($answerObj['error']['error_code'] == 9999) {
						$order->add_order_note( "Heliumpay was in maintenance mode." );
						wc_add_notice(  'Sorry. Heliumpay is under maintenance for a short while. Please retry later or choose another payment option.');
					} else {
						wc_add_notice(  'Error with the payment creation. '.sanitize_text_field($answerObj['error']['error']), 'error' );
					}
				} else {
					$ret['result'] = "success";
					$txn_id = sanitize_text_field($answerObj["trx_id"]);

					$secret = trim(sanitize_text_field($answerObj["secret"]));
					$order->add_meta_data( 'wc_gateway_heliumpay_secret', $secret, true);
					$order->add_meta_data( 'wc_gateway_heliumpay_trx_id', $txn_id, true);
					$order->save();

					$redirectURL = plugin_dir_url(__FILE__).'pay/index.html?bckShop='.urlencode(wc_get_page_permalink( 'shop' )).'&bckCheckout='.urlencode(wc_get_checkout_url()).'&redirect='.urlencode($this->get_return_url( $order )).'&order_id='.urlencode($order_id).'&txn_id='.urlencode($txn_id).'&t='.time();
					if ($this->devmode) $redirectURL .= "&isDevmode=".urlencode($this->SERVICE_URL);
					if ($this->testmode) $redirectURL .= "&isTestmode";
					$ret = [
				    	'result' => 'success',
				    	'redirect' => $redirectURL
				    ];

					// add order note with txn details
					$order->add_order_note( "Heliumpay transaction created. Trx: #".$txn_id.". Transaction amount to pay: ".esc_html($answerObj["amount"])." HNT" );
					$order->add_order_note( "Heliumpay transaction to be done to Heliumpay wallet for processing: ".esc_html($answerObj["address"]).". Memo to be used: ".esc_html($answerObj["memo"]). ' Payment can be done also on the <a href="'.$redirectURL.'" target="_blank">payment detail page</a>.' );
					if ($answerObj['is_refundable'] == 1) {
						$order->add_order_note( "Order is refundable untill ".esc_html($answerObj["refund_possible_till"]) );
					}

				   	return $ret;
				}
			}

			return array(
				'result' => 'failure',
				'redirect' => ''
			);
		}

		function woocommerce_order_status_refunded($order_id) {
			// check ob order refundable is
			if ( !function_exists( 'wc_add_notice' ) ) {
				require_once ABSPATH . PLUGINDIR . '/woocommerce/includes/wc-notice-functions.php';
			}
			$is_refundable = $this->get_option( 'is_refundable' ) == "yes" ? 1 : 0;
			if ($is_refundable) {
				$order = new WC_Order( $order_id );
				$secret = $order->get_meta( 'wc_gateway_heliumpay_secret', true);
				$trx_id = $order->get_meta( 'wc_gateway_heliumpay_trx_id', true);
				// rufe refund service auf
				$data = [
					"secret"=>$secret
				];
				$url = $this->getServiceUrl()."refund/".$trx_id."/";
				$response = Requests::post( $url,  array(),  ['data'=>wp_json_encode($data)] );
				// speicher order note
				if ( is_wp_error( $response ) ) {
					$error_message = $response->get_error_message();
					$order->add_order_note("Heliumpay refund request creation failed.");
					wc_add_notice('Error with the Heliumpay refund request creation, please contact Heliumpay support if the refund is needed.', 'error');
				} else {
					$answer = $response->body;
					$answerObj = json_decode($answer, true);
					if (isset($answerObj['error'])) {
						$order->add_order_note( "Heliumpay refund request creation failed. Error: ".sanitize_text_field($answerObj['error']['error']) );
						if ($answerObj['error']['error_code'] == 9999) {
							$order->add_order_note( "Heliumpay was in maintenance mode." );
							wc_add_notice(  'Sorry. Heliumpay is under maintenance for a short while. Please retry later by setting the status to "Pending payment" and then again to "Refund" or contact Heliumpay support.');
						} else {
							wc_add_notice(  'Error with the refund request creation. '.sanitize_text_field($answerObj['error']['error']), 'error' );
						}
					} else {
						if ($answerObj != null) {
							$order->add_order_note( 'Refund requested. Refund-Request-Id #'.$answerObj['id'] );
						} else {
							wc_add_notice("Heliumpay error: ".$answer);
						}
					}
				}
			}

		}

	}
}

add_action( 'plugins_loaded', 'woocommerce_gateway_heliumpay_init' );
function woocommerce_gateway_heliumpay_init() {
	//load_plugin_textdomain( 'woocommerce-gateway-heliumpay', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'woocommerce_heliumpay_missing_wc_notice' );
		return;
	}

	if ( version_compare( WC_VERSION, WC_HELIUMPAY_MIN_WC_VER, '<' ) ) {
		add_action( 'admin_notices', 'woocommerce_heliumpay_wc_not_supported' );
		return;
	}

	woocommerce_gateway_heliumpay();
}

add_filter( 'woocommerce_payment_gateways', 'woocommerce_gateway_heliumpay_add_class' );
function woocommerce_gateway_heliumpay_add_class( $methods ) {
    $methods[] = 'WC_Gateway_Heliumpay';
    return $methods;
}

if (is_admin()) {
	include_once plugin_dir_path(__FILE__)."WC_Gateway_Heliumpay_Admin.php";
	new WC_Gateway_Heliumpay_Admin();
}