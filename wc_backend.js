function WC_Gateway_Heliumpay_Ajax_Backend($, phpObject) {
	let _self = this;
	let btn_id_webhook = phpObject.btn_id_webhook;

	function init() {
		//console.log(phpObject);
		_addHandlerToTheCodeFields();
	}

	function _addHandlerToTheCodeFields() {
		// finde die code text inputs
		$('body').find('button[data-id="'+btn_id_webhook+'"]')
			.on('click', () => {

				if (confirm("Do your want to request a webhook URL call for this order? This is typically done automatically by the heliumpay server.")) {
					$('body').find('button[data-id="'+btn_id_webhook+'"]').attr('disabled');
			 		$.ajax(
			 			{
			 				type: 'GET',
			 				url: phpObject.ajaxurl,
			 				data: {
			 					action: phpObject._action,
			 					a: 'requestWebhookCall',
		 						securityToken: phpObject.securityToken,
		 						order_id: phpObject.order_id
			 				},
			 				success: function( response ) {
			 					//console.log(response);
			 					if (response.success) {
			 						//alert("Webhook call was made. #"+response.data.answer.data);
			 					} else {
			 						//alert("Webhook call failed. "+response.data.answer);
			 					}
			 				}
			 			}
			 		)
				}
				//return false; // stop page reload

	 		})
	 		.removeAttr('disabled');
	}

	init();
}

(function($){
 	$(document).ready(function(){
 		WC_Gateway_Heliumpay_Ajax_Backend($, phpObject);
 	});
})(jQuery);