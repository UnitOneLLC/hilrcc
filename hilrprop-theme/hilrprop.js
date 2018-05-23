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
	
	formIsDirty: false, /* flag used by unload handler */
	
	/*
	 * this function is invoked via jQuery when any page on the site loads.
	 */
	onLoad: function() {
		/* if this is the home page (i.e. the form), add a button to reset the form */
		if (window.location.pathname === "/" || window.location.pathname === "") {
			HILRCC.setupMainForm();
		}
	
		/* install the unload handler */
		var inputs = jQuery("input").add("textarea").add("select")
			.not("[type='hidden']")
		    .not("[class*='admin']")
		    .not("#gravityflow-note")
		    .not("#gentry_display_empty_fields")
		    .not("#gravityflow-admin-action");
		    
		if ( inputs.length !== 0 ) {
			jQuery(window).on("beforeunload", HILRCC.onUnload);
			inputs.change(function() {HILRCC.formIsDirty = true;});
			
			var submitBtn = jQuery("#gform_submit_button_" + HILRCC.stringTable.formId);
			if (submitBtn.length) {
				submitBtn.on("click", function() {HILRCC.formIsDirty = false;});
			}
			submitBtn = jQuery("#gravityflow_update_button");
			if (submitBtn.length) {
				submitBtn.on("click", function() {HILRCC.formIsDirty = false;});
			}
		}
	
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
    },
    
    isGravitySingleView: function() {
    	return (jQuery(".hilr-view-course-title").length == 1);
    },
    
    /*
     * initialization of the main form
     */
    setupMainForm: function() {
    	/* add a button to reset the form */
		var footer = jQuery(".gform_footer");
		if (footer.length == 1) {
			var resetBtn = document.createElement("button");
			jQuery(resetBtn).text("Reset form").attr("type", "reset").attr("id", "hilr_reset_btn");
			footer.append(resetBtn);
			jQuery(resetBtn).click(HILRCC.confirmClearForm);
		}
		
		/* hide the delayed start option from January through June */
		var today = new Date();
		if (today.getMonth() < 6) { /* Jan is 0 */
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
				HILRCC.formIsDirty = false;
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
    handle_add_comment: function() {
    	var text = jQuery("#hilr_comment_input").val();
    	var path = location.href.split('/');
    	var entryId = path[path.length-1];
    	if (!entryId) entryId = path[path.length-2];
    
    	var data = {
			'action': 'add_comment',
			'entryId' : entryId,
			'text': text
		};

		jQuery.post(HILRCC.stringTable.ajaxURL, data, function(response) {
			if (response.indexOf("SUCCESS") == 0) {
				HILRCC.formIsDirty = false;
				window.location.reload();
			}
			else if (response.indexOf("EMPTY") != 0) {
				alert("Sorry, there was a problem: " + response);
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
    		HILRCC.formIsDirty = false;
	    	textarea.val("");
    		window.history.back();
    	}
    },
    
    getCurrentSemester: function() {
		var today = new Date();
		var month = today.getMonth();
		var year = today.getYear() + 1900;
		var comingSeason = "";
		if (month <= 6)
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
    
    onUnload: function() {
    	if (HILRCC.formIsDirty) {
    		return "You have unsaved input -- click Cancel to stay on page";
    	}
    	else {
    		return undefined;
    	}
    },
    
    confirmClearForm: function() {
    	HILRCC.formIsDirty = false;
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
        else if (viewId == 'all-proposals-single') {
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
    	else if (viewId === 'at-a-glance') {
    	
			HILRCC.combineCourseNumbersWithTitle();
			
			/* inject css classes for shading */
    		var items = jQuery(".gv-list-view");
    		var currentSlot = "";
    		for (var i=0; i < items.length; ++i) {
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
    					jQuery(headerDiv).text(slot);
    					item.before(headerDiv);
    				}
    			}
    		}
    	}
    	else if (viewId === 'scheduling') {
    	
    		/* allow a little more width */
    		jQuery("#primary").css("max-width", "1400px");
    	
    		/* install in-place editors for slot, size, room, and duration columns */
    		
    		jQuery("td." + HILRCC.stringTable.slot_cell_class).dblclick(
    		  	function() {
					var props = {
						options: ["Monday AM", "Monday PM", "Tuesday AM", "Tuesday PM",
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
						options: ["12", "18", "20", "22", "25"],
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
						options: HILRCC.stringTable.room_list.split(','),
						updateAjaxAction: "update_room",
						onAjaxSuccess: HILRCC.updateScheduleGrid
     				};
    				(new InplaceCellEditor()).create(this, props);
    			}
    		);
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
    				graf.html( graf.html() + " " + info.text() );
    				info.html("");
    			}
    		}
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
			jQuery(submitButton).val("Submit with notifications");
			newButton.val("Submit without notifications");
			newButton.attr('disabled', false);
			
			newButton.click(function() {
					HILRCC.setSuppressNotification(true);
					jQuery(jQuery(".gravityflow-action-buttons").children("input")[0]).trigger('click');
				});
		}
		/* hide the 'suppress notify' input */
		jQuery("#" + HILRCC.stringTable.suppress_id).hide();
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
		 cells = jQuery("#sched_grid_1 td img").add("#sched_grid_2 td img").not(".hilr-room-name"); /* picks up emojis */
		 cells.on("mouseover", function(e) {
			var id = e.target.parentElement.id;
			if (HILRCC.lastGridDataSet) {
				var courses = HILRCC.lastGridDataSet[id];
				if (courses && courses.length) {
					var s = "";
					var target = jQuery("#hilr_course_names");
					for (let i=0; i < courses.length; ++i) {
						s += courses[i] + "<br>";
					}
					target.html(s);
				}
			 }
		 });
		 cells.on("mouseout", function(e) {
			var id = e.target.id;
			jQuery("#hilr_course_names").html("");
		 });
	},
		
	updateScheduleGrid: function() {
        var data = {
				'action': 'get_sched_grid_data'
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
			if (cellData.length > 1) {
				cell.text("\u2757");
			}
			else {
				cell.text("\u2714");
			}
		}    
    
    }
        
};
		
jQuery(document).ready(HILRCC.onLoad);


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



