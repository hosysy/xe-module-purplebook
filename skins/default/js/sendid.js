/**
 * @fileoverview 발신번호 목록 관련
 * @requires layer_sendid.html
 */

/** @define {boolean} permission을 이용해 코드가 중복되지 않도록 한다. */
if (!sendid_javascript_permission) {
	var sendid_javascript_permission = false;
}

(function($) {
	jQuery(function($) {
		/**
		 * js file이 한번만 로딩되도록
		 */
		if (sendid_javascript_permission == false) {
		   	sendid_javascript_permission = true;
		} else {
			return;
		}

		jQuery('input','#layer_sendid').filter(function(index){ return !jQuery(this).hasClass('help'); }).tipsy();

		/**
		 * 발신번호 delete
		 */
		$(document).on('click', '.deleteCallback', function() {
			deleteCallback($(this).parent().attr('callback_srl'));
		});

		/**
		 * 전화번호 클릭시
		 */
		$(document).on('click', '#smsPurplebookCallbackList .phonenum', function() {
			$('#smsPurplebookCallback').val($(this).text()).select();
			$obj = $(this).parents('#layer_sendid');
			show_and_hide($obj);
		});

		/**
		 * .default 클릭시
		 */
		$(document).on('click', '#smsPurplebookCallbackList .default', function() {
			var handle_key = $(this).parent().attr('callback_srl');
			var phonenum = $('.phonenum',$(this).parent()).text();
			request_default_number(handle_key);
			$('#smsPurplebookCallback').val(phonenum).select();
		});

		/**
		 * layer_sendid 추가버튼 클릭시
		 */
		$(document).on('click', '#smsPurplebookButtonAddCallback', function() {
			var params = new Array();
			params['phone_number'] = $('#smsPurplebookInputCallback').val();
			var response_tags = new Array('error', 'message', 'handle_key', 'ars_number');
			exec_xml('purplebook', 'getPurplebookSenderIDVerificationInfo', params, function(ret_obj) {
				if (ret_obj['handle_key']) {
					var ars_number = ret_obj['ars_number'];
					var handle_key = ret_obj['handle_key'];
					popup_layer('layer_senderid_verification', '#layer_senderid_verification', function() {
						$('#ars_number').text(ars_number);
						$('#verify_ars_response').attr('data-handle_key', handle_key);
					});
				}
			}, response_tags);
			return false;
		});
		/*
		$(document).on('click', '#smsPurplebookButtonAddCallback', function() {
			var params = new Array();
			params['phonenum'] = $('#smsPurplebookInputCallback').val();
			var response_tags = new Array('error','message');
			exec_xml('purplebook', 'procPurplebookSaveCallbackNumber', params, function() {
				//popup_layer('layer_senderid_verification', '#layer_senderid_verification');
			   refreshCallbackList();
			   $('#smsPurplebookInputCallback').val('');
			}, response_tags);
			return false;
		});
		*/
	});
}) (jQuery);

/**
 * 발신번호 리스트 새로고침
 */
function refreshCallbackList() {
	var params = new Array();
	var response_tags = new Array('error','message','data');
	exec_xml('purplebook', 'getPurplebookSenderIDs', params, completeGetCallbackList, response_tags);
}

function completeGetCallbackList(ret_obj, response_tags) {
	$list = jQuery('#smsPurplebookCallbackList').empty();
	if (ret_obj['data']['data']) {
		var data = ret_obj['data']['data']['item'];
		if (!jQuery.isArray(data)) {
			data = new Array(data);
		}
		for (var i = 0; i < data.length; i++) {
			if (data[i].flag_default == 'Y') {
				on = ' on';
			} else {
				on = '';
			}
			$list.append('<li callback_srl="' + data[i].idno + '"><span class="default' + on + '"></span><span class="phonenum">' + data[i].phone_number + '</span><span class="deleteCallback" title="삭제">삭제</span></li>');
		}
	}
}

/**
 * 발신번호 삭제
 */
function deleteCallback(callback_srl) {
	var params = new Array();
	params['handle_key'] = callback_srl;
	var response_tags = new Array('error','message');
	exec_xml('purplebook', 'procPurplebookDeleteSenderID', params, function() { refreshCallbackList(); }, response_tags);
}

function request_default_number(handle_key) {
	var params = new Array();
	params['handle_key'] = handle_key;
	var response_tags = new Array('error','message');
	exec_xml('purplebook', 'procPurplebookSetDefaultSenderID', params, function() { refreshCallbackList(); }, response_tags);
}
