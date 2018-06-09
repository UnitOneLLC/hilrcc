<?php
# HILR Course Proposals app (server side).
# Copyright (c) 2018 HILR
# Author: Frederick Hewett
#
# This module serves the drafts page. Note that Gravity Forms does not
# expose its table names, so this code is inherently fragile. It should
# be simple to repair any version-related instability by using the
# Database Browser plug-in to recover the updated table and column names.
#
add_shortcode('HILRCC_drafts_table', 'HILRCC_emit_drafts_table');
function HILRCC_emit_drafts_table()
{
  // restrict access to admins
  $user = wp_get_current_user();
  if ( !in_array('cc_admin', (array) $user->roles) and
	   !in_array('administrator', (array) $user->roles)) {
	   return;
  }

  global $wpdb;
  
  $table = $wpdb->prefix . 'gf_draft_submissions';
  $query = 'SELECT uuid,email,date_created FROM ' . $table;
  $drafts = $wpdb->get_results($query, ARRAY_A);
  /* descending date sort */
  usort($drafts, function($arow, $brow) {
    if ($arow['date_created'] > $brow['date_created'])
      return -1;
    else if ($arow['date_created'] < $brow['date_created'])
      return 1;
    return 0;
  });

?>

<div id="drafts_div">
<table id='drafts_table'>
	<tbody>
		<tr>
			<th>Key</th><th>email</th><th>Created</th>
		</tr>
<?php
	foreach($drafts as &$draft) {
	  $uuid = $draft['uuid'];
	  $link_url = "http://ccsubmit.com/?gf_token=" . $uuid;
	  $email = $draft['email'];
	  $created = $draft['date_created'];
?>
		<tr>
			<td><a href='<?php echo"$link_url"; ?>'><?php echo "$uuid"; ?></a></td>
			<td><?php echo "$email"; ?></td>
			<td><?php echo "$created" ?></td>
		</tr>
<?php
	}
?>
	</tbody>
</table>

</div>
<?php
}
?>