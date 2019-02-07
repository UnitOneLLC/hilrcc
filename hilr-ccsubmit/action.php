<?php
/*
	HILR CCsubmit plugin - action.php
*/
	/*
		Action for entry creation and update
	*/
	add_action( 'gform_entry_created', 'HILRCC_on_entry_created' );
	function HILRCC_on_entry_created( $entry) {
		HILRCC_set_either_half_flag($entry);
		/* disallow mark-up in rich text fields */
		HILRCC_strip_tags($entry);
		/* don't allow empty rows in the Books list */
	 	HILRCC_remove_empty_book_rows($entry);
	}
	add_action( 'gform_post_update_entry', 'HILRCC_on_entry_updated' );
	function HILRCC_on_entry_updated( $entry, $form ) {
		/* don't allow empty rows in the Books list */
	 	HILRCC_remove_empty_book_rows($entry);
	}
	
	/*
	 * action to set the HILRCC_FIELD_ID_STATUS field to 'Active' when the workflow restarts.
	 */
	add_action('gravityflow_pre_restart_workflow', 'HILRCC_hook_restart_workflow', 10, 3);
	function HILRCC_hook_restart_workflow($entry, $form) {
		$entry_id = $entry['id'];
		GFAPI::update_entry_field($entry_id, HILRCC_FIELD_ID_STATUS, HILRCC_PROP_STATUS_VALUE_ACTIVE);
	}

	/*
	 * When a workflow is cancelled, force the proposal status to 'mistake'
	 */
	add_action( 'gravityflow_pre_cancel_workflow', 'HILRCC_hook_cancel_workflow', 10, 3 );
	function HILRCC_hook_cancel_workflow( $entry, $form, $step ) {
		$entry_id = $entry['id'];
		GFAPI::update_entry_field($entry_id, HILRCC_FIELD_ID_STATUS, HILRCC_PROP_STATUS_VALUE_MISTAKE);	
	}

	/* utility function */
	function get_entry_id_from_gravityview_url() {
		/* look for '/entry/' followed by id */
	    $url = $_SERVER['REQUEST_URI'];
	    $path = parse_url($url)['path'];
		$elems = preg_split('/\//', $path);
		$pos = array_search('entry', $elems);
		if (is_numeric($pos) and ($pos < (count($elems)-1))) {
			return $elems[$pos+1];
		}
	    else {
	    	return NULL;
	    }
	}

	/*
	 * Some views need a "Go to Workflow View" link. This code
	 * puts that in (for the single entry view).
	 */
	function HILRCC_inject_workflow_link()
	{
		$view_id = HILRCC_get_view_id_from_url();
		$link = site_url() . "/index.php/";
		$entry_id = get_entry_id_from_gravityview_url();
		if ($entry_id == NULL) {
			return;
		}
	    $entry = GFAPI::get_entry($entry_id);
		$user = wp_get_current_user();
		$isInInbox = false;
	    /* if the entry is assigned to the current user, link to the inbox */
		if (is_entry_assigned_current_user($entry)) {
			$link .= "inbox/?page=gravityflow-inbox&view=entry&id=" . HILRCC_PROPOSAL_FORM_ID .
			         "&lid=" . $entry['id'];
			$text = "Act on this proposal";
			$isInInbox = true;
		}
		/* if the current user is an admin, link to the administravie workflow view page */
		if ( in_array('cc_admin', (array) $user->roles) or
			 in_array('administrator', (array) $user->roles) or
			 in_array('catalog_admin', (array) $user->roles) ) {
			/* and emit an edit link (will be completed on client) for admins -- only
			   for the All Proposals view
			*/
			$view_id = HILRCC_get_view_id_from_url();
			if (($view_id == HILRCC_VIEW_ID_ACTIVE) || ($view_id == HILRCC_VIEW_ID_ADMIN_ALL)
				|| ($view_id == HILRCC_VIEW_ID_SCHEDULE)) {
				?>
					<div><a id="hilr_edit_this_proposal_link">Edit this proposal⟶</a></div>
				<?php
			}
			if (!$isInInbox) {
				$link .= "administrative/workflow-status/?page=gravityflow-inbox&view=entry&id=" .
						 HILRCC_PROPOSAL_FORM_ID . "&lid=" . $entry['id'];
				$text = "Act on this proposal";
			}
		}

		if (isset($text)) {
		?>
			<div >
				<a id="goto-workflow" href="<?php echo $link ?>"><?php echo $text?>⟶</a>
			</div>
		<?php
		}
	}
	/* function to hook the gravityview_render_entry action (per view) */
	function add_actions_for_workflow_links() {
		$have_workflow_views = array(
			HILRCC_VIEW_ID_REVIEW,
			HILRCC_VIEW_ID_VOTING,
			HILRCC_VIEW_ID_ACTIVE,
			HILRCC_VIEW_ID_DISCUSS,
			HILRCC_VIEW_ID_SCHEDULE,
			HILRCC_VIEW_ID_ADMIN_ALL
		);
		foreach ($have_workflow_views as &$view_id) {
			add_action('gravityview_render_entry_' . $view_id, 'HILRCC_inject_workflow_link');
		}
	}
	add_actions_for_workflow_links();
?>