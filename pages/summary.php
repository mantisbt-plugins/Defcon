<?php
layout_page_header();
layout_page_begin( 'summary_page.php' );

$t_filter = summary_get_filter();

print_summary_menu( 'summary_page.php', $t_filter );
print_summary_submenu( 'page1' );

$t_summary_header_arr = explode( '/', lang_get( 'summary_header' ) );

$t_summary_header = '';
foreach ( $t_summary_header_arr as $t_summary_header_name ) {
	$t_summary_header .= '<th class="align-right">';
	$t_summary_header .= $t_summary_header_name;
	$t_summary_header .= '</th>';
}

/**
 * print bug counts by assigned to each Consultant.
 * A filter can be used to limit the visibility.
 *
 * @param array $p_filter Filter array.
 * @return void
 */
function summary_print_by_consultant( array $p_filter = null ) {
	$t_project_id = helper_get_current_project();

	$t_specific_where = helper_project_specific_where( $t_project_id );
	if( ' 1<>1' == $t_specific_where ) {
		return;
	}

	$t_query = new DBQuery();
	$t_sql = 'SELECT COUNT({bug}.id) as bugcount, consultant_id, status'
		. ' FROM {bug},{plugin_Defcon_issue} WHERE {bug}.id = {plugin_Defcon_issue}.issue_id and consultant_id>0 AND ' . $t_specific_where;
	if( !empty( $p_filter ) ) {
		$t_subquery = filter_cache_subquery( $p_filter );
		$t_sql .= ' AND {bug}.id IN :filter';
		$t_query->bind( 'filter', $t_subquery );
	}
	$t_sql .= ' GROUP BY consultant_id, status'
		. ' ORDER BY consultant_id, status';
	$t_query->sql( $t_sql );

	$t_summaryusers = array();
	$t_cache = array();
	$t_bugs_total_count = 0;

	while( $t_row = $t_query->fetch() ) {
		$t_summaryusers[] = $t_row['consultant_id'];
		$t_status = $t_row['status'];
		$t_bugcount = $t_row['bugcount'];
		$t_bugs_total_count += $t_bugcount;
		$t_label = $t_row['consultant_id'];

		summary_helper_build_bugcount( $t_cache, $t_label, $t_status, $t_bugcount );
	}

	user_cache_array_rows( array_unique( $t_summaryusers ) );

	foreach( $t_cache as $t_label => $t_item) {
		# Build up the hyperlinks to bug views
		$t_bugs_open = isset( $t_item['open'] ) ? $t_item['open'] : 0;
		$t_bugs_resolved = isset( $t_item['resolved'] ) ? $t_item['resolved'] : 0;
		$t_bugs_closed = isset( $t_item['closed'] ) ? $t_item['closed'] : 0;
		$t_bugs_total = $t_bugs_open + $t_bugs_resolved + $t_bugs_closed;
		$t_bugs_ratio = summary_helper_get_bugratio( $t_bugs_open, $t_bugs_resolved, $t_bugs_closed, $t_bugs_total_count);

		$t_link_prefix = summary_get_link_prefix( $p_filter );

		$t_bug_link = $t_link_prefix . '&amp;' . FILTER_PROPERTY_HANDLER_ID . '=' . $t_label;
		$t_label = summary_helper_get_developer_label( $t_label, $p_filter );
		summary_helper_build_buglinks( $t_bug_link, $t_bugs_open, $t_bugs_resolved, $t_bugs_closed, $t_bugs_total );
		summary_helper_print_row( $t_label, $t_bugs_open, $t_bugs_resolved, $t_bugs_closed, $t_bugs_total, $t_bugs_ratio[0], $t_bugs_ratio[1] );
	}
}
?>

<div class="col-md-12 col-xs-12">
<div class="space-10"></div>

<div class="widget-box widget-color-blue2">
<div class="widget-header widget-header-small">
	<h4 class="widget-title lighter">
		<?php print_icon( 'fa-bar-chart-o', 'ace-icon' ); ?>
		Consultant Stats
	</h4>
</div>

<div class="widget-body">
<div class="widget-main no-padding">
	<!-- Consultant STATS -->
	<div class="space-10"></div>
	<div class="widget-box table-responsive">
		<table class="table table-hover table-bordered table-condensed table-striped">
		<thead>
			<tr>
				<th>Consultant</th>
				<?php echo $t_summary_header ?>
			</tr>
		</thead>
		<?php summary_print_by_consultant( $t_filter ) ?>
	</table>
	</div>
<?php
layout_page_end();
