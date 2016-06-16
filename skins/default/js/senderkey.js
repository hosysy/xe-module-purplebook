/**
 * @fileoverview 알림톡 SenderKey 목록 
 * @requires layer_senderkey.html
 */

/** @define {boolean} permission을 이용해 코드가 중복되지 않도록 한다. */
if (!senderkey_javascript_permission) {
	var senderkey_javascript_permission = false;
}

(function($) {
	jQuery(function($) {
		/**
		 * js file이 한번만 로딩되도록
		 */
		if (senderkey_javascript_permission == false) {
		   	senderkey_javascript_permission = true;
		} else {
			return;
		}

		jQuery('input','#layer_senderkey').filter(function(index){ return !jQuery(this).hasClass('help'); }).tipsy({html:true});

		/**
		 * SenderKey 클릭시
		 */
		$(document).on('click', '#smsPurplebookSenderKeyList .sender_key', function() {
			$('#smsPurplebookCallback').val($(this).text()).select();
			$obj = $(this).parents('#layer_sendid');
			show_and_hide($obj);
		});

		/**
		 * .default 클릭시
		 */
		$(document).on('click', '#smsPurplebookSenderKeyList .default', function() {
			var sender_key = $(this).parent().attr('sender_key');
			request_default_number(sender_key);
			$('#smsPurplebookCallback').val(sender_key).select();
		});
	});
}) (jQuery);

/**
 * SenderKey 리스트 새로고침
 */
function refreshCallbackList() {
	var params = new Array();
	var response_tags = new Array('error','message','data');
	//exec_xml('purplebook', 'getPurplebookSenderKeys', params, completeGetCallbackList, response_tags);
}

function completeGetCallbackList(ret_obj, response_tags) {
	$list = jQuery('#smsPurplebookSenderKeyList').empty();
	if (ret_obj['data']) {
		var data = ret_obj['data']['item'];
		if (!jQuery.isArray(data)) {
			data = new Array(data);
		}
		for (var i = 0; i < data.length; i++) {
			if (data[i].flag_default == 'Y') {
				on = ' on';
			} else {
				on = '';
			}
			$list.append('<li sender_key="' + data[i].idno + '"><span class="default' + on + '"></span><span class="sender_key">' + data[i].phone_number + '</span></li>');
		}
	}
}

function request_default_senderkey(handle_key) {
	var params = new Array();
	params['handle_key'] = handle_key;
	var response_tags = new Array('error','message');
	exec_xml('purplebook', 'procPurplebookSetDefaultSenderKey', params, function() { refreshCallbackList(); }, response_tags);
}
