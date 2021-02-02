<?php
# HILR Course Proposals app (server side).
# Copyright (c) 2018 HILR
# Author: Frederick Hewett
#
# This is a Wordpress app that uses three plug-ins (Gravity Forms,
# GravityView,and Gravity Flow) to implement the ingestion of
# course proposal forms and a workflow to process them, culminating
# in formatting for publication in the course catalog.
#
#
# Path to login logo image
define('HILRCC_LOGO_PATH', '/images/smaller_text_logo.png');
#
# Labels for workflow action buttons, per step
$workflow_button_labels_map = array(
	HILRCC_STEP_ID_SPONSOR_ASSIGNMENT => array("SUBMIT"=>"Submit", "SAVE"=>"Save"),
	HILRCC_STEP_ID_TABLING => array("SUBMIT"=>"Submit", "SAVE"=>"Save"),
	HILRCC_STEP_ID_REV_BY_COMM => array("APPROVE"=>"Ready to Vote", "REJECT"=>"Refer to Co-chairs", "REVERT"=>"Edit"),
	HILRCC_STEP_ID_MOD_BY_SPONSOR => array("SUBMIT"=>"Submit", "SAVE"=>"Save"),
	HILRCC_STEP_ID_POST_REV_MOD => array("SUBMIT"=>"Submit", "SAVE"=>"Save"),
	HILRCC_STEP_ID_POST_REV_ROUT => array("APPROVE"=>"Approve", "REJECT"=>"Reject", "REVERT"=>"Send to Sponsor"),
	HILRCC_STEP_ID_VOTING => array("APPROVE"=>"Approve", "REJECT"=>"Reject", "REVERT"=>"Send to Sponsor"),
	HILRCC_STEP_ID_FINAL_SPONSOR_REVIEW => array("SUBMIT"=>"Submit", "SAVE"=>"Save"),
	HILRCC_STEP_ID_PRE_PUB => array("SUBMIT"=>"Submit for Catalog", "SAVE"=>"Save"),
	HILRCC_STEP_ID_PUB => array("SUBMIT"=>"Done", "SAVE"=>"Update")
);

function HILRCC_enqueue_styles()
{
    /* $parent_style = 'twentyseventeen-style'; */
    $parent_style = 'gravityflow_status';
    
    wp_enqueue_style($parent_style, get_template_directory_uri() . '/style.css', array(), HILRCC_BUILD);
	wp_enqueue_script('jquery-ui-tooltip');
    wp_enqueue_script('jquery-ui-tabs');
    wp_register_script('hilrpropjs', get_stylesheet_directory_uri() . '/hilrprop.js', array(
        'jquery'
    ), HILRCC_BUILD);
    wp_enqueue_script('hilrpropjs');

	if (!is_user_logged_in())
		$role_context = 'hilr-cc-anon';
	else if (in_array('administrator', (array) wp_get_current_user()->roles))
		$role_context = 'hilr-cc-adm';
	else
		$role_context = 'hilr-cc-usr';

	wp_localize_script('hilrpropjs', 'HILRCC_stringTable', array(
		'siteURL' => site_url() . "/index.php/",
        'ajaxURL' => admin_url('admin-ajax.php'),
        'childThemeRootURL' => get_stylesheet_directory_uri(),
		'suppress_input' => 'input_' . HILRCC_FIELD_ID_SUPPRESS_NOTIFY,
		'sgl1_email_name' => 'input_' . HILRCC_FIELD_ID_SGL1_EMAIL,
		'formId' => HILRCC_PROPOSAL_FORM_ID,
		'slot_cell_class' => "gv-field-" . HILRCC_PROPOSAL_FORM_ID . "-" . HILRCC_FIELD_ID_TIMESLOT,
		'suppress_id' => "field_" . HILRCC_PROPOSAL_FORM_ID . "_" . HILRCC_FIELD_ID_SUPPRESS_NOTIFY,
		'lastmod_id' => "field_" . HILRCC_PROPOSAL_FORM_ID . "_" . HILRCC_FIELD_ID_LAST_MOD_TIME,
		'flexhalf_id' => "field_" . HILRCC_PROPOSAL_FORM_ID . "_" . HILRCC_FIELD_ID_FLEX_HALF,
		'course_desc_class' => 'gv-field-' . HILRCC_PROPOSAL_FORM_ID . "-" . HILRCC_FIELD_ID_COURSE_DESC,
		'course_info_class' => 'gv-field-' . HILRCC_PROPOSAL_FORM_ID . "-" . HILRCC_FIELD_ID_COURSE_INFO_STRING,
		'size_cell_class' => 'gv-field-' . HILRCC_PROPOSAL_FORM_ID . "-" . HILRCC_FIELD_ID_CLASS_SIZE,
		'duration_cell_class' => 'gv-field-' . HILRCC_PROPOSAL_FORM_ID . "-" . HILRCC_FIELD_ID_DURATION,
		'room_cell_class' => 'gv-field-' . HILRCC_PROPOSAL_FORM_ID . "-" . HILRCC_FIELD_ID_ROOM,
		'sgl_1_bio_class' => 'gv-field-' . HILRCC_PROPOSAL_FORM_ID . "-" . HILRCC_FIELD_ID_SGL1_BIO,
		'sgl_2_bio_class' => 'gv-field-' . HILRCC_PROPOSAL_FORM_ID . "-" . HILRCC_FIELD_ID_SGL2_BIO,
		'last_mod_class' => 'gv-field-' . HILRCC_PROPOSAL_FORM_ID . "-" . HILRCC_FIELD_ID_LAST_MOD_TIME,
		'semester_field_id' => HILRCC_FIELD_ID_SEMESTER,
		'room_list' => HILRCC_ROOMS,
		'role_context' => $role_context,
		'current_semester' => get_option('current_semester')
	));
}
add_action('wp_enqueue_scripts', 'HILRCC_enqueue_styles');

function HILRCC_redirect_page()
{
	if( is_page( 'course_report' ) && is_user_logged_in() )
	{
		$path = WP_PLUGIN_DIR . '/hilr-ccsubmit/course_report.php';
		include($path);
		exit;
	}
	
	/*
	 * A request to the All Proposals view with no query string gets 
	 * redirected to the same URL with a query string appended to 
	 * limit the search to the current semester.
	 */
	if (is_page('all-proposals') && (strpos($_SERVER['REQUEST_URI'], '/administrative/') != false)) {
		$query = parse_url($_SERVER['REQUEST_URI'])['query'];
		if (empty($query) && (strpos($_SERVER['REQUEST_URI'], '/entry/') === false)) { /* avoid single-entry case */
			$semester = get_option('current_semester');
			if (!empty($semester)) {
				$new_url = $_SERVER['REQUEST_URI'] . '?filter_' . HILRCC_FIELD_ID_SEMESTER . '=' . urlencode($semester) . '&mode=all';
				wp_redirect($new_url);
				exit;
			}
		}
	}
}
add_action( 'template_redirect', 'HILRCC_redirect_page' );

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

/* for the sponsor field on the form, populate the dropdown only with users who have both
   the member role and the cc_sponsor role
*/
add_filter( 'gravityflow_user_field', 'filter_sponsor_field', 10, 3 );
function filter_sponsor_field($users, $form_id, $field) {
	if ($field['id'] == HILRCC_FIELD_ID_SPONSOR) {
		$users = array();
		$all = get_users();
		foreach ($all as $user) {
			if ( in_array( 'cc_member', (array) $user->roles ) &&
			     in_array( 'cc_sponsor', (array) $user->roles )) {
				$elem = array(
					'value' => $user->ID,
					'text'  => $user->display_name
				);
				
				array_push($users, $elem);
			}
		}
	}
	return $users;
}

/* login page customization */
function my_login_logo()
{
?>
    <style type="text/css">
        #login h1 a, .login h1 a {
            background-image: url(<?php echo get_stylesheet_directory_uri() . HILRCC_LOGO_PATH?>);
            width: 320px;
			background-size: auto;
			background-repeat: no-repeat;
        }
    </style>
<?php
}
add_action('login_enqueue_scripts', 'my_login_logo');

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

include 'schedgrid.php';
include 'drafts.php';
?>