<?php
layout_page_header();
layout_page_begin( 'summary_page.php' );

$t_filter = summary_get_filter();

print_summary_menu( 'consultant_graph.php', $t_filter );

# Submenu
plugin_push_current( 'MantisGraph');
$t_mantisgraph = plugin_get();
$t_mantisgraph->print_submenu();
plugin_pop_current();
?>

<div class="col-md-12 col-xs-12">
	<div class="space-10"></div>
	<div class="widget-box widget-color-blue2">
		<div class="widget-header widget-header-small">
			<h4 class="widget-title lighter">
				<?php print_icon( 'fa-bar-chart-o', 'ace-icon' ); ?>
				<?php echo 'By Consultant Graphs' ?>
			</h4>
		</div>

		<div class="col-md-6 col-xs-12" style="padding: 20px;">
			<div class="widget-header widget-header-small">
				<h4 class="widget-title lighter">
					<?php print_icon( 'fa-bar-chart', 'ace-icon' ); ?>
					<?php echo 'Top Consultants by Fixed Issues' ?>
				</h4>
			</div>

<?php
			$t_metrics = create_consultant_resolved_summary( $t_filter );
			graph_bar( $t_metrics );
?>
		</div>

		<div class="col-md-6 col-xs-12" style="padding: 20px;">
			<div class="widget-header widget-header-small">
				<h4 class="widget-title lighter">
					<?php print_icon( 'fa-bar-chart', 'ace-icon' ); ?>
					<?php echo 'Consultants by Open issues' ?>
				</h4>
			</div>

<?php
			$t_metrics = create_consultant_open_summary( $t_filter );
			graph_bar( $t_metrics );
?>
		</div>
	</div>
</div>

<?php
layout_page_end();

/**
 * Create summary for issues resolved by a developer
 * @param array $p_filter Filter array.
 * @return array with key being username and value being # of issues fixed.
 */
function create_consultant_resolved_summary( array $p_filter = null ) {
	$t_project_id = helper_get_current_project();
	$t_user_id = auth_get_current_user_id();
	$t_specific_where = helper_project_specific_where( $t_project_id, $t_user_id );
	$t_resolved_status_threshold = config_get( 'bug_resolved_status_threshold' );

	$t_query = new DBQuery();
	$t_sql = 'SELECT consultant_id, count(*) as count FROM {bug},{plugin_Defcon_issue} WHERE ' . $t_specific_where
		. ' AND {bug}.id = {plugin_Defcon_issue}.issue_id AND consultant_id <> :nouser AND status >= :status_resolved AND resolution = :resolution_fixed';
	if( !empty( $p_filter ) ) {
		$t_subquery = filter_cache_subquery( $p_filter );
		$t_sql .= ' AND {bug}.id IN :filter';
		$t_query->bind( 'filter', $t_subquery );
	}
	$t_sql .= ' GROUP BY consultant_id ORDER BY count DESC';
	$t_query->sql( $t_sql );
	$t_query->bind( array(
		'nouser' => NO_USER,
		'status_resolved' => (int)$t_resolved_status_threshold,
		'resolution_fixed' => FIXED
		) );
	$t_query->set_limit( 20 );

	$t_handler_array = array();
	$t_handler_ids = array();
	while( $t_row = $t_query->fetch() ) {
		$t_handler_array[$t_row['consultant_id']] = (int)$t_row['count'];
		$t_handler_ids[] = $t_row['consultant_id'];
	}

	if( count( $t_handler_array ) == 0 ) {
		return array();
	}

	user_cache_array_rows( $t_handler_ids );

	foreach( $t_handler_array as $t_consyultant_id => $t_count ) {
		$t_metrics[user_get_name( $t_consultant_id )] = $t_count;
	}

	arsort( $t_metrics );

	return $t_metrics;
}

/**
 * Create summary for issues opened by a developer
 * @param array $p_filter Filter array.
 * @return array with key being username and value being # of issues fixed.
 */
function create_consultant_open_summary( array $p_filter = null ) {
	$t_project_id = helper_get_current_project();
	$t_user_id = auth_get_current_user_id();
	$t_specific_where = helper_project_specific_where( $t_project_id, $t_user_id );
	$t_resolved_status_threshold = config_get( 'bug_resolved_status_threshold' );

	$t_query = new DBQuery();
	$t_sql = 'SELECT consultant_id, count(*) as count FROM {bug},{plugin_Defcon_issue} WHERE ' . $t_specific_where
		. ' AND {bug}.id = {plugin_Defcon_issue}.issue_id AND consultant_id <> :nouser AND status < :status_resolved';
	if( !empty( $p_filter ) ) {
		$t_subquery = filter_cache_subquery( $p_filter );
		$t_sql .= ' AND {bug}.id IN :filter';
		$t_query->bind( 'filter', $t_subquery );
	}
	$t_sql .= ' GROUP BY consultant_id ORDER BY count DESC';
	$t_query->sql( $t_sql );
	$t_query->bind( array(
		'nouser' => NO_USER,
		'status_resolved' => (int)$t_resolved_status_threshold
		) );

	$t_handler_array = array();
	$t_handler_ids = array();
	while( $t_row = $t_query->fetch() ) {
		$t_handler_array[$t_row['consultant_id']] = (int)$t_row['count'];
		$t_handler_ids[] = $t_row['consultant_id'];
	}

	if( count( $t_handler_array ) == 0 ) {
		return array();
	}

	user_cache_array_rows( $t_handler_ids );

	foreach( $t_handler_array as $t_consultant_id => $t_count ) {
		$t_metrics[user_get_name( $t_consultant_id )] = $t_count;
	}

	arsort( $t_metrics );

	return $t_metrics;
}
