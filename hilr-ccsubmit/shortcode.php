<?php
/*
	HILR CCsubmit plugin - shortcode.php
	Shortcode implementation
*/

	add_shortcode('HILRCC_homepage', 'HILRCC_show_homepage');
	function HILRCC_show_homepage($atts, $content)
	{
		$override = !empty($_GET["ignore_disable"]);
		
		/* if display of the form is enabled, show the form */
		if ($override OR get_option('submissions_enabled')) {
			$semester = get_option("current_semester");
			$shortcode = "[gravityform id='" . HILRCC_PROPOSAL_FORM_ID . "' title=true description=true ajax=true field_values='hilrcc_semester=$semester']";
			return do_shortcode($shortcode);
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
		$startNumber = HILRCC_COURSENO_FULLSEM_START;
		$semester = get_option("current_semester");

		do_renumber_courses($startNumber, $semester);
	}
	
	
	add_shortcode('HILRCC_Weekly_Update_Counts', 'HILRCC_weekly_update_counts');
	function HILRCC_weekly_update_counts() {
		$markup = <<<EOD
			<div>
				<span id='course-count'></span>
			</div>
			<script>
				durationCounts = [];
				var totalHalves = 0;
				document.querySelectorAll("[data-label='Course Duration']").forEach((node) => {
					dur = node.textContent;
					if (!durationCounts[dur]){ durationCounts[dur] = 1;}
					else { ++durationCounts[dur];}
					if (dur.indexOf('Half') != -1) totalHalves++;
					if (dur.indexOf('Full') != -1) totalHalves += 2;
				});
				var durMarkup = '';
				for (dur in durationCounts) {
					if ((dur.indexOf('Half') != -1) || (dur.indexOf('Full') != -1)) {
							durMarkup += dur + ': ' + durationCounts[dur] + '<br>'
						}
					}
					durMarkup += 'FTE: ' + totalHalves/2 + '<br>';
					document.getElementById('course-count').innerHTML = durMarkup;
			</script>
		EOD;
	
		return $markup;
	}

	add_shortcode('HILRCC_Active_Proposal_Counts', 'HILRCC_active_proposal_counts');
	function HILRCC_active_proposal_counts() {
		$markup = <<<EOD
			<div>
				Proposal count: <span id="__count"></span><br>
				Full Term: <span id="__fullTerm"></span><br>
				First Half: <span id="__1stHalf"></span><br>
				Second Half: <span id="__2ndHalf"></span><br>
				Either Half: <span id="__eitherHalf"></span><br>
				FTE: <span id="__FTE"></span><br>
			</div>
			<script>
				var v__text = jQuery(jQuery(".gv-table-view")[0]).text();
				var v__count = jQuery("tbody [data-label=ID").length; 
				var v__fullTerm = v__text.match(/Full Term/g); v__fullTerm = v__fullTerm ? v__fullTerm.length : 0;
				var v__1stHalf = v__text.match(/First Half/g); v__1stHalf= v__1stHalf ? v__1stHalf.length : 0;
				var v__eitherHalf = v__text.match(/Either First or Second Half/g); v__eitherHalf = v__eitherHalf ? v__eitherHalf.length : 0;
				var v__2ndHalf = v__text.match(/Second Half/g); v__2ndHalf = v__2ndHalf ? v__2ndHalf.length - v__eitherHalf : 0;
				var v__FTE = v__fullTerm + (v__1stHalf + v__eitherHalf + v__2ndHalf)/2;
				
				jQuery("#__count").text(v__count);
				jQuery("#__fullTerm").text(v__fullTerm);
				jQuery("#__1stHalf").text(v__1stHalf);
				jQuery("#__2ndHalf").text(v__2ndHalf);
				jQuery("#__eitherHalf").text(v__eitherHalf);
				jQuery("#__FTE").text(v__FTE);
			</script>
		EOD;
	
		return $markup;
	}
?>