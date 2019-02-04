<?php
/*
	HILR CCsubmit plugin - business logic module
*/
	$duration_map = array(
		"Full Term" => 1,
		"Full Term Delayed Start" => 1,
		"First Half" => 2,
		"Second Half" => 3,
		"Either First or Second Half" => 4
	);

	/* comparison function for catalog sort */
	function catalog_comparator($a_entry, $b_entry)
	{
		global $duration_map;
		
		$a_duration = $duration_map[$a_entry[HILRCC_FIELD_ID_DURATION]];
		$b_duration = $duration_map[$b_entry[HILRCC_FIELD_ID_DURATION]];
		if ($a_duration < $b_duration) {
			return -1;
		} elseif ($a_duration > $b_duration) {
			return 1;
		} else {
			$a_slot = $a_entry[HILRCC_FIELD_ID_TIMESLOT];
			$b_slot = $b_entry[HILRCC_FIELD_ID_TIMESLOT];
			if ($a_slot < $b_slot) {
				return -1;
			} elseif ($a_slot > $b_slot) {
				return 1;
			} else {
				$a_title = $a_entry[HILRCC_FIELD_ID_TITLE];
				$b_title = $b_entry[HILRCC_FIELD_ID_TITLE];
				if ($a_title < $b_title) {
					return -1;
				} elseif ($a_title > $b_title) {
					return 1;
				}
			}
		}
		return 0;
	}

	/* comparison function for glance view sort */
	function glance_comparator($a_entry, $b_entry)
	{
		$a_slot = $a_entry[HILRCC_FIELD_ID_TIMESLOT];
		$b_slot = $b_entry[HILRCC_FIELD_ID_TIMESLOT];
		$a_course_no = $a_entry[HILRCC_FIELD_ID_COURSE_NO];
		$b_course_no = $b_entry[HILRCC_FIELD_ID_COURSE_NO];

		if ($a_slot < $b_slot) {
			return -1;
		} elseif ($a_slot > $b_slot) {
			return 1;
		}  else {
			$result = strcmp($a_course_no, $b_course_no);
			return $result;
		}
	}
	
	function edition_sort_comparator($a_book, $b_book) {
		if (is_this_edition_only($a_book)) {
			if (is_this_edition_only($b_book)) {
				return 0;
			}
			else {
				return 1;
			}
		}
		else {
			if (!is_this_edition_only($b_book)) {
				return 0;
			}
			else {
				return -1;
			}
		}
	}

	function HILRCC_set_either_half_flag_by_id($entry_id) {
		HILRCC_set_either_half_flag(GFAPI::get_entry($entry_id));
	}
	
	function HILRCC_set_either_half_flag($entry) {
		$duration = rgar($entry, HILRCC_FIELD_ID_DURATION);
		$flex = rgar($entry, HILRCC_FIELD_ID_FLEX_HALF);
		/* once the flag is true, never set it back to false */
		if ($flex === 'true') {
			return;
		}
		else if ($duration === 'Either First or Second Half') {
			GFAPI::update_entry_field($entry['id'], HILRCC_FIELD_ID_FLEX_HALF, 'true');
		}
	}
	
	function HILRCC_remove_empty_book_rows_by_id($entry_id) {
		return HILRCC_remove_empty_book_rows(GFAPI::get_entry($entry_id));
	}
	function HILRCC_remove_empty_book_rows($entry) {
		$books_in = unserialize(rgar($entry, HILRCC_FIELD_ID_BOOKS));
		$books_out = array();
		$found_blank = false;
		
		foreach ($books_in as $book) {
			if (empty($book['Author']) and empty($book['Title'])) {
				$found_blank = true;
				continue;
			}
			array_push($books_out, $book);
		}
		
		if (!$found_blank) {
			return true;
		}
		$serialized = maybe_serialize($books_out);
		$result = GFAPI::update_entry_field($entry['id'], HILRCC_FIELD_ID_BOOKS, $serialized);
		return $result;
	}

	/* return true if the passed step is a User Input step */
	function HILRCC_is_UI_step($step)
	{
		$step_type = $step->get_type();
		return $step_type == 'user_input';
	}
	/* return true if the passed step is a User Input step */
	function HILRCC_is_approval_step($step)
	{
		$step_type = $step->get_type();
		return $step_type == 'approval';
	}
	/* computed fields are based on the values of other fields */
	function HILRCC_update_computed_fields($entry_id)
	{
		HILRCC_update_readings($entry_id);
		HILRCC_auto_bold_sgl_names($entry_id);
		HILRCC_update_time_summary($entry_id);
		HILRCC_update_workload_string($entry_id);
		HILRCC_compress_spaces($entry_id);
		HILRCC_remove_empty_book_rows_by_id($entry_id);
		HILRCC_set_either_half_flag_by_id($entry_id);
	}
	/* update the Readings string for the catalog based on the Books field */
	define("SPACE_SPAN", "&nbsp;");
	function HILRCC_update_readings($entry_id)
	{
		
		$books = unserialize(rgar(GFAPI::get_entry($entry_id), HILRCC_FIELD_ID_BOOKS));
		$readingString = "";
		if (!empty($books)) {
			$readingString = "<strong>Readings: </strong>";
		} else {
			return;
		}
		
		usort($books, edition_sort_comparator);
		
		/* Check for special case: multiple books all with 'This edition only'.
		 * In this case, we emit 'These editions only:'
		 */
		 
		$isSpecialTheseCase = true;
		if (count($books) <= 1) {
			$isSpecialTheseCase = false;
		}
		else {
			foreach ($books as &$book) {
				if (!is_this_edition_only($book)) {
					$isSpecialTheseCase = false;
					break;
				}
			}
		}
		
		/* now generate the string */
		$isFirst = true;
		foreach ($books as &$book) {
			if (!$isFirst) {
				$readingString .= "; ";
			}
			if ($isFirst and $isSpecialTheseCase) {
				$readingString .= "Only these editions: ";
			}
			$isFirst = false;
			
			if (!$isSpecialTheseCase) {
				if (is_this_edition_only($book)) {
					$readingString = $readingString . "This edition only: ";
				}
			}
			$author = $book[HILRCC_LABEL_AUTHOR] . ", ";
			$title  = "<em>" . $book[HILRCC_LABEL_TITLE] . "</em>";
			$pubed  = SPACE_SPAN . "(" . $book[HILRCC_LABEL_PUBLISHER] . ", " . $book[HILRCC_LABEL_EDITION] . ")";
			
			$readingString = $readingString . $author . $title . $pubed;
		}
		
		if (!empty($readingString)) {
			$readingString = $readingString . ".";
		}
		
		$result = GFAPI::update_entry_field($entry_id, HILRCC_FIELD_ID_READINGS_STRING, $readingString);
		return $result;
	}

	function is_this_edition_only($book) {
		$edOnly  = strtoupper($book[HILRCC_LABEL_ONLY_ED]);
		if ((strpos($edOnly, 'Y') !== false) or (strpos($edOnly, 'X') !== false)) {
			return true;
		}
		return false;
	}

	/* add <strong> tags around SGL names in bios */
	function HILRCC_auto_bold_sgl_names($entry_id)
	{
		$entry = GFAPI::get_entry($entry_id);
		
		$sgl      = rgar($entry, HILRCC_FIELD_ID_SGL1_FIRST) . " " . rgar($entry, HILRCC_FIELD_ID_SGL1_LAST);
		$bold_sgl = HILRCC_TAG_BOLD_OPEN . $sgl . HILRCC_TAG_BOLD_CLOSE;
		$new_bio  = HILRCC_replace_if_not_present(rgar($entry, HILRCC_FIELD_ID_SGL1_BIO), $sgl, $bold_sgl);
		GFAPI::update_entry_field($entry_id, HILRCC_FIELD_ID_SGL1_BIO, $new_bio);
		
		$sgl      = rgar($entry, HILRCC_FIELD_ID_SGL2_FIRST) . " " . rgar($entry, HILRCC_FIELD_ID_SGL2_LAST);
		$bold_sgl = HILRCC_TAG_BOLD_OPEN . $sgl . HILRCC_TAG_BOLD_CLOSE;
		$new_bio = HILRCC_replace_if_not_present(rgar($entry, HILRCC_FIELD_ID_SGL2_BIO), $sgl, $bold_sgl);
		GFAPI::update_entry_field($entry_id, HILRCC_FIELD_ID_SGL2_BIO, $new_bio);
	}

	function HILRCC_replace_if_not_present($text, $old, $new)
	{
		if (strpos($text, $new) === false) {
			$text = str_replace($old, $new, $text);
		}
		return $text;
	}

	function HILRCC_update_time_summary($entry_id)
	{
		$entry = GFAPI::get_entry($entry_id);
		$val = "";
		$choices = array(HILRCC_FIELD_ID_CHOICE_1, HILRCC_FIELD_ID_CHOICE_2, HILRCC_FIELD_ID_CHOICE_3);
		$i = 0;
		foreach ($choices as $id) {
			$i += 1;
			$choice = $entry[$id];
			if (!empty($choice)) {
				$val .= substr($choice, 0, 3) . " ";
				if (strpos($choice, 'AM') !== false) {
					$val .= 'AM';
				}
				else {
					$val .= 'PM';
				}
				if ($i !== 3) {
					$val .= '-';
				}
			}
		}
		GFAPI::update_entry_field($entry_id, HILRCC_FIELD_ID_TIME_PREFERENCE, $val);
	}

	function HILRCC_update_workload_string($entry_id)
	{
		$entry = GFAPI::get_entry($entry_id);
		$workload = rgar($entry, HILRCC_FIELD_ID_WORKLOAD);
		$limit = rgar($entry,HILRCC_FIELD_ID_CLASS_SIZE);
		
		$val = "";
		if (!empty($workload) and ($workload != '0') and (intval($workload) > 0)) {
			$n = intval($workload);
			$f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
			if ($n < 10) {
				$workload = $f->format($n);
			}

			if ($n == 1) {
			    $val = "Estimated outside work is one hour per week. ";
			}
			else {
			    $val = "Estimated outside work is $workload hours per week. ";
			}
				
		}
		$val .= "Class size is limited to " . $limit . ".";
		
		GFAPI::update_entry_field($entry_id, HILRCC_FIELD_ID_COURSE_INFO_STRING, $val);
	}

	function HILRCC_compress_spaces_in_field($entry_id, $field_id)
	{
		$entry = GFAPI::get_entry($entry_id);
		$field_val = rgar($entry, $field_id);
		if (!empty($field_val)) {
			$targets = array("\x20\x20","\xc2\xa0\x20");
			$num_replaced = 0;
			$new_val = str_replace($targets, " ", $field_val, $num_replaced); /* replace 2 spaces with 1 */
			if ($num_replaced !== 0) {
				GFAPI::update_entry_field($entry_id, $field_id, $new_val);
			}
		}
	}

	function HILRCC_compress_spaces($entry_id)
	{
		HILRCC_compress_spaces_in_field($entry_id, HILRCC_FIELD_ID_TITLE);
		HILRCC_compress_spaces_in_field($entry_id, HILRCC_FIELD_ID_COURSE_DESC);
		HILRCC_compress_spaces_in_field($entry_id, HILRCC_FIELD_ID_SGL1_BIO);
		HILRCC_compress_spaces_in_field($entry_id, HILRCC_FIELD_ID_SGL2_BIO);
		HILRCC_compress_spaces_in_field($entry_id, HILRCC_FIELD_ID_OTHER_MAT);
	}
	
	function is_entry_assigned_current_user($entry)
	{
		$flow_api  = new Gravity_Flow_API(HILRCC_PROPOSAL_FORM_ID);
		$user = wp_get_current_user();
		$user_id = $user->ID;
		$current_step = $entry['workflow_step'];
		$step = $flow_api->get_step($current_step, $entry);
		if (empty($step)) {
			return false;
		}

		$assignees = $step->get_assignees();
		foreach ($assignees as $ass) {
			if ($ass->get_type() == 'user_id') {
				if ($ass->get_id() == $user_id) {
					return true;
				}
			}
			elseif ($ass->get_type() == 'role') {
				if (in_array( $ass->get_id(), (array) $user->roles ) ) {
					$step_status = $step->get_role_status($ass->get_id());
					return ($step_status != 'complete');
				}
			}
		}
		return false;
	}

	function get_query_string_param($param) {
		parse_str($_SERVER['QUERY_STRING'], $output);
		return $output[$param];
	}
	
	function HILRCC_update_last_mod_time($entry) {
		date_default_timezone_set(HILRCC_DEFAULT_TIMEZONE); 
		$dt = new DateTime();
		$stamp = "" . date_timestamp_get($dt) . "000";
		GFAPI::update_entry_field($entry['id'], HILRCC_FIELD_ID_LAST_MOD_TIME, $stamp);
	}
	
	/*
	 * When the form is submitted, remove some tags from rich text fields that 
	 * appear in the catalog.
	 */
	function HILRCC_strip_tags($entry) {
		$std_allowed_tags = "<b><i><em><strong>";
		HILRCC_strip_tags_from_field($entry, HILRCC_FIELD_ID_COURSE_DESC, $std_allowed_tags); 
		HILRCC_strip_tags_from_field($entry, HILRCC_FIELD_ID_SGL1_BIO, $std_allowed_tags); 
		HILRCC_strip_tags_from_field($entry, HILRCC_FIELD_ID_SGL2_BIO, $std_allowed_tags); 
		HILRCC_strip_tags_from_field($entry, HILRCC_FIELD_ID_OTHER_MAT, $std_allowed_tags); 
	}

	function HILRCC_strip_tags_from_field($entry, $field_id, $allowed) {
		$richtext = rgar($entry, $field_id);
		if (!empty($richtext)) {
			$stripped = strip_tags($richtext, $allowed);
			GFAPI::update_entry_field($entry['id'], $field_id, $stripped);
		}
	}

?>