<?php
function setup_HILR_settings_page(){
?>
	    <div class="wrap">
	    <h1>HILR Settings</h1>
	    <form method="post" action="options.php">
	        <?php
	            settings_fields("section");
	            do_settings_sections("theme-options");      
	            submit_button(); 
	        ?>          
	    </form>
		</div>
	<?php
}

function add_HILR_menu_item()
{
	add_menu_page("HILR", "HILR Settings", "manage_options", "hilr-settings-panel", "setup_HILR_settings_page", null, 99);
}

add_action("admin_menu", "add_HILR_menu_item");


function display_current_semester()
{
	?>
    	<input type="text" name="current_semester" id="current_semester" value="<?php echo get_option('current_semester'); ?>" />
    <?php
}

function display_starting_course_number()
{
	?>
    	<input type="text" name="starting_course_number" id="starting_course_number" value="<?php echo get_option('starting_course_number'); ?>" />
    <?php
}

function display_HILR_settings()
{
	add_settings_section("section", "All Settings", null, "theme-options");
	
	add_settings_field("current_semester", "Current Semester", "display_current_semester", "theme-options", "section");
    add_settings_field("starting_course_number", "Starting Course Number", "display_starting_course_number", "theme-options", "section");

    register_setting("section", "current_semester");
    register_setting("section", "starting_course_number");
}

add_action("admin_init", "display_HILR_settings");