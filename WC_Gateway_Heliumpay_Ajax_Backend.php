<?php
defined('ABSPATH') or die('Direct access not allowed');
if (is_admin() && isset($_REQUEST['WoocommerceGatewayHeliumpayDebug'])) {
	defined('WP_DEBUG') or define( 'WP_DEBUG', true );
	ini_set('display_startup_errors', 'On');
	error_reporting(2147483647); // max future error values
	ini_set('display_errors', 'On');
}

class WC_Gateway_Heliumpay_Ajax_Backend {

	public function executeJSON($a) {
		wp_send_json_error (['answer'=>"NOT LONGER SUPPORTED", 'url'=>""]); // deaktiviert da nun down
		$ret = "";
		$justJSON = false;
		try {
			switch (trim($a)) {
				case "requestWebhookCall":
					$ret = $this->requestWebhookCall();
					break;
				default:
					throw new Exception("function ".$a." not implemented");
			}
		} catch(Exception $e) {
			return wp_send_json_error (['msg'=>$e->getMessage()]);
		}
		if ($justJSON) return wp_send_json($ret);
		else return wp_send_json_success( $ret );
	}

	private function requestWebhookCall() {
		$order_id = intval($_REQUEST['order_id']);
		if ($order_id <= 0) {
			wc_add_notice( "Order id missing. Please reload page.", 'error' );
			wp_send_json_error (['answer'=>"order id missing", 'url'=>""]);
			return;
		}
		$order = new WC_Order( $order_id );
		// lade meta info secret von order
		$secret = WC_Gateway_Heliumpay_Admin::getSecret($order_id);
		// lade meta into trx_id von order
		$txn_id_idcode = WC_Gateway_Heliumpay_Admin::getTrxId($order_id);
		// sende einen request für den webhook
		$WC_Gateway_Heliumpay = new WC_Gateway_Heliumpay();
		$url = $WC_Gateway_Heliumpay->getServiceUrl()."webhook/".$txn_id_idcode;
		// antworte mit antwort vom server
		$data = ["secret"=>urlencode($secret)];
		$response = Requests::post( $url,  array(),  $data );
		//if ( is_wp_error( $response ) || $response->status_code != 200) {
		if ($response->success == false) {
			$error_message = $response->status_code." ".$response->url;
			$order->add_order_note( "Heliumpay transaction webhook call failed. ".$error_message );
			wc_add_notice(  "Heliumpay transaction webhook call failed. ".$error_message." Please retry later.", 'error' );
			$answerObj = [];
			try {
				$answer = $response->body;
				//$answerObj = json_decode($answer, true);
			} catch(Exception $e) {}
			wp_send_json_error (['answer'=>$answer, 'url'=>$url]);
		} else {
			$answer = $response->body;
			$answerObj = json_decode($answer, true);
			if (isset($answerObj['error'])) {
				$order->add_order_note( "Heliumpay transaction webhook call made. Error code ".$answerObj['error'] );
			} else {
				$order->add_order_note( "Heliumpay transaction webhook call made. Webhook call tracking id #".$answerObj['data'] );
			}
			wp_send_json_success( ['answer'=>$answerObj] );
		}
	}

}
?>