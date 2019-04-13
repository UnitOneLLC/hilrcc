<?php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="course_report.csv"');


	/*
	 * generate course report in CSV format
	 */
	function HILRCC_generate_course_report()
	{
		$timeslot_map = array("",
					  "Monday AM",
					  "Monday PM",
					  "Tuesday AM",
					  "Tuesday PM",
					  "Wednesday AM",
					  "Wednesday PM",
					  "Thursday AM",
					  "Thursday PM");

		$semester = get_option('current_semester');
		$search = array();
		$search['form_id'] = HILRCC_PROPOSAL_FORM_ID;
		$search['status'] = 'active';	
		$search['field_filters'] = array();
		$search['field_filters'][] = array('key'=>HILRCC_FIELD_ID_SEMESTER, 'operator'=>'is', 'value'=>$semester);
		$search['field_filters'][] = array('key'=>HILRCC_FIELD_ID_STATUS, 'operator'=>'is', 'value'=>HILRCC_PROP_STATUS_VALUE_APPROVED);
		$search['field_filters'][] = array('key'=>HILRCC_FIELD_ID_DURATION, 'operator'=>'isnot', 'value'=>'Either First or Second Half');
		$search['field_filters'][] = array('key'=>HILRCC_FIELD_ID_TIMESLOT, 'operator'=>'isnot', 'value'=>'');
		
		$entries = GFAPI::get_entries(0, $search, null, array( 'offset' => 0, 'page_size' => 1000));

		$columns = [
			["label"=>'CRN', "fid"=>HILRCC_FIELD_ID_COURSE_NO],
			["label"=>'COURSE NAME', "fid"=>HILRCC_FIELD_ID_TITLE],
			["label"=>'SGL1 - First and Last', "fid"=>[HILRCC_FIELD_ID_SGL1_FIRST, HILRCC_FIELD_ID_SGL1_LAST]],
			["label"=>'SGL1 - FIRST', "fid"=>HILRCC_FIELD_ID_SGL1_FIRST],
			["label"=>'SGL1 - LAST', "fid"=>HILRCC_FIELD_ID_SGL1_LAST],
			["label"=>'SGL1 EMAIL', "fid"=>HILRCC_FIELD_ID_SGL1_EMAIL],
			["label"=>'SGL1 - prev led', "fid"=>HILRCC_FIELD_ID_SGL1_PREV],
			["label"=>'SGL2 - First and Last', "fid"=>[HILRCC_FIELD_ID_SGL2_FIRST, HILRCC_FIELD_ID_SGL2_LAST]],
			["label"=>'SGL2 - FIRST', "fid"=>HILRCC_FIELD_ID_SGL2_FIRST],
			["label"=>'SGL2 - LAST', "fid"=>HILRCC_FIELD_ID_SGL2_LAST],
			["label"=>'SGL2 EMAIL', "fid"=>HILRCC_FIELD_ID_SGL2_EMAIL],
			["label"=>'SGL2 - prev led', "fid"=>HILRCC_FIELD_ID_SGL2_PREV],
			["label"=>'slot', "fid"=>HILRCC_FIELD_ID_TIMESLOT, "map"=>$timeslot_map],
			["label"=>'duration', "fid"=>HILRCC_FIELD_ID_DURATION],
			["label"=>'room', "fid"=>HILRCC_FIELD_ID_ROOM]
		];
		
		$response = "";
		$first = true;
		foreach ($columns as $col) {
			if (!$first)
				$response .= ",";
			$first = false;
			$response .= $col["label"];
		}
		$response .= "\n";

		foreach ($entries as $entry) {
			$first = true;
			foreach ($columns as $col) {
				if (!$first)
					$response .= ",";
				$first = false;
				if (!is_array($col["fid"])) {
					$field_val = rgar($entry, $col["fid"]);
				}
				else {
					$colids = $col["fid"];
					$field_val = "";
					$firstx = true;
					foreach ($colids as $colid) {
						if (!$firstx) {
							$field_val .= " ";
						}
						$firstx = false;
						$field_val .= rgar($entry, $colid);
					}
					
				}
				if (!empty($col["map"])) {
					$field_val = $col["map"][$field_val];
				}
				
				elseif  (strpos($field_val, ",") !== false) {
					$field_val = '"' . $field_val . '"';
				}
				
				$response .= $field_val;
			}
			$response .= "\n";
		}
		
		echo $response;
	}

	HILRCC_generate_course_report();

?>