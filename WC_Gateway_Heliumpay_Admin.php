<?php
defined('ABSPATH') or die('Direct access not allowed');
if (is_admin() && isset($_REQUEST['WoocommerceGatewayHeliumpayDebug'])) {
	defined('WP_DEBUG') or define( 'WP_DEBUG', true );
	ini_set('display_startup_errors', 'On');
	error_reporting(2147483647); // max future error values
	ini_set('display_errors', 'On');
}

class WC_Gateway_Heliumpay_Admin {
	private $uniqueIDMetaBox_Webhook = "wc_gateway_heliumpay_webhook";
	private $_prefix = 'wc_gateway_heliumpay';
	private $btn_id_webhook = 'wc_gateway_heliumpay_btnWebhook';
	private $order;

	public function __construct() {
		// add actions

		//add_action('add_meta_boxes', [$this, 'add_meta_boxes']); // deaktiviert da nun down
		if (wp_doing_ajax()) {
			add_action('wp_ajax_'.$this->_prefix.'_executeAjaxCallsBE', [$this,'executeAjaxCallsBE']); // nicht angemeldete user, sollen eine antwort erhalten
		}

		//add_filter("woocommerce_admin_order_actions", [$this,"woocommerce_admin_order_actions"]);
		//add_action( 'woocommerce_admin_order_actions_end', [$this, 'woocommerce_admin_order_actions_end'], 10, 2);

		// $this->addActionsAndFilters(); // deaktiviert da nun down
	}

	private function addActionsAndFilters() {
		/*
		* Add a WC product tab
		*/
		add_filter('woocommerce_product_data_tabs', function($tabs) {
			//unset( $tabs['inventory'] );
			$tabs['hp_dd'] = array(
				'label'    => 'Heliumpay',
				'title'    => 'Heliumpay settings NO LONGER ACTIVE',
				'target'   => 'wc_heliumpay_data',
				'class'		=> ['show_if_simple', 'show_if_variable']
			);
			return $tabs;
		}, 98 );

		/*
		* WC product Tab content
		*/
		add_action( 'woocommerce_product_data_panels', function(){
			echo '<div id="wc_heliumpay_data" class="panel woocommerce_options_panel hidden">';

				if (version_compare( WC_VERSION, WC_HELIUMPAY_MIN_WC_VER, '<' )) {
					echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'The plugin requires WooCommerce %1$s or greater to be installed and active. WooCommerce %2$s is not supported.', 'heliumpay-payment-gateway' ), WC_HELIUMPAY_MIN_WC_VER, WC_VERSION ) . '</strong></p></div>';
				} else {
					woocommerce_wp_checkbox( array(
						'id'            => 'wc_heliumpay_exclude_payment',
						'label'			=> 'Exclude Heliumpay payment',
						'description'   => 'If active and the product is in the cart, then Heliumpay will not be shown as a payment option.',
						'value'         => get_post_meta( get_the_ID(), 'wc_heliumpay_exclude_payment', true ),
					) );
				}

			echo '</div>';
		});

		add_action( 'woocommerce_process_product_meta', function( $id, $post ){
			if( !empty( $_POST['wc_heliumpay_exclude_payment'] ) ) {
				update_post_meta( $id, 'wc_heliumpay_exclude_payment', isset($_POST['wc_heliumpay_exclude_payment']) ? 'yes' : 'no' );
			} else {
				delete_post_meta( $id, 'wc_heliumpay_exclude_payment' );
			}
		}, 10, 2 );
	}

	static function getSecret($order_id) {
		$order_id = intval($order_id);
		return get_post_meta( $order_id, 'wc_gateway_heliumpay_secret', true);
	}
	static function getTrxId($order_id) {
		$order_id = intval($order_id);
		return get_post_meta( $order_id, 'wc_gateway_heliumpay_trx_id', true);
	}

	function add_meta_boxes() {
		global $post_type;
		$screens = []; // wo meta boxen anzeigen
		if( $post_type == 'shop_order' ) {
			// check ob order heliumpay ist
			$secret = self::getSecret( intval($_GET['post']) );
			if (!empty($secret)) {
				$this->addJSFileAndHandlerBackend();
				$screens[] = 'shop_order'; // wo anzeigen (post_type)
			}
		}
	    foreach ($screens as $screen) {
	        add_meta_box(
	            $this->uniqueIDMetaBox_Webhook, // Unique ID
	            'Heliumpay',  // Box title
	            [$this, 'display_side_box'],  // Content callback, must be of type callable
	            $screen,
	            'side',
	            'high'
	        );
	    }
	}

	function display_side_box($post) {
		$order = new WC_Order( $post->ID );
		if (!$order) return "No heliumpay order information found";

		$txn_id = self::getTrxId( $post->ID );
	    ?>
	    <p><b>TRX ID:</b> <?php echo esc_html($txn_id); ?></p>
	    <button disabled data-id="<?php echo esc_attr($this->btn_id_webhook); ?>" class="button button-primary">Request Webhook Call</button>
	    <?php
	}

	private function addJSFileAndHandlerBackend() {
		wp_register_script(
			'WC_Gateway_Heliumpay_Ajax_Backend',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'wc_backend.js',
			array( 'jquery', 'jquery-blockui' ),
			(current_user_can("administrator") ? time() : WC_HELIUMPAY_VERSION),
			true );
		wp_localize_script(
 			'WC_Gateway_Heliumpay_Ajax_Backend',
			'phpObject', // name der js variable
 			[
 				'_action' => $this->_prefix.'_executeAjaxCallsBE',
 				'ajaxurl' => admin_url( 'admin-ajax.php' ),
 				'securityToken' =>$this->setSecurityToken(intval($_GET['post'])),
 				'order_id'=>intval($_GET['post']),
 				'btn_id_webhook'=>$this->btn_id_webhook
 			] // werte in der js variable
 			);
      	wp_enqueue_script('WC_Gateway_Heliumpay_Ajax_Backend');
 	}


 	// ajax Backend calls
	public function executeAjaxCallsBE() {
		include_once plugin_dir_path(__FILE__)."WC_Gateway_Heliumpay_Ajax_Backend.php";
		if (!isset($_REQUEST['a'])) return wp_send_json_error("a not provided");
		if (!isset($_REQUEST['order_id'])) return wp_send_json_error("order id not provided");
		// werte security aus
		if (!isset($_REQUEST['securityToken'])) return wp_send_json_error("security value not provided");
		if ($_REQUEST['securityToken'] != $this->getSecurityToken(intval($_REQUEST['order_id']))) return wp_send_json_error("security value wrong");
		$WC_Gateway_Heliumpay_Ajax_Backend = new WC_Gateway_Heliumpay_Ajax_Backend();
		return $WC_Gateway_Heliumpay_Ajax_Backend->executeJSON(sanitize_text_field($_REQUEST['a']));
	}

	private function setSecurityToken($post_id) {
		// generiert einen token und speichert diesen in der order ab
		$securityToken = uniqid();
		update_post_meta( $post_id, '_wc_gateway_heliumpay_seecuritytoken_ajax', $securityToken );
		return $securityToken;
	}
	private function getSecurityToken($post_id) {
		return get_post_meta( $post_id, '_wc_gateway_heliumpay_seecuritytoken_ajax', true);
	}

	// add a button to each item on the order table within the
	// andere variante
	function woocommerce_admin_order_actions($actions, $the_order) {
	    $actions[] = [
	        "action" => "actionCode",
	        "url" => admin_url("?page=myPage"),
	        "name" => "New link",
	    ];

	    return $actions;

	}
	// add a button to each item on the order table within the
	function woocommerce_admin_order_actions_end() {

	    // create some tooltip text to show on hover
	    $tooltip = __('Some tooltip text here.', 'textdomain');

	    // create a button label
	    $label = __('Label', 'textdomain');

	    echo '<a class="button tips custom-class" href="#" data-tip="'.esc_attr($tooltip).'">'.esc_html($label).'</a>';
	}


}
?>