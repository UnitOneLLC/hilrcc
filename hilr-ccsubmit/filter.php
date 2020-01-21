<?php
/*
	HILR CCsubmit plugin - filter.php
*/

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
	 * Validate phone fields
	 *
	 * @return array('is_valid'=>bool, 'message'=>string)
	 */
	add_filter('gform_field_validation_' . HILRCC_PROPOSAL_FORM_ID . '_' . HILRCC_FIELD_ID_PHONE_1, 'HILRCC_validate_phone_field_1');
	add_filter('gform_field_validation_' . HILRCC_PROPOSAL_FORM_ID . '_' . HILRCC_FIELD_ID_PHONE_2, 'HILRCC_validate_phone_field_2');
	function HILRCC_validate_phone_field_1() {
		return HILRCC_validate_phone_field(HILRCC_FIELD_ID_PHONE_1);
	}
	function HILRCC_validate_phone_field_2() {
		return HILRCC_validate_phone_field(HILRCC_FIELD_ID_PHONE_2);
	}
	function HILRCC_validate_phone_field($field_id)
	{
		$result = array();
		$phone = rgpost('input_' . $field_id);
		if (!HILRCC_validate_phone($phone)) {
			$result['is_valid'] = false;
			$result['message'] = 'Please enter a valid phone number.';
		}
		else {
			$result['is_valid'] = true;
			$result['message'] = '';
		}
		return $result;
	}
	// validate workload field
	add_filter('gform_field_validation_' . HILRCC_PROPOSAL_FORM_ID . '_' . HILRCC_FIELD_ID_WORKLOAD, 'HILRCC_validate_workload');
	function HILRCC_validate_workload()
	{
		$result = array();
		$workload = rgpost('input_' . HILRCC_FIELD_ID_WORKLOAD);
		$is_match = preg_match("/[0-4]/", $workload, $matches);
		if ($is_match and ($matches[0] == trim($workload))) {
			$result['is_valid'] = true;
			$result['message'] = '';
		}
		else {
			$result['is_valid'] = false;
			$result['message'] = 'Please enter a whole number of hours between 0 and 4.';
		}
		return $result;
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
	
	add_filter('gravityview/sorting/full-name', 'HILRCC_set_name_sort_last');
	function HILRCC_set_name_sort_last() {
		return "last";
	}


	/* In GravityView, display the label of a dropdown field value instead of its sorting value */
	add_filter('gravityview/fields/select/output_label', '__return_true');

	function HILRCC_get_view_id_from_url()
	{
		$url = $_SERVER['REQUEST_URI'];
		if (strpos($url, "inbox-view") !== false)
			return HILRCC_VIEW_ID_INBOX;
		if (strpos($url, "proposal-view") !== false)
			return HILRCC_VIEW_ID_REVIEW;
		if (strpos($url, "voting-review") !== false)
			return HILRCC_VIEW_ID_VOTING;
		if (strpos($url, "adminstrative/all-proposals") !== false)
			return HILRCC_VIEW_ID_ADMIN_ALL;
		if (strpos($url, "all-proposals") !== false)
			return HILRCC_VIEW_ID_ACTIVE;
		if (strpos($url, "weekly-update") !== false)
			return HILRCC_VIEW_ID_WEEKLY;
		if (strpos($url, "catalog") !== false)
			return HILRCC_VIEW_ID_CATALOG;
		if (strpos($url, "at-a-glance") !== false)
			return HILRCC_VIEW_ID_GLANCE;
		if (strpos($url, "sched") !== false)
			return HILRCC_VIEW_ID_SCHEDULE;
		if (strpos($url, "under-discussion") !== false)
			return HILRCC_VIEW_ID_DISCUSS;
		return '0';
	}


	/* Attach filter to GravityView get_entries to enable custom sort for catalog view */
	add_filter('gravityview/view/entries', 'HILRCC_custom_view_entries');
	function HILRCC_custom_view_entries($entry_coll)
	{
		$entries = $entry_coll->all();
		$view_id = HILRCC_get_view_id_from_url();
		
	    if ($view_id == HILRCC_VIEW_ID_CATALOG) {
	        try {
	            usort($entries, catalog_comparator);
	            $coll = new \GV\Entry_Collection();
	            foreach($entries as $entry) {
	            	$coll->add($entry);
	            }
	            return $coll;
	        }
	        catch (Exception $e) {
	            echo 'Catalog sorting error:: ', $e->getMessage(), "\n";
	        }
	    }
	    else if ($view_id == HILRCC_VIEW_ID_GLANCE) {
	        try {
	            usort($entries, glance_comparator);
	            $coll = new \GV\Entry_Collection();
	            foreach($entries as $entry) {
	            	$coll->add($entry);
	            }
	            return $coll;
	        }
	        catch (Exception $e) {
	            echo 'Glance sorting error:: ', $e->getMessage(), "\n";
	        }
	    }
	    else if ($view_id == HILRCC_VIEW_ID_INBOX) {
	    
			$inbox_coll = new \GV\Entry_Collection();
	    	$user = wp_get_current_user();
	    	$user_id = $user->ID;
	    	foreach ($entries as &$entry) {
	    		if (is_entry_assigned_current_user($entry)) {
	    			$inbox_coll->add($entry);
	    		}
			}
			return $inbox_coll;
	    }
	    else {
	    	return $entry_coll;
	    }
	}
	
	/* adjust the status field of the form when completing the Review by Committee step (id=13) */
	add_filter('gravityflow_step_complete', 'HILRCC_gravityflow_step_complete', 10, 4);
	function HILRCC_gravityflow_step_complete($step_id, $entry_id, $form_id, $status)
	{
			if ($step_id == HILRCC_STEP_ID_REV_BY_COMM) {
					$newFieldValue = NULL;
					$formStatus    = $_POST['gravityflow_approval_new_status_step_' . HILRCC_STEP_ID_REV_BY_COMM];
					if ($formStatus == 'rejected') {
							$newFieldValue = HILRCC_PROP_STATUS_VALUE_DISCUSS;
					} else if ($formStatus == 'approved') {
							$newFieldValue = HILRCC_PROP_STATUS_VALUE_ACTIVE;
					}
					if ($newFieldValue != NULL) {
							$result = GFAPI::update_entry_field($entry_id, HILRCC_FIELD_ID_STATUS, $newFieldValue);
					}
			}
			if ($step_id == HILRCC_STEP_ID_VOTING) {
					$newFieldValue = NULL;
					$formStatus    = $_POST['gravityflow_approval_new_status_step_' . HILRCC_STEP_ID_VOTING];
					if ($formStatus == 'approved') {
							$newFieldValue = HILRCC_PROP_STATUS_VALUE_APPROVED;
					} else if ($formStatus == 'rejected') {
							$newFieldValue = 'Rejected';
					}
					if ($newFieldValue != NULL) {
							$result = GFAPI::update_entry_field($entry_id, HILRCC_FIELD_ID_STATUS, $newFieldValue);
					}
			}
			$api  = new Gravity_Flow_API($form_id);
			$step = $api->get_current_step(GFAPI::get_entry($entry_id));
			
			/* copy the workflow note to the discussion thread */
			$note = stripslashes_deep($_POST['gravityflow_note']);
			if (!empty($note) and (HILRCC_is_UI_step($step) or HILRCC_is_approval_step($step))) {
				$comment = "[Workflow note] " . $note;
				HILRCC_add_comment($entry_id, $comment);
			}
			/* for UI steps, update computed fields */
			if (HILRCC_is_UI_step($step)) {
					HILRCC_update_computed_fields($entry_id);
					HILRCC_update_last_mod_time(GFAPI::get_entry($entry_id));
			}
	}

	/*
	 * update the entry timestamp when it is saved
	 */
	add_filter( 'gform_entry_post_save', 'HILRCC_post_save');
	/* not clear when/if this gets called */
	function HILRCC_post_save( $entry ) {
		HILRCC_update_last_mod_time($entry);
		HILRCC_strip_tags($entry);
		return $entry;
	}
	
	/*
	 * After a GV edit, the default is to stay in edit mode. This function is
	 * filter that is intended to let you change the message string on a
	 * successful edit. The hack is to instead inject script that redirects
	 * to the provided backlink (saving the user a click).
	 */
	add_filter( 'gravityview/edit_entry/success', 'HILRCC_filter_edit_success', 10, 4);
	function HILRCC_filter_edit_success($entry_updated_message , $view_id, $entry, $back_link) {
		return "<script>location.replace('" . $back_link . "')</script>";
	}

	/*
	 * Gravity Forms 2.4.15 emits a lot of superflous notifications about email that this filter suppresses.
	 * See https://docs.gravityforms.com/gform_notification_note/
	 */
	add_filter( 'gform_notification_note', function ( $note_args, $entry_id, $result, $notification ) {
		if ( ! empty( $notification['workflow_notification_type'] ) && $result ) {
			$note_args = array();
		}
		return $note_args;
	}, 10, 4 );

?>