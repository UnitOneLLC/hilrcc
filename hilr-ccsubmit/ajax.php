<?php
/*
	HILR CCsubmit plugin - ajax.php
	Handlers for ajax calls
*/

	/*
 	 * ajax handler to re-number the courses
 	 */
	add_action('wp_ajax_renumber_courses', 'ajax_renumber_courses');
	function ajax_renumber_courses() {
		$start = stripslashes_deep($_POST["start"]);
		$startNumber = intval($start);
		$semester = stripslashes_deep($_POST["semester"]);
		do_renumber_courses($startNumber, $semester);
		echo "SUCCESS";
	}

	function do_renumber_courses($startNumber, $semester) {
		$search = array();
		$search['form_id'] = HILRCC_PROPOSAL_FORM_ID;
		$search['status'] = 'active';	
		$search['field_filters'] = array();
		$search['field_filters'][] = array('key'=>HILRCC_FIELD_ID_SEMESTER, 'operator'=>'is', 'value'=>$semester);
		$search['field_filters'][] = array('key'=>HILRCC_FIELD_ID_STATUS, 'operator'=>'is', 'value'=>HILRCC_PROP_STATUS_VALUE_APPROVED);
		$search['field_filters'][] = array('key'=>HILRCC_FIELD_ID_DURATION, 'operator'=>'isnot', 'value'=>'Either First or Second Half');
		$search['field_filters'][] = array('key'=>HILRCC_FIELD_ID_TIMESLOT, 'operator'=>'isnot', 'value'=>'');
		$search['field_filters'][] = array('key'=>HILRCC_FIELD_ID_TIMESLOT, 'operator'=>'isnot', 'value'=>'0');
		
		/** THERE MAY BE ADDITIONAL SEARCH CRITERIA **/
			
		$entries = GFAPI::get_entries(0, $search, null, array( 'offset' => 0, 'page_size' => 1000));
		
		if (is_wp_error($entries)) {
			echo $entries.get_error_message($entries.get_error_code());
		}
		else {
		    usort($entries, 'catalog_comparator');
			
			$number = HILRCC_COURSENO_FULLSEM_START;
			$sawFirstHalf = false;
			$sawSecondHalf = false;
			
			foreach ($entries as &$entry) {
				$term = $entry[HILRCC_FIELD_ID_DURATION];
				if (($term == "First Half") && !$sawFirstHalf) {
					$sawFirstHalf = true;
					$number = HILRCC_COURSENO_1STHALF_START;
				}
				else if (($term == "Second Half") && !$sawSecondHalf) {
					$sawSecondHalf = true;
					$number = HILRCC_COURSENO_2NDHALF_START;
				}
				
				GFAPI::update_entry_field($entry['id'], HILRCC_FIELD_ID_COURSE_NO, $number);
				$number = $number + 1;
			}
		}
	}

	/*
	 * ajax handler to clear all course numbers
	 */
	add_action('wp_ajax_clear_course_numbers', 'clear_course_numbers');
	function clear_course_numbers() {
		$semester = stripslashes_deep($_POST["semester"]);
		$search = array();
		$search['status'] = 'active';
		$search[HILRCC_FIELD_ID_SEMESTER] = $semester;
		/** THERE MAY BE ADDITIONAL SEARCH CRITERIA **/
		
		$entries = GFAPI::get_entries(0, $search, null, array( 'offset' => 0, 'page_size' => 1000));
		if (is_wp_error($entries)) {
			echo $entries.get_error_message($entries.get_error_code());
		}
		else {
			foreach ($entries as &$entry) {
				GFAPI::update_entry_field($entry['id'], HILRCC_FIELD_ID_COURSE_NO, null);
			}
			echo "SUCCESS";
		}
	}

	/*
	 * ajax call for adding a comment
	 */
	add_action('wp_ajax_add_comment', 'HILRCC_ajax_add_comment');
	function HILRCC_ajax_add_comment()
	{
	    if (!current_user_can('gravityforms_edit_entries')) {
	        echo ("ERROR: capability");
	        return;
	    }
	    
	    $comment = stripslashes_deep($_POST["text"]);
	    if ($comment == '') {
	        echo ('EMPTY');
	        return;
	    }
	    $entry_id = $_POST["entryId"];
	    if ($entry_id == '') {
	        echo ("ERROR: entry id missing");
	        return;
	    }
	    
	    $entry = GFAPI::get_entry($entry_id);
	    if (is_wp_error($entry)) {
	        echo ("ERROR: " . $entry . get_error_message());
	        return;
	    }
	    
	    $result = HILRCC_add_comment($entry_id, $comment);

	    if ($result) {
			HILRCC_update_last_mod_time($entry);
	        echo ("SUCCESS");
	    } else {
	        echo ("FAIL: GFAPI");
	    }
	}

	function HILRCC_add_comment($entry_id, $comment) {
	    $form      = GFAPI::get_form(HILRCC_PROPOSAL_FORM_ID);
	    $entry     = GFAPI::get_entry($entry_id);
	    $discfield = GFFormsModel::get_field($form, HILRCC_FIELD_ID_DISCUSSION);
	    $discvalue = $discfield->get_value_save_entry($comment, $form, $input_name = '', $entry_id, $entry);
	    $result    = GFAPI::update_entry_field($entry_id, HILRCC_FIELD_ID_DISCUSSION, $discvalue);
	    return $result;
	}


	/*
	 * ajax call to check if entry is assigned to logged-in user
	 */
	add_action('wp_ajax_is_entry_assigned_to_user', 'check_entry_assigned');
	function check_entry_assigned()
	{
		$entry_id = $_POST["entry_id"];
		if (is_entry_assigned_current_user(GFAPI::get_entry($entry_id))) {
			echo "true";
		}
		else {
			echo "false";
		}
	}

	/*
	 * ajax call to get schedule slot counts
	 */
	function HILRCC_ajax_time_preference_summary() {
		$ts = HILRCC_fetch_time_summary();
		echo json_encode($ts);
	}
	add_action('wp_ajax_fetch_time_preference_summary', 'HILRCC_ajax_time_preference_summary');


	function HILRCC_fetch_time_summary() {
		$search = array();
		$search[HILRCC_FIELD_ID_SEMESTER] = $semester;
		$search['form_id'] = HILRCC_PROPOSAL_FORM_ID;
		$search['status'] = 'active';
		/** THERE MAY BE ADDITIONAL SEARCH CRITERIA **/
		
		$map = array("",
					  "Monday AM",
					  "Monday PM",
					  "Tuesday AM",
					  "Tuesday PM",
					  "Wednesday AM",
					  "Wednesday PM",
					  "Thursday AM",
					  "Thursday PM");
			
		$entries = GFAPI::get_entries(0, $search, null, array( 'offset' => 0, 'page_size' => 1000));
		$result = array();
		
		$result['Full Term'] = array("Monday AM" => 0, "Monday PM" => 0,
						 "Tuesday AM" => 0, "Tuesday PM" => 0,
						 "Wednesday AM" => 0, "Wednesday PM" => 0,
						 "Thursday AM" => 0, "Thursday PM" => 0);
		$result['First Half'] = array("Monday AM" => 0, "Monday PM" => 0,
						 "Tuesday AM" => 0, "Tuesday PM" => 0,
						 "Wednesday AM" => 0, "Wednesday PM" => 0,
						 "Thursday AM" => 0, "Thursday PM" => 0);
		$result['Second Half'] = array("Monday AM" => 0, "Monday PM" => 0,
						 "Tuesday AM" => 0, "Tuesday PM" => 0,
						 "Wednesday AM" => 0, "Wednesday PM" => 0,
						 "Thursday AM" => 0, "Thursday PM" => 0);
		
		foreach ($entries as &$entry) {
			$slot = rgar($entry, HILRCC_FIELD_ID_TIMESLOT);
			$term = rgar($entry, HILRCC_FIELD_ID_DURATION);
			if (!empty($slot)) {
				if (array_key_exists($term, $result)) {
					$result[$term][$map[$slot]] += 1;
				}
			}
		}
		
		return $result;
	}

	/*
	 * ajax call to update the time slot
	 */
	add_action('wp_ajax_update_timeslot', 'HILRCC_update_time_slot');
	function HILRCC_update_time_slot() {
		$entry_id = $_POST["entry_id"];
		$timeslot = $_POST["value"];
		$slot_val;
		$lookup = array(
					  "Monday AM" => 1,
					  "Monday PM" => 2,
					  "Tuesday AM" => 3,
					  "Tuesday PM" => 4,
					  "Wednesday AM" => 5,
					  "Wednesday PM" => 6,
					  "Thursday AM" => 7,
					  "Thursday PM" => 8);

		if (empty($timeslot)) {
			$slot_val = 0;
		}
		elseif (array_key_exists($timeslot, $lookup)) {
			$slot_val = $lookup[$timeslot];
		}
		
		if (isset($slot_val)) {
		    $result   = GFAPI::update_entry_field($entry_id, HILRCC_FIELD_ID_TIMESLOT, $slot_val) ;
		}
		else {
			echo("FAIL: bad slot: $timeslot");
			return;
		}
	    
	    if ($result) {
	        echo ("SUCCESS");
	    } else {
	        echo ("FAIL: GFAPI");
	    }
	}

	/*
	 * ajax call to update the class size
	 */
	add_action('wp_ajax_update_class_size', 'HILRCC_update_class_size');
	function HILRCC_update_class_size() {
		$entry_id = $_POST["entry_id"];
		$class_size = $_POST["value"];
		
		$result   = GFAPI::update_entry_field($entry_id, HILRCC_FIELD_ID_CLASS_SIZE, $class_size) ;
	    
	    if ($result) {
	        echo ("SUCCESS");
	    } else {
	        echo ("FAIL: GFAPI");
	    }
	}

	/*
	 * ajax call to update the duration field
	 */
	add_action('wp_ajax_update_duration', 'HILRCC_update_duration');
	function HILRCC_update_duration() {
		$entry_id = $_POST["entry_id"];
		$duration = $_POST["value"];
		
		$result   = GFAPI::update_entry_field($entry_id, HILRCC_FIELD_ID_DURATION, $duration) ;
	    
	    if ($result) {
			HILRCC_set_either_half_flag_by_id($entry_id);
	        echo ("SUCCESS");
	    } else {
	        echo ("FAIL: GFAPI");
	    }
	}

	/*
	 * ajax call to update the room field
	 */
	add_action('wp_ajax_update_room', 'HILRCC_update_room');
	function HILRCC_update_room() {
		$entry_id = $_POST["entry_id"];
		$room = $_POST["value"];
		
		$result   = GFAPI::update_entry_field($entry_id, HILRCC_FIELD_ID_ROOM, $room) ;
	    
	    if ($result) {
	        echo ("SUCCESS");
	    } else {
	        echo ("FAIL: GFAPI");
	    }
	}

	/*
	 * ajax call to update all automatic (computed) fields (takes semester as arg)
	 */
	add_action('wp_ajax_update_computed_fields', 'HILRCC_ajax_update_computed_fields');
	function HILRCC_ajax_update_computed_fields()
	{
		$semester = stripslashes_deep($_POST["semester"]);
		$search = array();
		$search[HILRCC_FIELD_ID_SEMESTER] = $semester;
		$search['form_id'] = HILRCC_PROPOSAL_FORM_ID;
		$search['status'] = 'active';
		$entries = GFAPI::get_entries(0, $search, null, array( 'offset' => 0, 'page_size' => 1000));
		
		if (is_wp_error($entries)) {
			echo "FAIL: " . $entries.get_error_message($entries.get_error_code());
		}
		else {
			foreach($entries as &$entry) {
				$entry_id = $entry['id'];
				HILRCC_update_computed_fields($entry_id);
			}
			echo "SUCCESS";
		}
	}

?>