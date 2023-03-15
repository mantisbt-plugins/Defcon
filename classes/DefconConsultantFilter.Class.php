<?PHP
class DefconConsultantFilter extends MantisFilter {
	public $field = 'consultant';
	public $title = 'Consultant';
	public $type = FILTER_TYPE_MULTI_STRING;
	public $default = array();

	public function query( $p_filter_input ) {
		if ( !is_array( $p_filter_input ) ) {
			return;
		}

		$t_query = array(
			'join'  => "LEFT JOIN  {plugin_Defcon_issue} as Z1 ON {bug}.id= Z1.issue_id",
			'where' => "Z1.consultant_id=$p_filter_input[0]",
			);
		return $t_query;
	}

	public function display( $p_filter_value ) {
		$t_options = $this->options();

		if ( isset( $t_options[ $p_filter_value ] ) ) {
			return $t_options[ $p_filter_value ];
		}

		return $p_filter_value;
	}

	public function options() {
		static $s_options = null;
		// we need to add filter on project_id and affected relations to speed up the filtering
		$project = helper_get_current_project();
		if ($project == 0){
			$t_query  = "SELECT consultant_id,username FROM {plugin_Defcon_issue},{user} where {user}.id={plugin_Defcon_issue}.consultant_id order by username ";
		} else{
			$t_query  = "SELECT consultant_id,username FROM {plugin_Defcon_issue},{user},{bug} where {user}.id={plugin_Defcon_issue}.consultant_id  and {bug}.id={plugin_Defcon_issue}.issue_id and {bug}.project_id=$project order by username ";
		}
		$t_result = db_query( $t_query );
		$s_options = array();
		while ( $t_row = db_fetch_array( $t_result ) ) {
			$s_options[ "'".$t_row['consultant_id']."'" ] = $t_row['username'];
//			.' ('.$t_row['consultant_id'].')'
		}	
		return $s_options;
	}

}