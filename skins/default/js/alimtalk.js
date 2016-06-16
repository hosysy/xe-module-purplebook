/**
 * @fileoverview 알림톡 템플릿 메시지 리스트
 * @requires layer_alimtalk.html
 */

/** @define {boolean} permission을 이용해 코드가 중복되지 않도록 한다. */
if (!alimtalk_javascript_permission) {
	var alimtalk_javascript_permission = false;
}

(function($) {
	jQuery(function($) {
		/**
		 * js file이 한번만 로딩되도록
		 */
		if (alimtalk_javascript_permission == false) {
		   	alimtalk_javascript_permission = true;
		} else {
			return;
		}

		/**
		 * li 클릭시 해당 내용 screen에 입력
		 */
		$(document).on('click', '#layer_alimtalk li', function() {
			var content = $(this).attr('title');
			template_code = $(this).attr('template_code');
			$current = get_active_textarea();
			$current.val(content);
			$layer = $('#layer_alimtalk');
			show_and_hide($layer);
		});
	});
}) (jQuery);
