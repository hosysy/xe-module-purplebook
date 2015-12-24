<?php

/* 
	작성자 : 비쥴펜(xcream at naver)
	작성일 : 2012. 7. 6.
	라이센스 : GPL
	제목 : xlsx 심플 리더기
	버전 : 0.1
*/

//ini_set('memory_limit',-1);
//set_time_limit(0);

class excel_xlsx_reader
{
	function Exl2phpTime($tRes, $dFormat="1900")
	{
		if($dFormat == "1904")
		{
			$fixRes = 24107.375; 
		}
		else
		{
			$fixRes = 25569.375; 
		}

	    return intval((($tRes - $fixRes)* 86400)); 
	} 

	function init($inputFileName)
	{
		$this->xlsx = $inputFileName;

		$this->zip =zip_open($this->xlsx);
		$this->string_pack = array();

		if($this->zip)
		{
			while($zip_entry = zip_read($this->zip))
			{
				if(zip_entry_name($zip_entry)== 'xl/sharedStrings.xml')
				{	
					if(zip_entry_open($this->zip, $zip_entry, "r"))
					{
						$buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
						$arr = simplexml_load_string($buf);
						$obja = $arr->si;
						$length = sizeof($obja);

						for($i =0; $i<$length; $i++)
						{
							$this->string_pack[$i] =(string)$obja[$i]->t;	
						}
						zip_entry_close($zip_entry);
					}
			    }
		    }
		}

		$this->close();
	}

	function close()
	{
		zip_close($this->zip);
	}

	/**
	 * @breif load_sheet 
	 * @params $sheet_index : sheet_number
	 */
	function load_sheet($sheet_index = 1)
	{
		$this->zip = zip_open($this->xlsx);
		if($this->zip)
		{
			while($zip_entry = zip_read($this->zip))
			{
				// 실제 로드되는 파일
				if(zip_entry_name($zip_entry)== 'xl/worksheets/sheet'.$sheet_index.'.xml')
				{	
					if(zip_entry_open($this->zip, $zip_entry, "r"))
					{
						$buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
						$arr = simplexml_load_string($buf);
						$this->rows = &$arr->sheetData->row;
						$this->rowsize = sizeof($this->rows);

						if($this->rowsize > 0)
						{
							$colsize = explode(":",(string)$this->rows[0]['spans']);
							$this->colsize =(int)array_pop($colsize);	// 1:7 이런식으로 값이 들어있음.
						}
						else
						{
							$this->colsize = 0;
						}

						zip_entry_close($zip_entry);
					}
			    }
		    }
		}
	}

	function val($y,$x)
	{
		$cols = $this->rows[$y]->c;
		if(isset($cols[$x]))
		{
			$col = $cols[$x];
			
			if(isset($col['t'])&&(string)$col['t']=='s')
			{
				// 문자일 경우	
				$value =  $this->string_pack[(int)$col->v];
			}
			else if(isset($col['s'])&&(string)$col['s']=='1') 
			{	
				// 날짜 일 경우
				$value =  $this->Exl2phpTime((float)$col->v);
			}
			else
			{
				$value =(string)$col->v;
			}
		}
		else
		{
			$value = '';
		}
		return $value;
	}
}

?>
