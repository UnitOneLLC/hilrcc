/*
 * hilrprop.js
 * Copyright (c) 2018 HILR
 *
 * Client-side code for HILR Course proposal app
 */
 
var HILRCC = {
	/* the string table is set up in functions.php -- see HILRCC_enqueue_styles */
	stringTable: HILRCC_stringTable,

	/* Custom CSS classes on certain inputs encode the instructions for
	 * rearranging the DOM afer load.
	 */
	MOVECLASS_STEM : "hilr-movecell-",
	REMOVE_TH : "hilr-remove-th",
	COLSPAN_STEM : "hilr-colspan-",
	KEEP_TH : "hilr-keep-th",
	
	CLASS_CAPS: ["12", "16", "20", "24"],
	
	formIsDirty: false, /* flag used by unload handler */
	loadTime: new Date(),
	
	/*
	 * this function is invoked via jQuery when any page on the site loads.
	 */
	onLoad: function() {
		HILRCC.formIsDirty = false;

		/* if this is the home page (i.e. the form), add a button to reset the form */
		if (window.location.pathname === "/" || window.location.pathname === "") {
			HILRCC.setupMainForm();
		}
	
		/* delayed actions for RTEs */
		setTimeout(HILRCC.delayedSetup, 2500); /* allow settle time for RTE (bug?) */
	
		var viewId = HILRCC.getGravityViewId();
		if (viewId) {
			HILRCC.prepareView(viewId);
		}
		if (HILRCC.isGravitySingleView()) {
			HILRCC.rearrangeGravityViewTable();
		}
		
		if (HILRCC.isGravityFlowInboxEntry()) {
			HILRCC.modifyInboxEntry();
		}
		
	  	HILRCC.fixAdminBox();
	  	
	  	HILRCC.fixInboxTab();
	  	
	  	HILRCC.fixValidationMessage();
	  	
	  	HILRCC.removeExcessSectionHeader();
	  	
	  	HILRCC.fixViewMoreLessToggle();
	  	
	  	HILRCC.showWordCounts();
	
		HILRCC.fixWorkflowNoteLabel();
		
		HILRCC.removeHiddenFields();
		
		HILRCC.fixAllProposalsUrl();
	  	
	  	// disable autofill
		jQuery("input").attr( 'autocomplete', 'new-password' );
		
		jQuery("body").addClass(HILRCC.stringTable.role_context);
    },

	allowedChangeElementIds: ["gravityflow-note", "gravityflow-admin-action", "gentry_display_empty_fields"],
	
	clearDirty:  function() {
		HILRCC.formIsDirty = false;
	},
	setDirty: function() {
		HILRCC.formIsDirty = true;
	},
	delayedSetup: function() {
		HILRCC.installUnloadHandler();
		HILRCC.moveRTEButtons();	
	},
    installUnloadHandler: function() {

		jQuery("iframe").contents().find("#tinymce").on("keyup", function() {
			HILRCC.setDirty();
		}); 
  			
		var inputs = jQuery("form").not(".gv-widget-search").not("#adminbarsearch");		    
		    
  		if ( inputs.length !== 0 ) {
  			jQuery(window).on("beforeunload", HILRCC.onUnload);
  			inputs.change(function(e) {
  				var lookingFor = e.target.id;
  				for(var i=0; i < HILRCC.allowedChangeElementIds.length; ++i) {
  					if (HILRCC.allowedChangeElementIds[i] == lookingFor)
  						return;
  				}
	  			HILRCC.setDirty();
  			});
  			
 			var submitBtn = jQuery("#gform_submit_button_" + HILRCC.stringTable.formId);
  			if (submitBtn.length) {
  				submitBtn.on("click", HILRCC.clearDirty);
  			}
  			var submitBtn = jQuery("#gravityflow_submit_button");
  			if (submitBtn.length) {
  				submitBtn.on("click", HILRCC.clearDirty);
  			}
  			submitBtn = jQuery("#gravityflow_update_button");
  			if (submitBtn.length) {
  				submitBtn.on("click", HILRCC.clearDirty);
  			}
  			submitBtn = jQuery("#gravityflow_save_progress_button");
  			if (submitBtn.length) {
  				submitBtn.on("click", HILRCC.clearDirty);
  			}

  		}
     },
    
    isGravitySingleView: function() {
    	return (jQuery(".hilr-view-course-title").length == 1);
    },
    
	moveRTEButtons: function() {
		/* show the 'special character' button on the RTE as a sibling of the italic button */
		var rtes = jQuery('.hilr-hide-rte-tools');
		rtes.each(function(div) {
			div = jQuery(rtes[div]);
			var ital = div.find('.mce-btn[aria-label^="Italic"]');
			var spec = div.find('.mce-btn[aria-label^="Special character"]');
			if (ital && spec) {
				spec.detach().appendTo(ital.parent());
			}
		});
	},

    /*
     * initialization of the main form
     */
    setupMainForm: function() {
    
    	var jform = jQuery("#gform_" + HILRCC.stringTable.formId);
    	jform.keypress(function(event) {
    		HILRCC.lastKey = event.charCode;
    		if (event.charCode == 13) {
    			event.preventDefault();
    		}
    		return true;
    	});

    	/* add a button to reset the form */
		var footer = jQuery(".gform_footer");
		if (footer.length == 1) {
			var resetBtn = document.createElement("button");
			jQuery(resetBtn).text("Reset Form").attr("type", "reset").attr("id", "hilr_reset_btn");
			footer.append(resetBtn);
			jQuery(resetBtn).click(HILRCC.confirmClearForm);
		}
		
/* hide the delayed start if target semester is Fall */
		if (HILRCC.getCurrentSemester().indexOf("Fall") == 0) {
			var idclass = ".gchoice_" + HILRCC.stringTable.formId + "_3_1";
			var delayStartOption = jQuery(".hilr-duration " + idclass);
			if (delayStartOption) {
				delayStartOption.hide();
			}
		}
		
		/* initialize listeners for schedule choice radio buttons */
		/**** DISABLING THIS CODE
		jQuery('.hilr-sched-1 input[type=radio]').click(function() {HILRCC.onSchedClick(this.value, 1);});
		jQuery('.hilr-sched-2 input[type=radio]').click(function() {HILRCC.onSchedClick(this.value, 2);});
		jQuery('.hilr-sched-3 input[type=radio]').click(function() {HILRCC.onSchedClick(this.value, 3);});
		******/
		/*
		 * Save and Continue
		 */
		jQuery("#gform_save_" + HILRCC.stringTable.formId + "_link").on("click", HILRCC.saveEmailSession);
		
		var ajaxSubmitIframe = jQuery("#gform_ajax_frame_" + HILRCC.stringTable.formId);
		if (ajaxSubmitIframe.length) {
			ajaxSubmitIframe[0].onload = function() {
				HILRCC.clearDirty();
				ajaxSubmitIframe.off("load", HILRCC.onLoad);
				ajaxSubmitIframe.on("load", HILRCC.onLoad);		
				setTimeout(function() {
					var emailInput = jQuery("[name='gform_resume_email']");
					if (emailInput.length) {
						emailInput.val(HILRCC.getEmailSession());
					}
				},
				1000);
			};
		}
    },
    
	/*
	   This function makes the radio groups for the 1st, 2nd, and
	   3rd time slots mutually exclusive, e.g. if the user checks
	   Tuesday PM for the 1st choice, then Tuesday PM is unchecked
	   for the 2nd and 3rd choices
	*/
	/********** DISABLING THIS CODE
	onSchedClick: function(val, clickedGroup) {
		var clicked, others = Array(2);
		if (clickedGroup === 1) {
			clicked = jQuery('.hilr-sched-1 input[type=radio]');
			others[0] = jQuery('.hilr-sched-2 input[type=radio]');
			others[1] = jQuery('.hilr-sched-3 input[type=radio]');
		}
		else if (clickedGroup === 2) {
			clicked = jQuery('.hilr-sched-2 input[type=radio]');
			others[0] = jQuery('.hilr-sched-1 input[type=radio]');
			others[1] = jQuery('.hilr-sched-3 input[type=radio]');
		}
		else {
			clicked = jQuery('.hilr-sched-3 input[type=radio]');
			others[0] = jQuery('.hilr-sched-1 input[type=radio]');
			others[1] = jQuery('.hilr-sched-2 input[type=radio]');
		}
		
		for (var i = 0; i < clicked.length; ++i) {
			if (clicked[i].value == val) {
				others[0][i].checked = false;
				others[1][i].checked = false;
			}
		}
	},
	*/

	/*
	 * In GV single entry mode, reconfigure the table for compactness based on
	 * CSS class names.
	 */
	rearrangeGravityViewTable: function() {
		 var movers = jQuery("[class*=" + HILRCC.MOVECLASS_STEM +"]");
		 for (var i=0; i < movers.length; ++i) {
			var hilrcls = HILRCC.extractClassname(movers[i].className, HILRCC.MOVECLASS_STEM);
			var dest = hilrcls.substr(HILRCC.MOVECLASS_STEM.length).split('-');
			var sel = "tr.gv-field-" + HILRCC.stringTable.formId + "-" + dest[0];
			var destRow = jQuery(sel);
			var td = jQuery(movers[i]).children('td')[0];
			destRow.append(jQuery(jQuery(td).remove()));
			jQuery(movers[i]).hide();
	
			if (jQuery(movers[i]).hasClass(HILRCC.KEEP_TH)) {
				var th = jQuery(movers[i]).children('th')[0];
				jQuery(td).html("<b>" + jQuery(th).text() + "</b>: " + jQuery(td).html());
			}
		 }
		 /* remove TH elements for rows that have class hilr-remove-th */
		 var deadTHs = jQuery("." + HILRCC.REMOVE_TH + " th");
		 for (var i=0; i < deadTHs.length; ++i) {
			deadTHs[i].remove();
		 }
		 /* add colspan attributes per hilr-colspan-* classes */
		 var addColspans = jQuery("[class*=" + HILRCC.COLSPAN_STEM +"]");
		 for (var i=0; i < addColspans.length; ++i) {
			var hilrcls = HILRCC.extractClassname(addColspans[i].className, HILRCC.COLSPAN_STEM);
			var colspan = hilrcls.substr(HILRCC.COLSPAN_STEM.length);
			jQuery(jQuery(addColspans[i]).children("td")[0]).prop("colspan", colspan);
		 }
	},
    
	/*
	 * Inject a textarea and a button in the single item
	 * view for adding comments on a proposal
	 */
    setupForCommentInput: function() {
    	jQuery(window).load(function() {
			 var editLinkRow = jQuery(".hilr-edit-link");
			 if (editLinkRow.length) {
			 	editLinkRow.removeClass("gv-field-2-edit_link");
				var markup=
					"<th>Add a comment</th>" +
					"<td colspan='3'>" +
					"<textarea id='hilr_comment_input' rows='5' cols='80' class='hilr-comment-input'></textarea>" +
					"<button id='hilr_add_comment_btn' class='hilr-add-comment-button'>Submit</button>" +
					"<button id='hilr_discard_comment_btn' class='hilr-add-comment-button'>Discard and Go Back</button>" +
					"</td>";
		 
				editLinkRow.html(markup);
				jQuery("#hilr_add_comment_btn").click(HILRCC.handle_add_comment);
				jQuery("#hilr_discard_comment_btn").click(HILRCC.handle_discard_comment);
			 }
			 /*
			 * This code is to contain a discussion thread in a scrolling div
			 * so that it doesn't take up too much vertical real-estate.
			 */
			var discussionRow = jQuery(".hilr-view-discussion");
			if (discussionRow.length) {
				var tcell = jQuery(discussionRow).children("td")[0];
				var contents = jQuery(tcell).html();
				contents = "<div class='hilr-discussion-scroller'>" + contents + "</div>";
				jQuery(tcell).html(contents);
			}
		});

	},
    
    setupAdminPage: function() {
    	jQuery(window).load(function() {
	    	jQuery("#hilr-clear-numbers-btn").on('click', HILRCC.clearCourseNumbers);
			jQuery("#hilr-assign-numbers-btn").on('click',HILRCC.assignCourseNumbers);
		});
    },
    
    fixAdminBox: function() {
    	var adminBox = jQuery("#postbox-container-1 div.postbox").not("#gravityflow-status-box-container");
    	if (adminBox.length != 1)
    		return;

		var warningUrl = HILRCC.stringTable.childThemeRootURL + "/adminwarning.html";
  		var markUp = "<div class='hilr-admin-postbox-wrapper'>" +
						"<button id='hilr-toggle-admin-btn'>Workflow Controls</button>" +
					 "</div>" +
					 "<iframe id='hilr-admin-warning-frame' src='" + warningUrl + "'/>";

    	adminBox.before(markUp);
    	adminBox.toggle();
    	
		jQuery("#hilr-toggle-admin-btn").click(function() {
			var ANIMATION_TIME_MS = 250;
			jQuery("#postbox-container-1 div.postbox").not("#gravityflow-status-box-container").toggle(ANIMATION_TIME_MS);
	    	jQuery("#hilr-admin-warning-frame").toggle(ANIMATION_TIME_MS);
	    	return false;
	    });
    },
    
    fixInboxTab: function() {
    	if (location.pathname == "/index.php/inbox/") {
			var inboxTab = jQuery("#top-menu>li:first-child");
			if (inboxTab.length == 1) 
				inboxTab.addClass("current_page_item current-menu-item page_item");    	
    	}
    },
    
    fixValidationMessage: function() {
		/* if the confirmation message is present, skip this */
		var confId = "#gform_confirmation_message_" + HILRCC.stringTable.formId;
		if (jQuery("iframe").contents().find(confId).length !== 0)
			return;
    	var valErr = jQuery(".gform_validation_error div.validation_error");
    	if (valErr.length !== 0) {
    		var moreText = jQuery(document.createElement("div"));
    		moreText.text("Your proposal has not been submitted yet. Please correct the problems and submit again. You can also choose Save and Continue Later to save a draft without fixing the problems.");
    		moreText.addClass("hilr-val-err-more");
    		valErr.append(moreText);
    		HILRCC.setDirty();
    	}
    },
    
	/*
	 * if there's a "View More" toggle, just get rid of it
	 */
    fixViewMoreLessToggle: function() {
		var viewMoreLess = jQuery("a[title='View More']");
		viewMoreLess.remove();
		var hidden = jQuery(".gravityflow-dicussion-item-hidden");
		if (hidden.length !== 0) {
			hidden.attr("style", "display:block");
		}
    },
    
    removeExcessSectionHeader: function() {
    	var secHeads = jQuery(".entry-detail-view .entry-view-section-break");
    	for (var i=0; i < secHeads.length; ++i) {
    		var sh = jQuery(secHeads[i]);
    		if (sh.text().indexOf("Thank you for preparing") == 0) {
    			sh.remove();
    			return;
    		}
    	}
    },
    
    showWordCounts: function() {
   		var cdRow = jQuery("tr." + HILRCC.stringTable.course_desc_class);
   		cdRow = cdRow.add("tr." + HILRCC.stringTable.sgl_1_bio_class);
   		cdRow = cdRow.add("tr." + HILRCC.stringTable.sgl_2_bio_class);
   		var k;
   		for (k=0; k < cdRow.length; ++k) {
   			var thisRow = jQuery(cdRow[k]);
   			var cdparas = jQuery(thisRow.find("td p"));
   			var wc = 0;
   			for (var i=0; i < cdparas.length; ++i) {
   				var cd = jQuery(cdparas[i]);
				if (cd.length !== 0) {
					wc += HILRCC.wordCount(cd.text());
				}
   			}
			var label = jQuery(thisRow.find("th span")[0]);
			var countSpan = jQuery(document.createElement("span"));
			countSpan.text("   (" + wc + " words)");
			countSpan.addClass("hilr-word-count");
			label.after(countSpan);
			label.after(document.createElement("br"));
   		}
    },
    
    wordCount: function(s) {
		var count = 0;
		var matches = s.match(/\b/g);
		if (matches) {
			count = matches.length / 2;
		}
		return count;
   },

	fixWorkflowNoteLabel: function () {
		var noteLabel = jQuery("#gravityflow-notes-label, label[for='gravityflow-note']");
		if (noteLabel.length !== 0) {
			noteLabel.text("Workflow Note");
		}
	},
    
    warningIframeLoaded: function(h) {
    	var frame = jQuery('#hilr-admin-warning-frame');
		frame.height(h+15);
		frame.toggle();
    },
    
    /*
     * Make an ajax call to renumber the courses (after input check and
     * confirmation).
     */
    assignCourseNumbers: function() {
    	var start = jQuery("#hilr-start-number").val();
    	if (start) {
    		start = parseInt(start);
    	}
    	else {
    		start = NaN;
    	}
    
		if (isNaN(start)) {
			alert("You must provide a numeric starting value.");
			return;
		}

    	if (!confirm("This will renumber all courses for " + HILRCC.getCurrentSemester() +
    					" starting with " + start))
    		return;
    
    	var data = {
			'action': 'renumber_courses',
			'semester' : HILRCC.getCurrentSemester(),
			'start' : start
		};

		jQuery.post(HILRCC.stringTable.ajaxURL, data, function(response) {
			if (response.indexOf("SUCCESS") == 0) {
				alert("Courses were successfully renumbered.");
			}
			else  {
				alert("Sorry, there was a problem: " + response);
			}
		});
    },
    
    /*
     * Make an ajax call to clear all the courses numbers.
     */
    clearCourseNumbers: function() {
       	if (!confirm("This will clear all course numbers for " + HILRCC.getCurrentSemester()))
    		return;
    		
    	var data = {
			'action': 'clear_course_numbers',
			'semester' : HILRCC.getCurrentSemester()
		};

		jQuery.post(HILRCC.stringTable.ajaxURL, data, function(response) {
			if (response.indexOf("SUCCESS") == 0) {
				alert("Course numbers were successfully removed.");
			}
			else {
				alert("Sorry, there was a problem: " + response);
			}
		});
    },
    /*
     * Make an ajax call to update all the computed fields for the current semester
     */
    updateComputedFields: function() {
    	var data = {
			'action': 'update_computed_fields',
			'semester' : HILRCC.getCurrentSemester()
		};

		jQuery.post(HILRCC.stringTable.ajaxURL, data, function(response) {
			if (response.indexOf("SUCCESS") == 0) {
				alert("Successfully recomputed fields.");
			}
			else  {
				alert("Sorry, there was a problem: " + response);
			}
		});
    
    },
    /*
     * Make an ajax call to post a comment (discussion entry).
     */
	lastCommentAdded: "",
	
    handle_add_comment: function() {
    	var text = jQuery("#hilr_comment_input").val();
    	var path = location.pathname.split('/');
   		var entryId = path[path.length-1];

		if (text == HILRCC.lastCommentAdded) {
			return; /* multiple clicks on the button -- just ignore */
		}
		HILRCC.lastCommentAdded = text;
		
    	if (!entryId) entryId = path[path.length-2];    
    	var data = {
			'action': 'add_comment',
			'entryId' : entryId,
			'text': text
		};

		jQuery.post(HILRCC.stringTable.ajaxURL, data, function(response) {
			if (response.indexOf("SUCCESS") == 0) {
				HILRCC.clearDirty();
				window.location.reload();
			}
			else if (response.indexOf("EMPTY") != 0) {
				alert("Sorry, there was a problem: " + response);
				HILRCC.lastCommentAdded = "";
			}
		});

    },
    
    check_entry_assigned: function(entry_id, handler) {
    	var data = {
			'action': 'is_entry_assigned_to_user',
			'entry_id' : entry_id
		};

		jQuery.post(HILRCC.stringTable.ajaxURL, data, function(response) {
			if (response.indexOf("true") == 0) {
				handler(true);
			}
			else  {
				handler(false);
			}
		});
    },
    
    handle_discard_comment: function() {
    	var textarea = jQuery("#hilr_comment_input");
    	if ((textarea.val() == '') || window.confirm("Are you sure?")) {
    		HILRCC.clearDirty();
	    	textarea.val("");
    		window.history.back();
    	}
    },
    
    getCurrentSemester: function() {
		var curSemester = HILRCC.stringTable.current_semester;
		if (curSemester)
			return curSemester;
			
		/* fallback to using current date if not in settings */
		var today = new Date();
		var month = today.getMonth();
		var year = today.getYear() + 1900;
		var comingSeason = "";
		if (month < 6)
			comingSeason = "Fall ";
		else {
			comingSeason = "Spring ";
			year = year + 1;
		}
		
		return comingSeason + year;
    },
    
    extractClassname: function(classList, stem) {
		var clazzes = classList.split(' ');
		var hilrcls = "";
		for (var i=0; i < clazzes.length; ++i) {
			if (clazzes[i].indexOf(stem) == 0) {
				hilrcls = clazzes[i];
				break;
			}
		}
		return hilrcls;
    },
    
    onUnload: function(e) {
    	if (HILRCC.formIsDirty) {
			var msg = "You have unsaved input -- click Cancel to stay on page";
			e.returnValue = msg;
    		return msg;
    	}
    	else {
    		return undefined;
    	}
    },
    
    confirmClearForm: function() {
    	HILRCC.clearDirty();
    	return confirm('Do you really want to reset the form?');
    },
    
    /* GravityView pages have a custom widget on the multiple items view
       that contains a span with class 'hilr-view-id'. The id of the span
       is the name of the view.
    */
    getGravityViewId: function() {
    	var viewIdSpan = jQuery(".hilr-view-id");
    	if (viewIdSpan.length == 1) {
    		return viewIdSpan.attr("id");
    	}
    	else {
    		return null;
    	}
    },
    
    /* code specific to different GravityView views */
    prepareView: function(viewId) {
    
    	/* for inbox view, munge course title links to open workflow inbox for proposal */
    	if (viewId === 'inbox-view') {
    		titles = jQuery("td.gv-field-2-1");
    		for (var i=0; i < titles.length; ++i) {
    			var anchor = jQuery(jQuery(titles[i]).children('a')[0]);
    			var href = anchor.attr('href');
    			var pathElems = href.split("/");
    			var id = pathElems[pathElems.length-2];
    			
    			anchor.attr('href', 'javascript:HILRCC.goToInboxView(' + id + ')');
        	}
        }
        else if ((viewId == 'all-proposals-single') || (viewId == 'all-proposals-2-single') || (viewId == 'scheduling-single')) {
        	/* process edit link for single entry view */
			var entryEditLink = jQuery(".gv-field-" + HILRCC.stringTable.formId + "-edit_link td a");
			if (entryEditLink.length == 1) {
				var url = entryEditLink.attr("href");
				var topAnchor = jQuery("#hilr_edit_this_proposal_link");
				if (topAnchor.length == 1) {
					topAnchor.attr("href", url);
				}
			}
        }
        else if (viewId == 'review-by-committee-single') {
        	HILRCC.setupForCommentInput();
        }
    	else if (viewId === 'at-a-glance') {
    	
			HILRCC.combineCourseNumbersWithTitle();
			
			/* inject css classes for shading */
    		let items = jQuery(".gv-list-view");
    		var currentSlot = "";
    		for (let i=0; i < items.length; ++i) {
    			var item = jQuery(jQuery(items[i]).children(".gv-list-view-title")[0]);

				var durationDiv = item.children(".hilr-glance-duration")[0];
				if (durationDiv) {
					var durationMap = {
						"Full Term": "hilr-glance-full",
						"First Half": "hilr-glance-first",
						"Second Half": "hilr-glance-second"
					}
					var durVal = jQuery(durationDiv).text();
					if (durVal) durVal = durVal.trim();
					var durClass = durationMap[durVal];
					if (durClass) {
						item.addClass(durClass);
					}
				}

				/* intersperse headers for time slot */
				
    			var thisSlotDiv = item.children(".hilr-glance-slot")[0];
    			if (thisSlotDiv) {
    				var slot = jQuery(thisSlotDiv).text();
    				if (slot && (slot != currentSlot)) {
    					currentSlot = slot;
    					var headerDiv = document.createElement("div");
    					jQuery(headerDiv).addClass("hilr-glanceview-header");
						slot = slot.replace(" AM", ", 10 AM–12 NOON");
						slot = slot.replace(" PM", ", 1 PM–3 PM");
    					jQuery(headerDiv).text(slot);
    					item.before(headerDiv);
    				}
    			}
    		}

			/* set up the copy-to-clipboard button */
			jQuery("#hilr-copy-glance-to-clipboard-btn").click(HILRCC.copyGravityViewListViewToClipboard);
    		
    	}
    	else if (viewId === 'scheduling') {
    	
    		/* allow a little more width */
    		jQuery("#primary").css("max-width", "1500px");

			/* apply a special CSS class to the 'duration' cell if the value of flex_half in the row is 'true' */
			
			jQuery("td.hilr-scheduling-flex").each(function (index) {
				var jFlex = jQuery(this);
				if (jFlex.html() === 'true') {
					jFlex.parent().children("." + HILRCC.stringTable.duration_cell_class).addClass("hilr-flex-duration");
				}
			});
    	
    		/* install in-place editors for slot, size, room, and duration columns */
    		
    		jQuery("td." + HILRCC.stringTable.slot_cell_class).dblclick(
    		  	function() {
					var props = {
						options: ["—", "Monday AM", "Monday PM", "Tuesday AM", "Tuesday PM",
								   "Wednesday AM", "Wednesday PM", "Thursday AM", "Thursday PM" ],
						updateAjaxAction: "update_timeslot",
						onAjaxSuccess: HILRCC.updateScheduleGrid
					};
    			    (new InplaceCellEditor()).create(this, props);
    		     }
    		);
    			
    		jQuery("td." + HILRCC.stringTable.size_cell_class).dblclick(
    		  	function() {
					var props = {
						options: HILRCC.CLASS_CAPS,
						updateAjaxAction: "update_class_size"
					};
    				(new InplaceCellEditor()).create(this, props);
    		  	}
    		);
 
     		jQuery("td." + HILRCC.stringTable.duration_cell_class).dblclick(
    		  	function() {
					var props = {
						options: ["Full Term", "Full Term Delayed Start", "First Half", "Second Half", "Either First or Second Half"],
						updateAjaxAction: "update_duration",
						onAjaxSuccess: HILRCC.updateScheduleGrid
     				};
    				(new InplaceCellEditor()).create(this, props);
    			}
    		);
   			
     		jQuery("td." + HILRCC.stringTable.room_cell_class).dblclick(
    		  	function() {
					var props = {
						options: ['—'].concat(HILRCC.stringTable.room_list.split(',')),
						updateAjaxAction: "update_room",
						onAjaxSuccess: HILRCC.updateScheduleGrid
     				};
    				(new InplaceCellEditor()).create(this, props);
    			}
    		);

			// if table cells in the Room or Slot columns are empty, put in a — char
			var maybeEmptyCells = jQuery("td.hilr-sched-maybe-empty");
			for (var k=0; k < maybeEmptyCells.length; ++k) {
				var cell = maybeEmptyCells[k];
				if (jQuery(cell).html().length === 0) {
					jQuery(cell).html('—')
				}
			}

			// add a tooltip on cells in the "time-preference-summary" column    		
    		var tps = jQuery("td.hilr-sched-tps");
    		tps.tooltip();
    		for (var i=0; i < tps.length; ++i) {
    			var cell = jQuery(tps[i]);
    			var parent = cell.parent();
    			var roomReq = parent.children(".hilr-sched-room-req");
    			var schedInfo = parent.children(".hilr-sched-info");
				var collo = parent.children(".hilr-scheduling-collo");
    			var tipText = roomReq[0].innerText + " " + schedInfo[0].innerText + " " + collo[0].innerText;
    			if (tipText.trim().length > 1) {
					cell.attr("title", " ");
					cell.tooltip("option", "content", tipText);
    			}
    		}
    		tps.tooltip();
    		
    		
    	}
    	else if (viewId === 'catalog') {
			HILRCC.combineCourseNumbersWithTitle();
			    		
    		/* make a single paragraph for the course description and course info fields. */
    		var descriptions = jQuery("div." + HILRCC.stringTable.course_desc_class);
    		for (var i=0; i < descriptions.length; ++i) {
    			var desc = jQuery(descriptions[i]);
    			var info = jQuery(desc).next();
    			if (info.hasClass(HILRCC.stringTable.course_info_class)) {
    				var graf = jQuery(desc.children("p")[0]);
    				if (graf.length == 0) {
  						graf = jQuery(desc.find("td")[0])  
  					}
    				graf.html( graf.html() + " " + info.text() );
    				info.html("");
    			}
    		}
    		
    		/* insert headers for term and time slot */
    		var terms = ["Full Semester Courses",
    					 "First Half Six-Week Courses",
    					 "Second Half Six-Week Courses"];
    		
    		var sawFirstHalf = false, sawSecondHalf = false;
    		let items = jQuery("[id*=gv_list_]");
    		
			var durationHead = jQuery(document.createElement("div"));
			durationHead.addClass("hilr-catview-duration-header");
			durationHead.text(terms[0]);
			items[0].before(durationHead[0]);
			
			currentSlot = "";
			
			for (let i=0; i < items.length; ++i) {
				var item = items[i];
				var slot = jQuery(item).find(".hilr-catview-slot").text();
				var duration = jQuery(item).find(".hilr-catview-duration").text();
				
				if (!sawFirstHalf && (duration.indexOf("First Half") == 0)) {
					sawFirstHalf = true;
					durationHead = jQuery(document.createElement("div"));
					durationHead.addClass("hilr-catview-duration-header");
					durationHead.text(terms[1]);
					item.before(durationHead[0]);
					currentSlot = "";
				}
				if (!sawSecondHalf && (duration.indexOf("Second Half") == 0)) {
					sawSecondHalf = true;
					durationHead = jQuery(document.createElement("div"));
					durationHead.addClass("hilr-catview-duration-header");
					durationHead.text(terms[2]);
					item.before(durationHead[0]);
					currentSlot = "";
				}
				if (slot && (slot != currentSlot)) {
					currentSlot = slot;
					let slotDiv = jQuery(document.createElement("div"));
					slotDiv.addClass("hilr-catview-slot-header");
					slot = slot.replace("AM", "10 am–12 noon");
					slot = slot.replace("PM", "1 pm–3pm");
					slotDiv.text(slot);
					item.before(slotDiv[0]);
				}
			}
			/* Sometimes the RTE output renders as <table> elements instead of <p> elements.
			 * We need to get rid of these because they are treated differently in MS Word.
			 */
			
			var courseDescQuery = "." + HILRCC.stringTable.course_desc_class + ">table";
			var sglOneBioQuery = "." + HILRCC.stringTable.sgl_1_bio_class + ">table";
			var sglTwoBioQuery = "." + HILRCC.stringTable.sgl_2_bio_class + ">table";
			var needsFixing = jQuery(courseDescQuery).add(sglOneBioQuery).add(sglTwoBioQuery);
			
			for (var i=0; i < needsFixing.length; ++i) {
				var tbl = needsFixing[i];
				var cell = jQuery(tbl).find("td")[0];
				if (cell) {
					var markup = cell.innerHTML;
					var parent = tbl.parentElement;
					jQuery(tbl).remove();
					jQuery(parent).append("<p>" + markup + "</p>");
				}
			
			}
			/* set up the copy-to-clipboard button */
			jQuery("#hilr-copy-cat-to-clipboard-btn").click(HILRCC.copyGravityViewListViewToClipboard);
    	}

		/* convert timestamp (for last modify time) to formatted string */
		var tsCells = jQuery("td." + HILRCC.stringTable.last_mod_class);
		for (var j=0; j < tsCells.length; ++j){
			var cell = tsCells[j];
			var stamp = parseInt(jQuery(cell).html());
			var dateText = new Date(stamp).toLocaleString("en-US");
			if (dateText.indexOf("Invalid") !== 0) {
				/* remove seconds */
				var lastColon = dateText.lastIndexOf(":");
				var killText = dateText.slice(lastColon, lastColon+3);
				dateText = dateText.replace(killText, '');
				jQuery(cell).html(dateText);
			}
		}
    },
    
    copyGravityViewListViewToClipboard: function() {
		var range = document.createRange();
		var startElem = jQuery("div.gv-list-container")[0];
		var endElem = jQuery("#colophon")[0];
		range.setStart(startElem, 0);
		range.setEnd(endElem, 0);

		var selObj = window.getSelection()
		selObj.removeAllRanges();
		selObj.addRange(range);

		if (document.execCommand('copy')) {
			alert("Page copied. Use control-V or command-V to paste.");
			selObj.removeRange(range);
			return true;
		} else {
			alert('failed to set clipboard content');
			return false;
		}
	},
    
    /* both catalog view and at-a-glance view need this utility function */
    combineCourseNumbersWithTitle: function() {
		/* combine course number field with title string, throw away course number container */
		var courseNos = jQuery("h3.hilr-catview-course-number");
		for (var i=0; i < courseNos.length; ++i) {
			var jnum = jQuery(courseNos[i]);
			var titleDiv = jnum.next();
			titleDiv.html("<p><span class='hilr-catview-course-number'>" + jnum.text() + "</span> " + titleDiv.text() + "</p>");
			jnum.remove();
		}
    },
    
    isGravityFlowInboxEntry: function() {
    	if (window.location.href.indexOf("/index.php/inbox") >= 0) {
    		let queryParams = new URLSearchParams(window.location.search);
    		if (queryParams.has("view") && queryParams.get("view") == "entry" &&
    		    queryParams.has("id") && queryParams.has("lid"))
    		    return true;
    	}
    	return false;
    },

	removeHiddenFields: function() {
		var fieldsToRemove = [
			"last_mod_time",
			"Flexible half",
			"Readings catalog text",
			"suppress"
		];
		if ((window.location.href.indexOf("/inbox/") >= 0) ||
			(window.location.href.indexOf("workflow-status") >= 0)) {

			var cells = jQuery("td.entry-view-field-name");
			cells.each(function(index) {
				var thisText = jQuery(this).html();
				for (var i=0; i < fieldsToRemove.length; ++i) {
					if (thisText == fieldsToRemove[i]) {
						var parent = jQuery(this).parent();
						var nextRow = parent.next();
						parent.remove();
						nextRow.remove();
					}
				}
			});
		}
		
	},
	
	fixAllProposalsUrl: function() {
		var anch = jQuery(jQuery("#menu-item-598").children("a")[0]);
		var url = anch.attr("href");
		url += "?filter_" + HILRCC.stringTable.semester_field_id + "=" + encodeURIComponent(HILRCC.stringTable.current_semester) + "&mode=all"
		anch.attr("href", url);
	},
    
    /*
     * clone the submit button so that we can distinguish submit with/without
     * notification for the "Modification by Sponsor" step.
     */
    modifyInboxEntry: function() {
		var workflowBox = jQuery("#gravityflow-status-box-container");
		if (workflowBox.text().toLowerCase().indexOf("modification by sponsor") != -1) {
			HILRCC.setSuppressNotification(false);
			var buttonContainer = workflowBox.find(".gravityflow-action-buttons");
			var submitButton = buttonContainer.children("input")[0];
			var newButton = jQuery(submitButton).clone(true);
			buttonContainer.append(newButton);
			jQuery(submitButton).val("Done (send email)");
			newButton.val("Done");
			newButton.attr('disabled', false);
			newButton.attr('type', 'button');
			newButton.attr('onclick', '');
			
			newButton.click(function() {
					HILRCC.setSuppressNotification(true);
					jQuery(jQuery(".gravityflow-action-buttons").children("input")[0]).trigger('click');
				});
		}
		/* hide the 'suppress notify' input and other hidden inputs */
		jQuery("#" + HILRCC.stringTable.suppress_id).hide();
		jQuery("#" + HILRCC.stringTable.lastmod_id).hide();
		jQuery("#" + HILRCC.stringTable.flexhalf_id).hide();
		
    },
    
    /*
     * Control the setting of a hidden input that determines if the Notification of Changes step
     * is to be executed. Setting the hidden input to true suppresses the execution and avoids
     * sending the notifications.
     */
    setSuppressNotification: function(arg) {
		jQuery("[name='" + HILRCC.stringTable.suppress_input + "']").val(arg); /* boolean set/clear the suppress checkbox */
    },
    
    /*
     * Parse the current location on a GravityView page to obtain the entry id.
     * The redirect to the inbox (Gravity Flow) page for the entry.
     */
    goToWorkflowView: function() {
    	var pathElems = window.location.pathname.split("/");
    	var entryAt = jQuery.inArray("entry", pathElems);
    	if ((entryAt !== -1) && (entryAt < pathElems.length-1)) {
    		var id = pathElems[entryAt + 1];
    		
    		HILRCC.check_entry_assigned(id, function(isAssigned) {
  				if (isAssigned) {
  					HILRCC.goToInboxView(id);
				}
				else {
					HILRCC.goToAdministrativeView(id);
				}
			});
    	}
    },
    
    goToInboxView: function(id) {
		var newUrl = HILRCC.stringTable.siteURL + "inbox/?page=gravityflow-inbox"
			 + "&view=entry&id=" + HILRCC.stringTable.formId + "&lid="
			 + id;
		window.location.href = newUrl;
	},
    
    goToAdministrativeView: function(id) {
		var newUrl = HILRCC.stringTable.siteURL + "administrative/workflow-status/?page=gravityflow-inbox"
			 + "&view=entry&id=" + HILRCC.stringTable.formId + "&lid="
			 + id;
		window.location.href = newUrl;
	},
    
    
    /*
     * For the Save and Continue page, we want to pre-populate the email input with the
     * last SGL1 email entered into the form. This is saved in session storage when the
     * user clicks the "Save and Continue" button.
     */
    saveEmailSession: function() {
    	var sgl1EmailInput = jQuery("[name='" + HILRCC.stringTable.sgl1_email_name + "']");
    	if (sgl1EmailInput.length  == 1) {
    		var email = sgl1EmailInput.val();
    		if (email) {
    			window.sessionStorage.setItem("sgl1Email", email);
    		}
    	}
    },
    
    getEmailSession: function() {
    	return window.sessionStorage.getItem("sgl1Email");
    },
    
    setUpScheduleGrid: function() {
    	if (window.location.href.indexOf("/entry/") === -1) { /* don't show grid on single entry page */
    		
			jQuery(function() {
				jQuery( "#sched_grid_0" ).tabs();
			 });
			 
			 HILRCC.updateScheduleGrid();
         }
         else {
         	jQuery("#sched_grid_0").hide();
         }
	},
	
	addGridListeners: function() {
		 var bUsesImgs = true;
		 var cells = jQuery("#sched_grid_1 td img").add("#sched_grid_2 td img"); /* picks up emojis for checkmark and ! */
		 if (cells.length == 0) {
		    bUsesImgs = false;
			cells = jQuery("#sched_grid_1 td").add("#sched_grid_2 td").not(".hilr-room-name"); /* Firefox */
		 }
		 cells.attr('title', " ");
		 cells.tooltip();
		 for (var i=0; i < cells.length; ++i) {
		 	var cell = cells[i];
		 	var id = bUsesImgs ? cell.parentElement.id : cell.id;
			if (HILRCC.lastGridDataSet) {
				var courses = HILRCC.lastGridDataSet[id];
				if (courses && courses.length) {
					var s = "<ol>";
					for (let i=0; i < courses.length; ++i) {
						s += "<li>" + courses[i] + "</li>";
					}
					s += "</ol>";
					jQuery(cell).tooltip("option", "content", s);
				}
			 }
		 }
		 cells.tooltip();
	},
		
	updateScheduleGrid: function() {
        var data = {
				'action': 'get_sched_grid_data',
				'semester': HILRCC.getCurrentSemester()
		};
        jQuery.post(HILRCC.stringTable.ajaxURL, data, function(response) {
					HILRCC.populateScheduleGrid(response);
  			 		setTimeout(HILRCC.addGridListeners,1000);
		});
	},
    
    lastGridDataSet: null,
    
    populateScheduleGrid(response) {
    	jQuery("#sched_grid_1 td").not(".hilr-room-name").text("");
    	jQuery("#sched_grid_2 td").not(".hilr-room-name").text("");
    	
    	var len = response.length;
    	if (response[len-1] == '0') {
			response = response.substr(0, len-1)
		}
				
		var gridData = JSON.parse(response);
	
    	HILRCC.lastGridDataSet = gridData;
    	
		for (key in gridData) {
			var cellData = gridData[key];
			var cell = jQuery("#" + key);
			if (key.indexOf("Unassigned") !== -1) {
				cell.text("\u2753"); // question mark in Unassigned row
			}
			else if (cellData.length > 1) {
				cell.text("\u2757"); // exclamation point if multiple
			}
			else {
				cell.text("\u2714"); // otherwise checkmark
			}
		}
    
    },

	doCourseReport() {
		var bSchedOnly = false;
		var ckbox = jQuery("#report-sched-only-ckbox");
		if (ckbox) {
			bSchedOnly = ckbox.prop("checked")
		}
		var qString = "";
		if (bSchedOnly) {
			qString = "?sched=1";
		}
		window.location.replace("/index.php/course_report/" + qString);
	}
        
};
		
jQuery(window).ready(HILRCC.onLoad);


function InplaceCellEditor() {
	return {
		options: null,
		tcell: null,
		saveTitle: 'Save',
		cancelTitle: 'Cancel',
		onSaveJs: "",
		saveText: "",
		updateAjaxAction: "",
		onAjaxSuccess: null,
		
		create: function(td, props) {
			this.tcell = td;
			this.tcell.editor = this;
			
			for (key in props) {
				this[key] = props[key];
			}
			
			var markup = "<select>";
			if (this.options == null) {
				alert("InplaceCellEditor with no options");
				return;
			}
			for (var i=0; i < this.options.length; ++i) {
				markup += "<option>" + this.options[i] + "</option>";
			}
			markup += "</select>";
			markup += "<button title='" + this.saveTitle + "' class='hilr-slot-save-btn' onclick='javascript:"
			          + "this.editor.saveValue(this)" + "'>&#x2714;</button>";
			markup += "<button title='" + this.cancelTitle + "' class='hilr-slot-cancel-btn' onclick='javascript:"
			          + "this.editor.restore()" + "'>X</button>";
			          
			var jtd = jQuery(td)
			this.saveText = jtd.text();
			jtd.html(markup);
			jtd.children('select').val(this.saveText);
			var buttons = jtd.children('button');
			buttons[0].editor = this;
			buttons[1].editor = this;
		},
		
		saveValue: function(btn) {
			var parentRow = jQuery(btn).parent().parent();
			var id = parentRow.children(".hilr-scheduling-entryid").text();
			var val = jQuery(btn).parent().children("select").val()
			if (val === '—') {
				val = "";
			}
			var data = {
				'action': this.updateAjaxAction,
				'entry_id' : id,
				'value' : val
			};

			jQuery.post(HILRCC.stringTable.ajaxURL, data, function(response) {
				if (response.indexOf("SUCCESS") == 0) {
					var td = jQuery(btn).parent();
					var val = jQuery(td.children('select')[0]).val();
					td.html(val);
					if (td[0].editor.onAjaxSuccess)
						td[0].editor.onAjaxSuccess();
				}
				else  {
					alert("Sorry, there was a problem: " + response);
				}
			});
		 },
		
		restore: function() {
			jQuery(this.tcell).html(this.saveText);
		}
	};
}

/*
 * Get query string parameter/value
 * from https://stackoverflow.com/questions/901115/how-can-i-get-query-string-values-in-javascript
 * NOT IN USE CURRENTLY
 */
 /*
function getParameterByName(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}
*/

