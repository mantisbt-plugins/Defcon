<?php

class DefconConsultantColumn extends MantisColumn {

	public $column = "consultant";
	public $sortable = true;

	private $cache = array();

	public function __construct() {
		plugin_push_current( 'Defcon' );
		$this->title = plugin_lang_get( 'consultant','Defcon');
		plugin_pop_current();
	}

	public function cache( $p_bugs ) {
		if ( count( $p_bugs ) < 1 ) {
			return;
		}

		$t_bug_ids = array();
		foreach ( $p_bugs as $t_bug ) {
			$t_bug_ids[] = $t_bug->id;
		}

		$t_bug_ids = implode( ',', $t_bug_ids );

		$t_query  = "SELECT issue_id as bug_id, username FROM {plugin_Defcon_issue} as a, {user} as b WHERE issue_id IN ( $t_bug_ids ) AND b.id=a.consultant_id ";


		$t_result = db_query( $t_query );

		while ( $t_row = db_fetch_array( $t_result ) ) {
			$this->cache[$t_row['bug_id']] = $t_row['username'];
		}
	}

	public function display( $p_bug, $p_columns_target ) {
		if ( isset( $this->cache[$p_bug->id] ) ) {
			 plugin_push_current( 'Defcon' );
				echo $this->cache[$p_bug->id];
				 plugin_pop_current();
		}
	}

	public function sortquery( $p_dir ) {
		return array(
	'join' => "LEFT JOIN {plugin_Defcon_issue} ON {plugin_Defcon_issue}.issue_id = {bug}.id LEFT join {user} on {user}.id = {plugin_Defcon_issue}.consultant_id",
	'order' => "{user}.username $p_dir",
		);
	}

}
