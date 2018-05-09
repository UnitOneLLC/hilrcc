<?php

define('HILRCC_PROPOSAL_FORM_ID', '2');

define('HILRCC_VIEW_ID_CATALOG', '201');
define('HILRCC_VIEW_ID_GLANCE', '426');
define('HILRCC_VIEW_ID_INBOX', '415');

define('HILRCC_FIELD_ID_TITLE', '1');
define('HILRCC_FIELD_ID_DURATION', '3');
define('HILRCC_FIELD_ID_BOOKS', '14');
define('HILRCC_FIELD_ID_SGL1_BIO', '18');
define('HILRCC_FIELD_ID_SGL2_BIO', '19');
define('HILRCC_FIELD_ID_CLASS_SIZE', '27');
define('HILRCC_FIELD_ID_DISCUSSION', '36');
define('HILRCC_FIELD_ID_STATUS', '37');
define('HILRCC_FIELD_ID_CHOICE_1', '38');
define('HILRCC_FIELD_ID_CHOICE_3', '39');
define('HILRCC_FIELD_ID_CHOICE_2', '40');
define('HILRCC_FIELD_ID_COURSE_NO', '42');
define('HILRCC_FIELD_ID_PHONE_1', '46');
define('HILRCC_FIELD_ID_PHONE_2', '50');
define('HILRCC_FIELD_ID_SGL1_EMAIL', '52');
define('HILRCC_FIELD_ID_SEMESTER', '54');
define('HILRCC_FIELD_ID_TIMESLOT', '55');
define('HILRCC_FIELD_ID_SGL1_FIRST', '56.3');
define('HILRCC_FIELD_ID_SGL1_LAST', '56.6');
define('HILRCC_FIELD_ID_SGL2_FIRST', '57.3');
define('HILRCC_FIELD_ID_SGL2_LAST', '57.6');
define('HILRCC_FIELD_ID_WORKLOAD', '58');
define('HILRCC_FIELD_ID_WEBSITE', '60.1');
define('HILRCC_FIELD_ID_READINGS_STRING', '64');
define('HILRCC_FIELD_ID_SUPPRESS_NOTIFY', '66');
define('HILRCC_FIELD_ID_TIME_PREFERENCE', '67');
define('HILRCC_FIELD_ID_COURSE_INFO_STRING', '68');

define('HILRCC_STEP_ID_INITIALIZATION', '1');
define('HILRCC_STEP_ID_REVIEW_NOTIFICATION', '3');
define('HILRCC_STEP_ID_TABLING', '6');
define('HILRCC_STEP_ID_NOTIFY_CHANGES', '17');
define('HILRCC_STEP_ID_REV_BY_COMM', '13');
define('HILRCC_STEP_ID_MOD_BY_SPONSOR', '2');
define('HILRCC_STEP_ID_POST_REV_MOD', '14');
define('HILRCC_STEP_ID_POST_REV_ROUT', '15');
define('HILRCC_STEP_ID_VOTING', '8');
define('HILRCC_STEP_ID_FINAL_SPONSOR_REVIEW', '16');
define('HILRCC_STEP_ID_PRE_PUB', '10');
define('HILRCC_STEP_ID_PUB', '11');

define('HILRCC_LABEL_AUTHOR', 'Author (first last)');
define('HILRCC_LABEL_TITLE', 'Title');
define('HILRCC_LABEL_PUBLISHER', 'Publisher');
define('HILRCC_LABEL_EDITION', 'Edition and Date');
define('HILRCC_LABEL_ONLY_ED', 'Only this edition (Y/N)');
define('HILRCC_TAG_BOLD_OPEN', '<strong>');
define('HILRCC_TAG_BOLD_CLOSE', '</strong>');

$workflow_button_labels_map = array(
	HILRCC_STEP_ID_INITIALIZATION => array("SUBMIT"=>"Submit", "SAVE"=>"Save"),
	HILRCC_STEP_ID_TABLING => array("SUBMIT"=>"Submit", "SAVE"=>"Save"),
	HILRCC_STEP_ID_REV_BY_COMM => array("APPROVE"=>"Ready to Vote", "REJECT"=>"Needs Discussion", "REVERT"=>"Edit"),
	HILRCC_STEP_ID_MOD_BY_SPONSOR => array("SUBMIT"=>"Submit", "SAVE"=>"Save"),
	HILRCC_STEP_ID_POST_REV_MOD => array("SUBMIT"=>"Submit", "SAVE"=>"Save"),
	HILRCC_STEP_ID_POST_REV_ROUT => array("APPROVE"=>"Approve", "REJECT"=>"Redject", "REVERT"=>"Revert"),
	HILRCC_STEP_ID_VOTING => array("APPROVE"=>"Approve", "REJECT"=>"Redject", "REVERT"=>"Revert"),
	HILRCC_STEP_ID_FINAL_SPONSOR_REVIEW => array("SUBMIT"=>"Submit", "SAVE"=>"Save"),
	HILRCC_STEP_ID_PRE_PUB => array("SUBMIT"=>"Submit", "SAVE"=>"Save"),
	HILRCC_STEP_ID_PUB => array("SUBMIT"=>"Submit", "SAVE"=>"Save")
);

function HILRCC_enqueue_styles()
{
    
    /* $parent_style = 'twentyseventeen-style'; */
    $parent_style = 'gravityflow_status';
    
    wp_enqueue_style($parent_style, get_template_directory_uri() . '/style.css', array(), '0.9.0');
    
    wp_register_script('hilrpropjs', get_stylesheet_directory_uri() . '/hilrprop.js', array(
        'jquery'
    ));
    wp_enqueue_script('hilrpropjs');

	wp_localize_script('hilrpropjs', 'HILRCC_stringTable', array(
		'siteURL' => site_url() . "/index.php/",
        'ajaxURL' => admin_url('admin-ajax.php'),
		'suppress_input' => 'input_' . HILRCC_FIELD_ID_SUPPRESS_NOTIFY,
		'sgl1_email_name' => 'input_' . HILRCC_FIELD_ID_SGL1_EMAIL,
		'formId' => HILRCC_PROPOSAL_FORM_ID,
		'slot_cell_class' => "gv-field-" . HILRCC_PROPOSAL_FORM_ID . "-" . HILRCC_FIELD_ID_TIMESLOT
	));        
	
/*    
    wp_localize_script('hilrpropjs', 'HILRCC_ajax_map', array(
        'ajaxURL' => admin_url('admin-ajax.php')
    ));
    
    wp_localize_script('hilrpropjs', 'HILRCC_suppress_input', 'input_' . HILRCC_FIELD_ID_SUPPRESS_NOTIFY);
*/
}
add_action('wp_enqueue_scripts', 'HILRCC_enqueue_styles');

/* Function to create a shortcode for the URL of the Inbox page */
function HILRCC_show_inbox_url($atts)
{
    
    $formid       = 0;
    $entryid      = 0;
    $have_formid  = false;
    $have_entryid = false;
    
    if (is_array($atts) and array_key_exists('formid', $atts)) {
        $formid      = $atts['formid'];
        $have_formid = ($formid != 0);
    }
    
    if (is_array($atts) and array_key_exists('entryid', $atts)) {
        $entryid      = $atts['entryid'];
        $have_entryid = ($entryid != 0);
    }
    if (have_formid === false) {
        return esc_url(site_url() . '/index.php/inbox/');
    }
    if (have_entryid === false) {
        return site_url() . '/index.php/inbox/?page=gravityflow-inbox&view=entry&id=' . $formid;
    }
    
    return site_url() . '/index.php/inbox/?page=gravityflow-inbox&view=entry&id=' . $formid . '&lid=' . $entryid;
}
add_shortcode('HILRCC_inbox_url', 'HILRCC_show_inbox_url');

/* Function to create a shortcode for the URL of the Proposal View page */
function HILRCC_show_proposal_view_url($atts)
{
    $entryid      = 0;
    $have_entryid = false;
    if (is_array($atts) and array_key_exists('entryid', $atts)) {
        $entryid      = $atts['entryid'];
        $have_entryid = ($entryid != 0);
    }
    
    if ($have_entryid) {
        return site_url() . '/index.php/proposal-view/entry/' . $entryid . '/';
    }
    return site_url() . '/index.php/proposal-view';
}
add_shortcode('HILRCC_proposal_view_url', 'HILRCC_show_proposal_view_url');

/* Function to create a shortcode for the URL of the Voting Review page */
function HILRCC_show_voting_review_url($atts)
{
    $entryid      = 0;
    $have_entryid = false;
    if (is_array($atts) and array_key_exists('entryid', $atts)) {
        $entryid      = $atts['entryid'];
        $have_entryid = ($entryid != 0);
    }
    
    if ($have_entryid) {
        return site_url() . '/index.php/voting-review/entry/' . $entryid;
    }
    return site_url() . '/index.php/voting-review';
}
add_shortcode('HILRCC_voting_review_url', 'HILRCC_show_voting_review_url');


/**
 * Bypass Force Login for course proposal form
 *
 * @return bool Whether to disable Force Login. Default false.
 */
function my_forcelogin_bypass($bypass)
{
    $url         = $_SERVER['REQUEST_URI'];
    $is_form_url = is_numeric(strpos($url, '/course-proposal-form'));
    return ($is_form_url or is_front_page());
}
add_filter('v_forcelogin_bypass', 'my_forcelogin_bypass', 10, 1);

/* default to inbox after login */
function default_to_inbox($redirect_to)
{
    
    if (strcmp($redirect_to, site_url("wp-admin/")) === 0) {
        return '/index.php/inbox-view/';
    }
    return $redirect_to;
}
add_filter('login_redirect', 'default_to_inbox');

/**
 * Hide the nav menu on the homempage
 */
add_filter('pre_wp_nav_menu', 'HILRCC_hide_menu_on_homepage');
function HILRCC_hide_menu_on_homepage($menu)
{
    if (is_home() or is_front_page()) {
        $menu = '';
    }
    return $menu;
}

/* 
 * ajax handler to re-number the courses 
 */
add_action('wp_ajax_renumber_courses', 'renumber_courses');
function renumber_courses() {
	$start = stripslashes_deep($_POST["start"]);
	$startNumber = intval($start);
	$semester = stripslashes_deep($_POST["semester"]);
	$search = array();
	$search[HILRCC_FIELD_ID_SEMESTER] = $semester;
	$search['form_id'] = HILRCC_PROPOSAL_FORM_ID;
	/** THERE MAY BE ADDITIONAL SEARCH CRITERIA **/
		
	$entries = GFAPI::get_entries(0, $search, null);
	
	if (is_wp_error($entries)) {
		echo $entries.get_error_message($entries.get_error_code());
	}
	else {
	    usort($entries, catalog_comparator);
		$number = $startNumber;
		$roundedFor1stHalf = false;
		$roundedFor2ndHalf = false;
		
		foreach ($entries as &$entry) {
			if (!$roundedFor1stHalf) {
				$term = $entry[HILRCC_FIELD_ID_DURATION];
				if ($term == "First Half") {
					$roundedFor1stHalf = true;
					$number = (intval($number/10) + 1) * 10;
				}
			}
			if (!$roundedFor2ndHalf) {
				$term = $entry[HILRCC_FIELD_ID_DURATION];
				if ($term == "Second Half") {
					$roundedFor2ndHalf = true;
					$number = (intval($number/10) + 1) * 10;
				}
			}
			GFAPI::update_entry_field($entry['id'], HILRCC_FIELD_ID_COURSE_NO, $number);
			$number = $number + 1;
		}
		echo "SUCCESS";	
	}
}

/*
 * ajax handler to clear all course numbers
 */
add_action('wp_ajax_clear_course_numbers', 'clear_course_numbers');
function clear_course_numbers() {
	$semester = stripslashes_deep($_POST["semester"]);
	$search = array();
	$search[HILRCC_FIELD_ID_SEMESTER] = $semester;
	/** THERE MAY BE ADDITIONAL SEARCH CRITERIA **/
	
	$entries = GFAPI::get_entries(0, $search, null);
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
add_action('wp_ajax_add_comment', 'add_comment');
function add_comment()
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
    
    $form      = GFAPI::get_form(HILRCC_PROPOSAL_FORM_ID);
    $discfield = GFFormsModel::get_field($form, HILRCC_FIELD_ID_DISCUSSION);
    $discvalue = $discfield->get_value_save_entry($comment, $form, $input_name = '', $entry_id, $entry);
    $result    = GFAPI::update_entry_field($entry_id, HILRCC_FIELD_ID_DISCUSSION, $discvalue);
    
    if ($result) {
        echo ("SUCCESS");
    } else {
        echo ("FAIL: GFAPI");
    }
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
		
	$entries = GFAPI::get_entries(0, $search, null);
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
	$timeslot = $_POST["timeslot"];
	
	$lookup = array(
				  "Monday AM" => 1,
				  "Monday PM" => 2,
				  "Tuesday AM" => 3,
				  "Tuesday PM" => 4,
				  "Wednesday AM" => 5,
				  "Wednesday PM" => 6,
				  "Thursday AM" => 7,
				  "Thursday PM" => 8);

	if (array_key_exists($timeslot, $lookup)) {
		
	    $result   = GFAPI::update_entry_field($entry_id, HILRCC_FIELD_ID_TIMESLOT, $lookup[$timeslot]) ;	
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

/**
 * Validate fields, including phone field on submit
 *
 * @return array('is_valid'=>bool, 'message'=>string)
 */
add_filter( 'gform_field_validation', 'HILRCC_validate_field', 10, 4 );
function HILRCC_validate_field($result, $value, $form, $field)
{
    if (($field->adminLabel == 'sgl_1_phone') or ($field->adminLabel == 'sgl_2_phone')) {
        if (!HILRCC_validate_phone($value)) {
            $result['is_valid'] = false;
            $result['message']  = $value . ' is not a valid phone number';
        }
    }
    return $result;
}


add_filter('gform_validation', 'HILRCC_custom_validation');
function HILRCC_custom_validation($validation_result)
{
    $form   = $validation_result['form'];
    $c1     = rgpost('input_' . HILRCC_FIELD_ID_CHOICE_1);
    $c2     = rgpost('input_' . HILRCC_FIELD_ID_CHOICE_2);
    $c3     = rgpost('input_' . HILRCC_FIELD_ID_CHOICE_3);
    $phone1 = rgpost('input_' . HILRCC_FIELD_ID_PHONE_1);
    $phone2 = rgpost('input_' . HILRCC_FIELD_ID_PHONE_2);
    
    if ($c1 != $c2) {
        $c2_ok = true;
    } else {
        $c2_ok = false;
    }
    if (($c1 != $c3) and ($c2 != $c3)) {
        $c3_ok = true;
    } else {
        $c3_ok = false;
    }
    $phone1_ok = HILRCC_validate_phone($phone1);
    if (($phone2 != NULL) and ($phone2 != '')) {
        $phone2_ok = HILRCC_validate_phone($phone2);
    } else {
        $phone2_ok = true;
    }
    
    if ($c2_ok and $c3_ok and $phone1_ok and $phone2_ok) {
        $validation_result['is_valid'] = true;
        return $validation_result;
    }
    
    // set the form validation to false
    $validation_result['is_valid'] = false;
    
    //finding Field with ID of 1 and marking it as failed validation
    foreach ($form['fields'] as &$field) {
        
        if (($field->id == HILRCC_FIELD_ID_CHOICE_2) and (!$c2_ok)) {
            $field->failed_validation  = true;
            $field->validation_message = 'Second choice cannot be the same as first.';
        }
        
        if (($field->id == HILRCC_FIELD_ID_CHOICE_3) and (!$c3_ok)) {
            $field->failed_validation  = true;
            $field->validation_message = 'Third choice cannot be the same as first or second.';
        }
        
        if (($field->id == HILRCC_FIELD_ID_PHONE_1) and (!$phone1_ok)) {
            $field->failed_validation  = true;
            $field->validation_message = 'Please enter a valid phone number.';
        }
        
        if (($field->id == HILRCC_FIELD_ID_PHONE_2) and (!$phone2_ok)) {
            $field->failed_validation  = true;
            $field->validation_message = 'Please enter a valid phone number.';
        }
    }
    
    //Assign modified $form object back to the validation result
    $validation_result['form'] = $form;
    return $validation_result;
}

function HILRCC_validate_phone($numberString)
{
    /*
    regex for North American phone number from
    https://stackoverflow.com/questions/3357675/validating-us-phone-number-with-php-regex
    */
    $sPattern = "/^
        (?:                                 # Area Code
            (?:                            
                \(                          # Open Parentheses
                (?=\d{3}\))                 # Lookahead.  Only if we have 3 digits and a closing parentheses
            )?
            (\d{3})                         # 3 Digit area code
            (?:
                (?<=\(\d{3})                # Closing Parentheses.  Lookbehind.
                \)                          # Only if we have an open parentheses and 3 digits
            )?
            [\s.\/-]?                       # Optional Space Delimeter
        )?
        (\d{3})                             # 3 Digits
        [\s\.\/-]?                          # Optional Space Delimeter
        (\d{4})\s?                          # 4 Digits and an Optional following Space
        (?:                                 # Extension
            (?:                             # Lets look for some variation of 'extension'
                (?:
                    (?:e|x|ex|ext)\.?       # First, abbreviations, with an optional following period
                |
                    extension               # Now just the whole word
                )
                \s?                         # Optionsal Following Space
            )
            (?=\d+)                         # This is the Lookahead.  Only accept that previous section IF it's followed by some digits.
            (\d+)                           # Now grab the actual digits (the lookahead doesn't grab them)
        )?                                  # The Extension is Optional
        $/x"; // /x modifier allows the expanded and commented regex
    
    return (preg_match($sPattern, $numberString) == 1);
}

/* change the Approve button in the Review by Committee step */
add_filter('gravityflow_approve_label_workflow_detail', 'filter_approve_label_workflow_detail', 10, 2);
function filter_approve_label_workflow_detail($approve_label, $step)
{
	global $workflow_button_labels_map;

	return $workflow_button_labels_map[strval($step->get_id())]["APPROVE"];
}

add_filter('gravityflow_revert_label_workflow_detail', 'filter_revert_label_workflow_detail', 10, 2);
function filter_revert_label_workflow_detail($approve_label, $step)
{
	global $workflow_button_labels_map;

	return $workflow_button_labels_map[strval($step->get_id())]["REVERT"];
}

add_filter('gravityflow_reject_label_workflow_detail', 'filter_reject_label_workflow_detail', 10, 2);
function filter_reject_label_workflow_detail($reject_label, $step)
{
	global $workflow_button_labels_map;

	return $workflow_button_labels_map[strval($step->get_id())]["REJECT"];
}

add_filter( 'gravityflow_update_button_text_user_input', 'filter_submit_label_workflow_detail' );
function filter_submit_label_workflow_detail( $text ) {
	global $workflow_button_labels_map;
	
	$entry_id = get_query_string_param("lid");
	if (!empty($entry_id)) {
		$entry = GFAPI::get_entry( $entry_id );
		$flow_api  = new Gravity_Flow_API(HILRCC_PROPOSAL_FORM_ID);
		$step = $flow_api->get_current_step($entry);
	
		return $workflow_button_labels_map[strval($step->get_id())]["SUBMIT"];
	}
	else {
		return "Submit";
	}

}

function get_query_string_param($param) {
	parse_str($_SERVER['QUERY_STRING'], $output);
	return $output[$param];
}



/* login page customization */
function my_login_logo()
{
?>
    <style type="text/css">
        #login h1 a, .login h1 a {
            background-image: url(<?php
    echo get_stylesheet_directory_uri();
?>/images/hilr-shield.png);
		height:65px;
		width:320px;
		background-size: 80px 92px;
		background-repeat: no-repeat;
        	padding-bottom: 30px;
        }
    </style>
<?php
}
add_action('login_enqueue_scripts', 'my_login_logo');

/* In GravityView, display the label of a dropdown field value instead of its sorting value */
add_filter('gravityview/fields/select/output_label', '__return_true');

/* Attach filter to GravityView get_entries to enable custom sort for catalog view */
add_filter('gravityview_entries', 'HILRCC_custom_view_entries');
function HILRCC_custom_view_entries($entries)
{
    $view_id = GravityView_View::getInstance()->getViewId();
    if ($view_id == HILRCC_VIEW_ID_CATALOG) {
        try {
            usort($entries, catalog_comparator);
        }
        catch (Exception $e) {
            echo 'Catalog sorting error:: ', $e->getMessage(), "\n";
        }
    }
    else if ($view_id == HILRCC_VIEW_ID_GLANCE) {
        try {
            usort($entries, glance_comparator);
        }
        catch (Exception $e) {
            echo 'Catalog sorting error:: ', $e->getMessage(), "\n";
        }
    }
    else if ($view_id == HILRCC_VIEW_ID_INBOX) {
    
    	$in_inbox = array();
    	$user = wp_get_current_user();
    	$user_id = $user->ID;
    	foreach ($entries as &$entry) {
    		if (is_entry_assigned_current_user($entry)) {
    			array_push($in_inbox, $entry);
    		}
		}
		return $in_inbox;
    }
    
    return $entries;
}

function is_entry_assigned_current_user($entry) 
{
	$flow_api  = new Gravity_Flow_API(HILRCC_PROPOSAL_FORM_ID);
	$user = wp_get_current_user();
	$user_id = $user->ID;
	$current_step = $entry['workflow_step'];
	$step = $flow_api->get_step($current_step, $entry);

	$assignees = $step->get_assignees();
	foreach ($assignees as $ass) {
		if ($ass->get_type() == 'user_id') {
			if ($ass->get_id() == $user_id) {
				return true;
			}
		}
		elseif ($ass->get_type() == 'role') {
			if ( in_array( $ass->get_id(), (array) $user->roles ) ) {
				return true;
			}
		}
	}
	return false;
}

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

/** remove "Howdy" (https://premium.wpmudev.org/blog/add-remove-from-wordpress/) **/
add_filter('gettext', 'change_howdy', 10, 3);
function change_howdy($translated, $text, $domain)
{
    
    #    if (!is_admin() || 'default' != $domain)
    #        return $translated;
    
    if (false !== strpos($translated, 'Howdy'))
        return str_replace('Howdy', 'Welcome', $translated);
    
    return $translated;
}


/* adjust the status field of the form when completing the Review by Committee step (id=13) */
add_filter('gravityflow_step_complete', 'HILRCC_gravityflow_step_complete', 10, 4);
function HILRCC_gravityflow_step_complete($step_id, $entry_id, $form_id, $status)
{
    if ($step_id == HILRCC_STEP_ID_REV_BY_COMM) {
        $newFieldValue = NULL;
        $formStatus    = $_POST['gravityflow_approval_new_status_step_' . HILRCC_STEP_ID_REV_BY_COMM];
        if ($formStatus == 'rejected') {
            $newFieldValue = 'Under discussion';
        } else if ($formStatus == 'approved') {
            $newFieldValue = 'Active';
        }
        if ($newFieldValue != NULL) {
            $result = GFAPI::update_entry_field($entry_id, HILRCC_FIELD_ID_STATUS, $newFieldValue);
        }
    }
    /* for UI steps, update automatic fields */
    $api  = new Gravity_Flow_API($form_id);
    $step = $api->get_current_step(GFAPI::get_entry($entry_id));
    if (HILRCC_is_UI_step($step)) {
        HILRCC_update_automatic_fields($entry_id);
    }
}
/* return true if the passed step is a User Input step */
function HILRCC_is_UI_step($step)
{
    $step_type = $step->get_type();
    return $step_type == 'user_input';
}
/* synthetic fields are based on the values of other fields */
function HILRCC_update_automatic_fields($entry_id)
{
    HILRCC_update_readings($entry_id);
    HILRCC_auto_bold_sgl_names($entry_id);
    HILRCC_update_time_summary($entry_id);
    HILRCC_update_workload_string($entry_id);
}
/* update the Readings string for the catalog based on the Books field */

function HILRCC_update_readings($entry_id)
{
    
    $books         = unserialize(rgar(GFAPI::get_entry($entry_id), HILRCC_FIELD_ID_BOOKS));
    $readingString = "";
    if (!empty($books)) {
        $readingString = "<strong>Readings: </strong>";
    } else {
        return;
    }
    
    $isFirst = true;
    foreach ($books as $book) {
        if (!$isFirst) {
            $readingString = $readingString . "; ";
        }
        $isFirst = false;
        $edOnly  = strtoupper($book[HILRCC_LABEL_ONLY_ED]);
        if (strpos($edOnly, 'Y') !== false) {
            $readingString = $readingString . "This edition only: ";
        }
        
        $author = $book[HILRCC_LABEL_AUTHOR] . ", ";
        $title  = "<em>" . $book[HILRCC_LABEL_TITLE] . "</em>";
        $pubed  = " (" . $book[HILRCC_LABEL_PUBLISHER] . ", " . $book[HILRCC_LABEL_EDITION] . ")";
        
        $readingString = $readingString . $author . $title . $pubed;
    }
    
    if (!empty($readingString)) {
        $readingString = $readingString . ".";
    }
    
    $result = GFAPI::update_entry_field($entry_id, HILRCC_FIELD_ID_READINGS_STRING, $readingString);
    return $result;
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
    	$val .= "($i-" . substr($choice, 0, 3) . " ";
    	if (strpos($choice, 'AM') !== false) {
    		$val .= 'AM) ';
    	}
    	else {
    		$val .= 'PM) ';
    	}
    }
    GFAPI::update_entry_field($entry_id, HILRCC_FIELD_ID_TIME_PREFERENCE, $val);
}

function HILRCC_update_workload_string($entry_id)
{
    $entry = GFAPI::get_entry($entry_id);
	$workload = rgar($entry, HILRCC_FIELD_ID_WORKLOAD);
	$website = rgar($entry, HILRCC_FIELD_ID_WEBSITE);
	$limit = HILRCC_get_class_limit($entry);
	if (!empty($website)) {
		$website .= ". ";
	}
	$val = "";
	if (!empty($workload) and ($workload != '0') and (intval($workload) > 0)) {
		$n = intval($workload);
		$f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
		if ($n < 10) {
			$workload = $f->format($n);
		}	
	
	    $val = "Estimated amount of work outside class is $workload hours per week. ";
	}
    $val .= $website;
    $val .= "Class size is limited to " . $limit . ".";
    
    GFAPI::update_entry_field($entry_id, HILRCC_FIELD_ID_COURSE_INFO_STRING, $val);
}

function HILRCC_get_class_limit($entry)
{
	echo("class size=");
	var_dump(rgar($entry,HILRCC_FIELD_ID_CLASS_SIZE));
	return '20';
}

?>