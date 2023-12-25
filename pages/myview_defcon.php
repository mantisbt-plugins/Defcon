<?php	
########################################################
# Mantis Bugtracker Plugin Defcon
#
# By Cas Nuy  www.nuy.info 2023
# To be used with Mantis 2.00 and above
#
########################################################
global $t_url_link_parameters;
global $t_bug_class;
global $t_update_bug_threshold;
global $t_filter;
$user 			= auth_get_current_user_id();
# what is the table for Defcon
$defcon_table	= plugin_table('issue');
$bug_table = "mantis_bug_table";
$t_status= plugin_config_get( 'myview_status' );
$query = "SELECT a.* FROM {plugin_Defcon_issue} b, {bug} a  WHERE b.issue_id = a.id and  consultant_id = $user  and status < $t_status ORDER BY a.id DESC";
$result = db_query($query);
$t_bug_count=db_num_rows($result);

if ($t_bug_count===0) {
	return;
}

$t_box_title_label = plugin_lang_get( 'my_issues' );
$t_box_title = 'defcon';
$t_collapse_block = is_collapsed( $t_box_title );
$t_block_css = $t_collapse_block ? 'collapsed' : '';
$t_block_icon = $t_collapse_block ? 'fa-chevron-down' : 'fa-chevron-up';

$t_bug_string = $t_bug_count == 1 ? 'bug' : 'bugs';

# -- ====================== BUG LIST ========================= --
?>
<div>
<div id="<?php echo $t_box_title ?>" class="widget-box widget-color-blue2 <?php echo $t_block_css ?>">
	<div class="widget-header widget-header-small">
		<h4 class="widget-title lighter">
			<?php print_icon( 'fa-list-alt', 'ace-icon' ); ?>
			<?php
			#-- Box title
echo $t_box_title_label;
# -- Viewing range info
$v_start =  1;
$v_end = $v_start + $t_bug_count - 1;
echo '<span class="badge"> ' . " $v_start - $v_end / $t_bug_count " . ' </span>';
?>

		</h4>
		<div class="widget-toolbar">
			<a data-action="collapse" href="#">
				<?php print_icon( $t_block_icon, '1 ace-icon bigger-125' ); ?>
			</a>
		</div>
	</div>

	<div class="widget-body">
		<div class="widget-main no-padding">
			<div class="table-responsive">
				<table class="table table-bordered table-condensed table-striped table-hover">
<tbody>
<?php


while ($t_bug  = db_fetch_array($result)) {

	$t_summary = string_display_line_links( $t_bug['summary'] );
	$t_last_updated = date( config_get( 'normal_date_format' ), $t_bug['last_updated'] );

	# Check for attachments
	$t_attachment_count = 0;
	# TODO: factor in the allow_view_own_attachments configuration option
	# instead of just using a global check.
	if( ( file_can_view_bug_attachments( $t_bug['id'], null ) ) ) {
		$t_attachment_count = file_bug_attachment_count( $t_bug['id'] );
	}

	# grab the project name
	$project_name = project_get_field( $t_bug['project_id'], 'name' );


	?>

<tr class="my-buglist-bug <?php echo $t_bug_class?>">
	<?php
	# -- Bug ID and details link + Pencil shortcut --?>
	<td class="nowrap width-13 my-buglist-id">
		<?php
			print_bug_link( $t_bug['id'], false );

			echo '<br />';

			# choose color based on status
			$t_status_css = html_get_status_css_fg( $t_bug['status'], auth_get_current_user_id(), $t_bug['project_id'] );
			$t_status = string_attribute( get_enum_element( 'status', bug_get_field( $t_bug['id'], 'status' ), $t_bug['project_id'] ) );
			print_icon( 'fa-square', 'fa-status-box ' . $t_status_css, $t_status );
			echo ' ';

			if( !bug_is_readonly( $t_bug['id']) && access_has_bug_level( $t_update_bug_threshold, $t_bug['id'] ) ) {
				echo '<a class="edit" href="' . string_get_bug_update_url( $t_bug['id'] ) . '">';
				print_icon( 'fa-pencil', 'bigger-130 padding-2 grey', lang_get( 'edit' ) );
				echo '</a>';
			}

			if( ON == config_get( 'show_priority_text' ) ) {
				print_formatted_priority_string( $t_bug );
			} else {
				print_status_icon( $t_bug['priority'] );
			}

			if( $t_attachment_count > 0 ) {
				$t_href = string_get_bug_view_url( $t_bug['id']  ) . '#attachments';
				$t_href_title = sprintf( lang_get( 'view_attachments_for_issue' ), $t_attachment_count, $t_bug['id'] );
				$t_alt_text = $t_attachment_count . lang_get( 'word_separator' ) . lang_get( 'attachments' );
				echo '<a class="attachments" href="' . $t_href . '" title="' . $t_href_title . '"> ';
				print_icon( 'fa-paperclip', 'fa-lg grey', $t_alt_text );
				echo '</a>';
			}

			?>
	</td>

	<?php
	# -- Summary --?>
	<td>
		<?php
			if( ON == config_get( 'show_bug_project_links' ) && helper_get_current_project() != $t_bug['project_id'] ) {
				echo '<span>[', string_display_line( project_get_name( $t_bug['project_id'] ) ), '] </span>';
			}
			$t_bug_url = string_get_bug_view_url( $t_bug['id'] );
			echo '<span><a href="' . $t_bug_url . '">' . $t_summary . '</a></span><br />';
	?>
		<?php
	# type project name if viewing 'all projects' or bug is in subproject
	echo '<span class="small">', string_display_line( category_full_name( $t_bug['category_id'], true, $t_bug['project_id'] ) ), '</span>';

	echo '<span class="small"> - ';
	echo $t_last_updated;

	echo '</span>';
	?>
	</td>
</tr>
<?php
	# -- end of Repeating bug row --
	
}

# -- ====================== end of BUG LIST ========================= --

?>
</tbody>
</table>
</div>
</div>
</div>
</div>
</div>
<?php
# Free the memory allocated for the rows in this box since it is not longer needed.
unset( $t_rows );
echo "<br>";
