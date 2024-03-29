<?php
function setup_HILR_settings_page(){
?>
	    <div class="wrap">
	    <h1>HILR Settings</h1>
	    <form method="post" action="options.php">
	        <?php
	            settings_fields("hilr-section");
	            do_settings_sections("theme-options");      
	            submit_button(); 
	        ?>
		</form>
		<form method="post" action="uploadhelp.php" enctype="multipart/form-data">
			<h3>Upload the Canvas help file</h3>
			<input type="file" id="canhelpfile" accept="text/*"/>
			<input type="submit"/>
		</form>
		</div>
	<?php
}

function add_HILR_menu_item()
{
	add_menu_page("HILR", "HILR Settings", "manage_options", "hilr-settings-panel", "setup_HILR_settings_page", null, 99);
}

add_action("admin_menu", "add_HILR_menu_item");


function display_submissions_enabled()
{
	?>
    	<input type="checkbox" name="submissions_enabled" id="submissions_enabled" value="1" <?php checked( '1', get_option( 'submissions_enabled' ))?> />
    <?php
}

function display_current_semester()
{
	?>
    	<input type="text" name="current_semester" id="current_semester" value="<?php echo get_option('current_semester'); ?>" />
    <?php
}

function display_HILR_settings()
{
	add_settings_section("hilr-section", "All Settings", null, "theme-options");
	
	add_settings_field("submissions_enabled", "Enable proposal submissions", "display_submissions_enabled", "theme-options", "hilr-section");
	add_settings_field("current_semester", "Current Semester", "display_current_semester", "theme-options", "hilr-section");

    register_setting("hilr-section", "submissions_enabled");
    register_setting("hilr-section", "current_semester");
}

add_action("admin_init", "display_HILR_settings");