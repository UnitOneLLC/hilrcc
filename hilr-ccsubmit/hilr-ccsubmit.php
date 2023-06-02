<?php
/*
	Plugin Name: hilr-ccsubmit
	Plugin URI: https://ccsubmit.com
	Description: Course Proposal Ingestion and Processing
	Version: 1.0
	Author: Frederick Hewett, HILR
	Author URI: https://ccsubmit.com
	License: GPL-3.0+
	-------------------------------------------------------------
	Copyright 2019, Harvard Institute for Learning in Retirement
*/

define('HILRCC_BUILD', '2.2.0');
#ID of the Gravity Forms form for course proposals
define('HILRCC_PROPOSAL_FORM_ID', '2');
#
# IDs for GravityView views
define('HILRCC_VIEW_ID_REVIEW', '129');
define('HILRCC_VIEW_ID_CATALOG', '201');
define('HILRCC_VIEW_ID_VOTING', '273');
define('HILRCC_VIEW_ID_ACTIVE', '308');
define('HILRCC_VIEW_ID_DISCUSS', '376');
define('HILRCC_VIEW_ID_WEEKLY', '405');
define('HILRCC_VIEW_ID_INBOX', '415');
define('HILRCC_VIEW_ID_SCHEDULE', '419');
define('HILRCC_VIEW_ID_GLANCE', '426');
define('HILRCC_VIEW_ID_ADMIN_ALL', '592');
#
# IDs for fields in the Gravity Forms form
define('HILRCC_FIELD_ID_TITLE', '1');
define('HILRCC_FIELD_ID_DURATION', '3');
define('HILRCC_FIELD_ID_COURSE_DESC', '6');
define('HILRCC_FIELD_ID_BOOKS', '14');
define('HILRCC_FIELD_ID_OTHER_MAT', '15');
define('HILRCC_FIELD_ID_SGL1_BIO', '18');
define('HILRCC_FIELD_ID_SGL2_BIO', '19');
define('HILRCC_FIELD_ID_CLASS_SIZE', '27');
define('HILRCC_FIELD_ID_THANK_YOU', '44');
define('HILRCC_FIELD_ID_SPONSOR', '35');
define('HILRCC_FIELD_ID_DISCUSSION', '36');
define('HILRCC_FIELD_ID_STATUS', '37');
define('HILRCC_FIELD_ID_CHOICE_1', '38');
define('HILRCC_FIELD_ID_CHOICE_3', '39');
define('HILRCC_FIELD_ID_CHOICE_2', '40');
define('HILRCC_FIELD_ID_COLLOQUIUM', '41');
define('HILRCC_FIELD_ID_COURSE_NO', '42');
define('HILRCC_FIELD_ID_PHONE_1', '46');
define('HILRCC_FIELD_ID_PHONE_2', '50');
define('HILRCC_FIELD_ID_SGL1_EMAIL', '52');
define('HILRCC_FIELD_ID_SEMESTER', '54');
define('HILRCC_FIELD_ID_TIMESLOT', '55');
define('HILRCC_FIELD_ID_SGL1_NAME', '56');
define('HILRCC_FIELD_ID_SGL1_FIRST', '56.3');
define('HILRCC_FIELD_ID_SGL1_LAST', '56.6');
define('HILRCC_FIELD_ID_SGL2_NAME', '57');
define('HILRCC_FIELD_ID_SGL2_FIRST', '57.3');
define('HILRCC_FIELD_ID_SGL2_LAST', '57.6');
define('HILRCC_FIELD_ID_SGL2_EMAIL', '53');
define('HILRCC_FIELD_ID_WORKLOAD', '58');
define('HILRCC_FIELD_ID_SGL1_PREV','61');
define('HILRCC_FIELD_ID_SGL2_PREV','63');
define('HILRCC_FIELD_ID_READINGS_STRING', '64');
define('HILRCC_FIELD_ID_SUPPRESS_NOTIFY', '66');
define('HILRCC_FIELD_ID_TIME_PREFERENCE', '67');
define('HILRCC_FIELD_ID_COURSE_INFO_STRING', '68');
define('HILRCC_FIELD_ID_ROOM', '69');
define('HILRCC_FIELD_ID_INSTRUCTIONS', '72');
define('HILRCC_FIELD_ID_FLEX_HALF', '75');
define('HILRCC_FIELD_ID_LAST_MOD_TIME', '78');
define('HILRCC_FIELD_ID_PREV_OFFERING', '79');
#
# IDs for steps in the Gravity Flow workflow attached to the form
define('HILRCC_STEP_ID_SPONSOR_ASSIGNMENT', '1');
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
#
# Proposal status values
define('HILRCC_PROP_STATUS_VALUE_ACTIVE', 'Active');
define('HILRCC_PROP_STATUS_VALUE_DISCUSS', 'Under discussion');
define('HILRCC_PROP_STATUS_VALUE_APPROVED', 'Approved');
define('HILRCC_PROP_STATUS_VALUE_DEFERRED', 'Deferred');
define('HILRCC_PROP_STATUS_VALUE_REJECTED', 'Rejected');
define('HILRCC_PROP_STATUS_VALUE_WITHDRAWN', 'Withdrawn');
define('HILRCC_PROP_STATUS_VALUE_MISTAKE', 'Mistake');

# Other string constants
define('HILRCC_LABEL_AUTHOR', 'Author (first last)');
define('HILRCC_LABEL_TITLE', 'Title');
define('HILRCC_LABEL_PUBLISHER', 'Publisher');
define('HILRCC_LABEL_EDITION', 'Year');
define('HILRCC_LABEL_ONLY_ED', 'Only this edition (x)');
define('HILRCC_TAG_BOLD_OPEN', '<strong>');
define('HILRCC_TAG_BOLD_CLOSE', '</strong>');
#
# Room List - the room list is comma-separated. This is used on the scheduling page.
define('HILRCC_ROOMS', 'G20,118,120,204,205,206,CLQM,305,307,Zoom');

# for timestamps on entries
define('HILRCC_DEFAULT_TIMEZONE', 'America/New_York');

# course numbering
define('HILRCC_COURSENO_FULLSEM_START', 300);
define('HILRCC_COURSENO_1STHALF_START', 100);
define('HILRCC_COURSENO_2NDHALF_START', 200);

include_once plugin_dir_path(__FILE__) . 'bizlogic.php';
include_once plugin_dir_path(__FILE__) . 'shortcode.php';
include_once plugin_dir_path(__FILE__) . 'ajax.php';
include_once plugin_dir_path(__FILE__) . 'filter.php';
include_once plugin_dir_path(__FILE__) . 'action.php';
include_once plugin_dir_path(__FILE__) . 'settings.php';
?>