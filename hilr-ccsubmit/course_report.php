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
			["label"=>'course_number', "fid"=>HILRCC_FIELD_ID_COURSE_NO],
			["label"=>'title', "fid"=>HILRCC_FIELD_ID_TITLE],
			["label"=>'sgl1_first', "fid"=>HILRCC_FIELD_ID_SGL1_FIRST],
			["label"=>'sgl1_last', "fid"=>HILRCC_FIELD_ID_SGL1_LAST],
			["label"=>'sgl2_first', "fid"=>HILRCC_FIELD_ID_SGL2_FIRST],
			["label"=>'sgl2_last', "fid"=>HILRCC_FIELD_ID_SGL2_LAST],
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
				$field_val = rgar($entry, $col["fid"]);
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