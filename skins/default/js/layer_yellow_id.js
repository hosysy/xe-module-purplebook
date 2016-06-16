/**
 * @fileoverview 알림톡 옐로아이디 목록 
 * @requires layer_yellow_id.html
 */

/** @define {boolean} permission을 이용해 코드가 중복되지 않도록 한다. */
if (!yellow_id_javascript_permission) {
	var yellow_id_javascript_permission = false;
}

(function($) {
	jQuery(function($) {
		/**
		 * js file이 한번만 로딩되도록
		 */
		if (yellow_id_javascript_permission == false) {
		   	yellow_id_javascript_permission = true;
		} else {
			return;
		}

		jQuery('input','#layer_yellow_id').filter(function(index){ return !jQuery(this).hasClass('help'); }).tipsy({html:true});

		/**
		 * 옐로아이디 클릭시
		 */
		$(document).on('click', '#smsPurplebookYellowIDList .yellow_id', function() {
			$('#smsPurplebookYellowID').val($(this).text()).select();
			$('#smsPurplebookSenderKey').val($(this).attr('data-sender_key'));
			$obj = $(this).parents('#layer_yellow_id');
			show_and_hide($obj);
		});

		/**
		 * .default 클릭시
		 */
		$(document).on('click', '#smsPurplebookYellowIDList .default', function() {
			var alim_user_srl = $(this).parent().attr('alim_user_srl');
			var flag_default = 'Y';
			if ($(this).hasClass('on') == true) flag_default = 'N';

			request_default_yellow_id(alim_user_srl, flag_default);
			//$('#smsPurplebookYellowID').val().select();
		});

		/**
		 * 추가 버튼 클릭시
		 */
		$(document).on('click', '#smsPurplebookButtonAddYellowID', function() {
			alert('알림톡 신청 페이지로 이동합니다.');
			var url = "http://www.coolsms.co.kr/AboutAlimTalk";
			$(location).attr('href',url);
		});
	});
}) (jQuery);

/**
 * 옐로아이디 리스트 새로고침
 */
function refreshCallbackList() {
	var params = new Array();
	var response_tags = new Array('error','message','data');
	exec_xml('purplebook', 'getPurplebookAlimtalkInfo', params, completeGetCallbackList, response_tags);
}

function completeGetCallbackList(ret_obj, response_tags) {
	$list = jQuery('#smsPurplebookYellowIDList').empty();
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
			$list.append('<li alim_user_srl ="' + data[i].alim_user_srl + '"><span class="default' + on + '"></span><span class="yellow_id" data-sender_key="' + data[i].sender_key + '">' + data[i].yellow_id.substring(0,16) + '</span></li>');
		}
	}
}

function request_default_yellow_id(alim_user_srl, flag_default) {
	var params = new Array();
	params['alim_user_srl'] = alim_user_srl;
	params['flag_default'] = flag_default;
	var response_tags = new Array('error','message');
	exec_xml('purplebook', 'procPurplebookSetDefaultYellowID', params, function() { refreshCallbackList(); }, response_tags);
}
