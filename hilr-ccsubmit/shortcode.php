<?php
/*
	HILR CCsubmit plugin - shortcode.php
	Shortcode implementation
*/

	add_shortcode('HILRCC_homepage', 'HILRCC_show_homepage');
	function HILRCC_show_homepage($atts, $content)
	{
		/* if display of the form is enabled, show the form */
		if (get_option('submissions_enabled')) {
			return do_shortcode('[gravityform id='.HILRCC_PROPOSAL_FORM_ID.' title=true description=true ajax=true]');
		}
		else {
			return $content;
		}
	}

	/* Function to create a shortcode for the URL of the Inbox page */
	add_shortcode('HILRCC_inbox_url', 'HILRCC_show_inbox_url');
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
		if ($have_formid === false) {
			return esc_url(site_url() . '/index.php/inbox/');
		}
		if ($have_entryid === false) {
			return site_url() . '/index.php/inbox/?page=gravityflow-inbox&view=entry&id=' . $formid;
		}
		
		return site_url() . '/index.php/inbox/?page=gravityflow-inbox&view=entry&id=' . $formid . '&lid=' . $entryid;
	}

	/* Function to create a shortcode for the URL of the Proposal View page */
	add_shortcode('HILRCC_proposal_view_url', 'HILRCC_show_proposal_view_url');
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

	/* Function to create a shortcode for the URL of the Voting Review page */
	add_shortcode('HILRCC_voting_review_url', 'HILRCC_show_voting_review_url');
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

	/* shortcode to force a recompute of computed fields */
	add_shortcode('HILRCC_Recompute_Fields', 'HILRCC_recompute_fields');
	function HILRCC_recompute_fields()
	{
		$semester = get_option('current_semester');
		$search = array();
		$search['status'] = 'active';
		$search[HILRCC_FIELD_ID_SEMESTER] = $semester;
		$search['form_id'] = HILRCC_PROPOSAL_FORM_ID;
		$entries = GFAPI::get_entries(0, $search, null, array( 'offset' => 0, 'page_size' => 1000));
		
		if (!is_wp_error($entries)) {
			foreach($entries as &$entry) {
				$entry_id = $entry['id'];
				HILRCC_update_computed_fields($entry_id);
			}
		}
	}
	/* shortcode to force renumbering of courses */
	add_shortcode('HILRCC_Renumber_Courses', 'HILRCC_renumber_courses');
	function HILRCC_renumber_courses() {
		$startNumber = intval(get_option("starting_course_number"));
		$semester = get_option("current_semester");

		do_renumber_courses($startNumber, $semester);
	}
?>