/**
 * @fileoverview 발신번호 목록 관련
 * @requires layer_sendid.html
 */

/** @define {boolean} permission을 이용해 코드가 중복되지 않도록 한다. */
if (!layer_senderid_verification_permission) {
	var layer_senderid_verification_permission = false;
}

function checkHint() {
	jQuery('#hint').show();
	var params = new Array();
	params['handle_key'] = jQuery('#verify_ars_response').attr('data-handle_key');
	var response_tags = new Array('error','message','result','data');
	jQuery('#hint-content').html('');
	exec_xml('purplebook', 'getPurplebookHint', params, function(ret_obj) {
		if (ret_obj['result']) {
			show_and_hide(jQuery('#layer_senderid_verification'));
			alert(ret_obj['message']);
			switch (ret_obj['result']) {
				case 'verified':
					refreshCallbackList();
					break;
				case 'timeout':
					break;
			}
			return;
		}
		if (ret_obj['data']) {
			jQuery('#instruction').text('혹시 아래 전화번호로 거셨나요?');
			var data = ret_obj['data']['item'];
			if (!jQuery.isArray(data)) {
				data = new Array(data);
			}
			for (var i = 0; i < data.length; i++) {
				jQuery('#hint-content').append(data[i]+"<br />");
			}
			setTimeout(checkHint, 10000);
		} else {
			jQuery('#instruction').text('전화요금은 무료입니다!');
			setTimeout(checkHint, 10000);
		}
	}, response_tags);
}

(function($) {
	jQuery(function($) {
		setTimeout(checkHint, 10000);

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
				show_and_hide(jQuery('#layer_senderid_verification'));
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
