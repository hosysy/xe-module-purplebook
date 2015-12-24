<?php
/**
 * @class  purplebookView
 * @author NURIGO(contact@nurigo.net)
 * @brief  purplebookView
 */
class purplebookView extends purplebook
{
	var $use_point;
	var $sms_point;
	var $lms_point;
	var $alert_message="";

	function init()
	{
		// 템플릿 경로 설정
		if (!$this->module_info->skin) $this->module_info->skin = 'default';
		Context::set("module_skin", $this->module_info->skin);
		$this->setTemplatePath($this->module_path."skins/{$this->module_info->skin}");
	}

	/**
	 * purplebook 기본화면
	 */
	function dispPurplebookIndex($basecamp = FALSE)
	{
		global $lang;

		$logged_info = Context::get('logged_info');

		$lang_list = array('sms','lms','mms','reserv_send','direct_send','msg_not_enough_money','available_sms_number','arranged_sms_number','msg_will_you_try','reservation_datetime','number_to_send','msg_will_you_send','msg_not_enough_money','available_lms_number','arranged_lms_number','available_mms_number','arranged_mms_number','msg_login_required');

		$widget_lang = new StdClass();
		foreach ($lang_list as $val)
		{
			$widget_lang->{$val} = $lang->{$val};
		}
		Context::set('widget_lang', $widget_lang);

		if($logged_info)
		{
			$oPurplebookModel = &getModel('purplebook');
			$callback = $oPurplebookModel->getDefaultSenderID($logged_info->user_id, $basecamp);
			if(is_string($callback)) Context::set('callback', $callback);
		}

		$this->setTemplateFile('address');

		// 관리자가 아니면 그냥 리턴한다.
		if(!$logged_info || $logged_info->is_admin != 'Y') return;

		// administrator이고 group_srl과 cellphone_fieldname이 파라메터로 넘어올 경우 해당 그룹의 회원의 전화번호 목록을 출력할 수 있도록 템플릿에 넘겨준다.
		$group_srl = Context::get('group_srl');
		$cellphone_fieldname = Context::get('cellphone_fieldname');
		if(!$group_srl || !$cellphone_fieldname) return;

		// 해당 그룹의 회원 목록 가져오기
		$oMemberModel = &getModel('member');
		$args->selected_group_srl = $group_srl;
		$args->page = 1;
		$args->list_count = 99999;
		$args->page_count = 10;
		$output = executeQueryArray('member.getMemberListWithinGroup', $args);
		if(!$output->toBool()) return $output;
		$member_list = $output->data;
		foreach($member_list as $key => $member_info)
		{
			// 확장변수에서 전화번호 필드의 값을 가져온다.
			$extra_vars = unserialize($member_info->extra_vars);
			$member_list[$key]->_purplebook->cellphone = is_array($extra_vars->{$cellphone_fieldname}) 
				? implode($extra_vars->{$cellphone_fieldname}) 
				: $extra_vars->{$cellphone_fieldname};
		}
		Context::set('member_list', $member_list);
	}

	/**
	 * excel download page
	 */
	function dispPurplebookExcelDownload() 
	{
		$download_fields = Context::get('download_fields');
		if (!$download_fields) $download_fields = "user_id,user_name,cellphone";
		$download_fields_arr = explode(',', $download_fields);

		// check permission
		$allowed = false;
		$allow_group = Context::get('allow_group');
		$group_srls = explode(',', $allow_group);
		$logged_info = Context::get('logged_info');
		if (!$logged_info) return new Object(-1, 'msg_invalid_request');
		$oMemberModel = &getModel('member');
		foreach ($group_srls as $group_srl) {
			$group = $oMemberModel->getGroup($group_srl);
			if (in_array($group->title, $logged_info->group_list)) {
				$allowed = true;
			}
		}
		if (!$allowed && $logged_info->is_admin != 'Y') return new Object(-1, 'msg_invalid_request');

		header("Content-Type: Application/octet-stream;");
		header("Content-Disposition: attachment; filename=\"members-" . date('Ymd') . ".xls\"");

		echo '<html>';
		echo '<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /></head>';
		echo '<body>';
		echo '<table>';

		// header
		echo '<tr>';
		foreach ($download_fields_arr as $field) {
			echo "<th>{$field}</th>";
		}
		echo "</tr>\n";

		// arguments
		$args = new Object();
		$this->makeArgs($args);

		// include utility
		require_once('purplebook.utility.php');

		// only mysql
		$db_info = Context::getDBInfo();
		if ($args->group_srl) {
			$query = "SELECT * FROM {$db_info->db_table_prefix}_member member"
				." JOIN {$db_info->db_table_prefix}_member_group_member member_group"
				." ON  member_group.member_srl = member.member_srl"
				." WHERE member_group.group_srl = {$args->group_srl}";

			$oDB = &DB::getInstance();
			$result = $oDB->_query($query);
			require_once('zMigration.class.php');
			$dbtool = new zMigration();
			$dbtool->setDBInfo($db_info);

			while ($row = $dbtool->fetch($result)) {
				$obj = $this->getResponseObject($row, $download_fields_arr);
				$obj->cellphone = CSUtility::getDashTel(str_replace('|@|', '', $obj->cellphone));
				// skip if no phone number.
				if (Context::get('nonphone_skip') && !$obj->cellphone) continue;
				echo '<tr>';
				foreach ($download_fields_arr as $field) {
					if (isset($obj->{$field})) echo '<td style="mso-number-format:\@\">' . $obj->{$field} . '</td>';
				}
				echo "</tr>\n";
				unset($obj);
				unset($row);
			}
		} else {
			// memory limit problem
			$query_id = 'purplebook.getMembers';
			$output = executeQueryArray($query_id, $args);

			foreach ($output->data as $no => $row) {
				$obj = $this->getResponseObject($row, $download_fields_arr);
				$obj->cellphone = CSUtility::getDashTel(str_replace('|@|', '', $obj->cellphone));
				// skip if no phone number.
				if (Context::get('nonphone_skip') && !$obj->cellphone) continue;
				echo '<tr>';
				foreach ($download_fields_arr as $field) {
					if (isset($obj->{$field})) echo '<td style="mso-number-format:\@\">' . $obj->{$field} . '</td>';
				}
				echo "</tr>\n";
				unset($obj);
				unset($row);
			}
		}

		// tail
		echo '</table>';
		echo '</body>';
		echo '</html>';

		exit(0);
	}

	/**
	 * 주소록 paging 리스트 
	 */
	function dispPurplebookPurplebookListPaging() 
	{
		$logged_info = Context::get('logged_info');
		if (!$logged_info)
			return new Object(-1, 'msg_not_logged');

		$args->user_id = $logged_info->user_id;
		$args->node_route = Context::get('node_route');
		$args->node_type = Context::get('node_type');
		$args->page = Context::get('page');

		$oPurplebookModel = &getModel('purplebook');
		$output = $oPurplebookModel->getPurplebookListPaging($args);

		if ((!is_array($output->data) || !count($output->data)) && $args->node_type == '1' && $args->node_route == '.') {
			return;
		}

		$data = array();

		if (is_array($output->data)) {
			foreach ($output->data as $no => $row) {
				$obj = new StdClass();
				$obj->attributes = new StdClass();
				$obj->attributes->id = $row->node_id;
				$obj->attributes->node_id = $row->node_id;
				$obj->attributes->node_name = $row->node_name;
				$obj->attributes->node_route = $row->node_route;
				$obj->attributes->phone_num = $row->phone_num;
				$obj->data = $row->node_name;
				$obj->state = "closed";
				$data[] = $obj;
			}
		}
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);

		Context::set('data', $data);
	}

	/**
	 * image file select 창
	 */
	function dispPurplebookFilePicker()
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info) {
			Context::set('message', Context::getLang('msg_login_required'));
			$this->setLayoutFile('default_layout');
			$this->setTemplateFile('filepicker_error');
			return;
		}

		$filter = Context::get('filter');
		if($filter) Context::set('arrfilter',explode(',',$filter));

		$this->setLayoutFile('default_layout');
		$this->setTemplateFile('filepicker');
	}

	/**
	 * 주소록 vcf로 다운로드 
	 */
	function dispPurplebookAddressVcfDownload()
	{
		$oPurplebookModel = &getModel('purplebook');

        $logged_info = Context::get('logged_info');
        if(!$logged_info) return new Object(-1, 'msg_not_logged');

		$node_route = Context::get('node_route');
		if(!$node_route) return new Object(-1, 'node_route is not defined');

		$args->member_srl = $logged_info->member_srl;
		$args->user_id = $logged_info->user_id;
		$args->node_route = $node_route;
		$args->node_type = "2";
		$output = executeQuery('purplebook.getPurplebookList', $args);
		if(!$output->toBool()) return $output;

		require_once('lib/vCard.php');

		header('Content-Type: text/x-vcard');  
		header('Content-Disposition: inline; filename= "address_list_' . date('Ymd') . '.vcf"');  
		header('Content-Length: '.filesize($vCard));  

		$vCard = new vCard;

		foreach($output->data as $key=>$row)
		{
			$vCard->n(iconv("UTF-8", "EUC-KR", $row->node_name), 'FirstName');
			$vCard->tel($row->phone_num);
			echo $vCard;
		}
	}


	/**
	 * 주소록 엑셀로 다운로드
	 */
	function dispPurplebookAddressDownload() 
	{
		$oPurplebookModel = &getModel('purplebook');

        $logged_info = Context::get('logged_info');
        if(!$logged_info) return new Object(-1, 'msg_not_logged');

        header("Content-Type: application/vnd.ms-excel;");
        header("Content-Disposition: attachment; filename=\"address_list_" . date('Ymd') . ".xls\"");

		$node_route = Context::get('node_route');
		if(!$node_route) return new Object(-1, 'node_route is not defined');

		$args->member_srl = $logged_info->member_srl;
		$args->user_id = $logged_info->user_id;
		$args->node_route = $node_route;
		$args->node_type = "2";
		$output = executeQuery('purplebook.getPurplebookList', $args);
		if(!$output->toBool()) return $output;

        echo "<?xml version='1.0' encoding='utf-8'?>\n";
        echo <<<THESTRING
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" 
xmlns:o="urn:schemas-microsoft-com:office:office" 
xmlns:x="urn:schemas-microsoft-com:office:excel" 
xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" 
xmlns:html="http://www.w3.org/TR/REC-html40">
<Worksheet ss:Name="주소록리스트"> 
<Table> 
<Row> 
  <Cell><Data ss:Type="String">이름</Data></Cell> 
  <Cell><Data ss:Type="String">번호</Data></Cell> 
  <Cell><Data ss:Type="String">메모1</Data></Cell> 
  <Cell><Data ss:Type="String">메모2</Data></Cell> 
  <Cell><Data ss:Type="String">메모3</Data></Cell> 
</Row> 
THESTRING;
        foreach($output->data as $key=>$row)
        {
            echo sprintf('<Row><Cell><Data ss:Type="String">%s</Data></Cell><Cell><Data ss:Type="String">%s</Data></Cell><Cell><Data ss:Type="String">%s</Data></Cell><Cell><Data ss:Type="String">%s</Data></Cell><Cell><Data ss:Type="String">%s</Data></Cell></Row>', $row->node_name, $row->phone_num, $row->memo1, $row->memo2, $row->memo3);
        }

        echo <<<THESTRING
</Table> 
</Worksheet> 
</Workbook> 
THESTRING;
        exit(0);
    }
}
/* End of file purplebook.view.php */
/* Location: ./modules/purplebook/purplebook.view.php */
