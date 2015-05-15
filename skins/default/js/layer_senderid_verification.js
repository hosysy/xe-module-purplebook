/**
 * @fileoverview 발신번호 목록 관련
 * @requires layer_sendid.html
 */

/** @define {boolean} permission을 이용해 코드가 중복되지 않도록 한다. */
if (!layer_senderid_verification_permission) {
	var layer_senderid_verification_permission = false;
}

(function($) {
	jQuery(function($) {
		/**
		 * js file이 한번만 로딩되도록
		 */
		if (layer_senderid_verification_permission == false) {
		   	layer_senderid_verification_permission = true;
		} else {
			return;
		}

		$(document).on('click', '#verify_ars_response', function() {
			var params = new Array();
			params['handle_key'] = $(this).attr('data-handle_key');
			var response_tags = new Array('error','message');
			exec_xml('purplebook', 'procPurplebookRegisterSenderID', params, function(ret_obj) {
				$('#layer_senderid_verification').html('');
				alert(ret_obj['message']);
				refreshCallbackList();
			}, response_tags);
		});
	});
}) (jQuery);


/**
 * 발신번호 삭제
 */
function deleteCallback(callback_srl) {

}

function request_default_number(phonenum) {
	var params = new Array();
	params['phonenum'] = phonenum;
	var response_tags = new Array('error','message');
	exec_xml('purplebook', 'procPurplebookSetDefaultCallbackNumber', params, function() { refreshCallbackList(); }, response_tags);
}
