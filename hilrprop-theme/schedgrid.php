<?php
# HILR Course Proposals app (server side).
# Copyright (c) 2018 HILR
# Author: Frederick Hewett
#
# This module serves the scheduling grid page
#
add_action('wp_ajax_get_sched_grid_data', 'HILRCC_fetch_sched_grid');
function HILRCC_fetch_sched_grid() 
{
/*
	return object (assoc array) structure: 
	
	{
		"MonAM-1-G20" : ["title 1", "title 2"],
		"MonAM-1-120" : [""],
		"MonAM-1-207" : ["title 3"]
		...
	}
*/
	$semester = stripslashes_deep($_POST["semester"]);
	$search = array();
	$search['form_id'] = HILRCC_PROPOSAL_FORM_ID;
	$search['status'] = 'active';
	$search['field_filters'] = array();
	$search['field_filters'][] = array('key'=>HILRCC_FIELD_ID_SEMESTER, 'operator'=>'is', 'value'=>$semester);
	$search['field_filters'][] = array('key'=>HILRCC_FIELD_ID_STATUS, 'operator'=>'is', 'value'=>'Approved');
	
	try {
		$entries = GFAPI::get_entries(0, $search, null, array( 'offset' => 0, 'page_size' => 1000));
	}
	catch (Exception $e) {
		return 'get_entries, exception: ' . $e->getMessage() . "\n";
	}
	$result = array();
	
	foreach ($entries as &$entry) {
		$slot = rgar($entry, HILRCC_FIELD_ID_TIMESLOT);
		$room = rgar($entry, HILRCC_FIELD_ID_ROOM);
		$term = rgar($entry, HILRCC_FIELD_ID_DURATION);
		$title = rgar($entry, HILRCC_FIELD_ID_TITLE);
		
/* debug code
		array_push($result, ["slot"=>$slot, "room"=>$room, "term"=>$term, "title"=>$title]);
		continue;
*/
		if (empty($slot) or empty($term) or strcmp($term, "Either First or Second Half") == 0) {
			continue;
		}
		if (empty($room)) {
			$room = 'Unassigned';
		}
		$key;
		if (strcmp($slot, '1') == 0 or strcmp($slot, '2') == 0) {
			$key = "Mon";
		}
		if (strcmp($slot, '3') == 0 or strcmp($slot, '4') == 0) {
			$key = "Tue";
		}
		if (strcmp($slot, '5') == 0 or strcmp($slot, '6') == 0) {
			$key = "Wed";
		}
		if (strcmp($slot, '7') == 0 or strcmp($slot, '8') == 0) {
			$key = "Thu";
		}
		if (intval($slot) % 2 === 1) {
			$key .= "AM-";
		}
		else {
			$key .= "PM-";
		}
		
		if (strcmp($term, "Second Half") === 0) {
			$key .= "2-";
		}
		else {
			$key .= "1-";
		}
		$key .= $room;
		
		if (!array_key_exists($key, $result)) {
			$result[$key] = array($title);
		}
		else {
			array_push($result[$key], $title);
		}
		if (strpos($term, "Full Term") !== false) { /* includes delayed start */
			$key = preg_replace("/-1-/", "-2-", $key);
			if (!array_key_exists($key, $result)) {
				$result[$key] = array($title);
			}
			else {
				array_push($result[$key], $title);
			}
		}
	}
	echo json_encode($result);
}

add_shortcode('HILRCC_sched_grid', 'HILRCC_emit_sched_grid');
function HILRCC_emit_sched_grid() 
{
	$rooms = preg_split('/,/', 'Unassigned,' . HILRCC_ROOMS);    

	$output=<<<OUT1

<div id="sched_grid_0">
  <ul class="hilrcc-no-print">
    <li><a href="#sched_grid_1">First Half</a></li>
    <li><a href="#sched_grid_2">Second Half</a></li>
  </ul>

<h3 class="print-only-block">First Half</h3>
<table id='sched_grid_1'>
	<tbody>
		<tr>
			<th></th><th>Mon AM</th><th>Mon PM</th><th>Tue AM</th><th>Tue PM</th><th>Wed AM</th><th>Wed PM</th><th>Thu AM</th><th>Thu PM</th>
		</tr>
OUT1;

	foreach($rooms as $room) {
		$output .= "<tr>";
		$output .= "<td class='hilr-room-name'>$room</td>";
		$output .= "<td id='MonAM-1-$room'></td>";
		$output .= "<td id='MonPM-1-$room'></td>";
		$output .= "<td id='TueAM-1-$room'></td>";
		$output .= "<td id='TuePM-1-$room'></td>";
		$output .= "<td id='WedAM-1-$room'></td>";
		$output .= "<td id='WedPM-1-$room'></td>";
		$output .= "<td id='ThuAM-1-$room'></td>";
		$output .= "<td id='ThuPM-1-$room'></td>";
		$output .= "</tr>";
	}
	$output .= <<<OUT2
	</tbody>
</table>
<h3 class="print-only-block" style="page-break-before: always;">Second Half</h3>
<table id='sched_grid_2'>
	<tbody>
		<tr>
			<th></th><th>Mon AM</th><th>Mon PM</th><th>Tue AM</th><th>Tue PM</th><th>Wed AM</th><th>Wed PM</th><th>Thu AM</th><th>Thu PM</th>
		</tr>
OUT2;		

	foreach($rooms as $room) {
		$output .= "<tr>";
		$output .= "<td class='hilr-room-name'>$room</td>";
		$output .= "<td id='MonAM-2-$room'></td>";
		$output .= "<td id='MonPM-2-$room'></td>";
		$output .= "<td id='TueAM-2-$room'></td>";
		$output .= "<td id='TuePM-2-$room'></td>";
		$output .= "<td id='WedAM-2-$room'></td>";
		$output .= "<td id='WedPM-2-$room'></td>";
		$output .= "<td id='ThuAM-2-$room'></td>";
		$output .= "<td id='ThuPM-2-$room'></td>";
		$output .= "</tr>";
	}
	$output .= <<<OUT3
	</tbody>
</table>
</div>
OUT3;
	return $output;
}
?>