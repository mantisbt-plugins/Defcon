<?php
/**
 * Defcon plugin for MantisBT
 * https://github.com/mantisbt-plugins/Defcon
 *
 * Copyright (c) 2022  Cas Nuy
 *
 * This file is part of the Defcon plugin for MantisBT.
 *
 * Defcon is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class Defconplugin extends MantisPlugin {

	function register() {
		$this->name = plugin_lang_get( 'title' );
		$this->description = plugin_lang_get( 'description' );
		$this->page = 'config';
		$this->version = '2.1.1';
		$this->requires = array( 'MantisCore' => '2.0.0', );
		$this->author = 'Cas Nuy';
		$this->contact = 'cas@nuy.info';
		$this->url = 'https://github.com/mantisbt-plugins/Defcon/';
	}

	function config() {
		return array(
			'update_threshold' => DEVELOPER,
			'myview_status' => 90,
			'backup_consultant' => 1,
			'role' => 1,
			);
	}


	function init() {
		// we need an event in the my_view_page
		event_declare('EVENT_MYVIEW');
		
		// show the field when creating a project
		plugin_event_hook( 'EVENT_MANAGE_PROJECT_CREATE_FORM' , 'defcon_create_project' );
		plugin_event_hook( 'EVENT_MANAGE_PROJECT_CREATE' , 'defcon_create_project2' );	
		// show the field when updating a project
		plugin_event_hook( 'EVENT_MANAGE_PROJECT_UPDATE_FORM' , 'defcon_update_project' );
		plugin_event_hook( 'EVENT_MANAGE_PROJECT_UPDATE' , 'defcon_update_project2' );
		// manage the field when deleting a project
		plugin_event_hook( 'EVENT_MANAGE_PROJECT_DELETE' , 'defcon_delete_project' );
		// show the field when viewing a project
		// this is adjusted in manaProject_page.php

		// make the field visible as column (allocated consultant @ issue level)
		plugin_event_hook( 'EVENT_FILTER_FIELDS' , 'defcon_field_filter' );
		// make the field filterable (allocated consultant @ issue level)
		plugin_event_hook( 'EVENT_FILTER_COLUMNS' , 'defcon_field_columns' );

		// show the field when reporting an issue
		// the defined consultant will be automatically assigned during issue creation 
		// In case no default value is available, issue will be assigned to the administrator
		// auto-update the Default Project Consultant
		plugin_event_hook( 'EVENT_REPORT_BUG_FORM', 'defcon_create_issue' );
		plugin_event_hook( 'EVENT_REPORT_BUG', 'defcon_create_issue2' );
		// show the field when updating an issue
		plugin_event_hook( 'EVENT_UPDATE_BUG_FORM', 'defcon_update_issue' );
		plugin_event_hook( 'EVENT_UPDATE_BUG', 'defcon_update_issue2' );
		// show the field when updating an issue
		plugin_event_hook( 'EVENT_VIEW_BUG_DETAILS', 'defcon_view_issue' );
		// manage the plugin dsata when deleting an issue
		plugin_event_hook( 'EVENT_BUG_DELETED', 'defcon_delete_issue' );
		
		//Show where the user is defined as primary consultant in the my_view page
		plugin_event_hook( 'EVENT_MYVIEW', 'defcon_myview' );
	}


	function defcon_field_filter($p_event){
		require_once( 'classes/DefconConsultantFilter.Class.php' );
		return array( 'DefconConsultantFilter', );  
		}
	
	function defcon_field_columns() {
		require_once( 'classes/DefconConsultantColumn.Class.php' );
		return array('DefconConsultantColumn',);
	} 

function defcon_create_project( $p_event, $t_project_id ) {
		echo '<tr class="spacer"><td colspan="6"></td></tr>';
		echo '<tr class="hidden"></tr>';
?>
		<tr>
			<th class="category">
				<label for="reporter_id">
					<?php echo plugin_lang_get( 'consultant','Defcon' ) ?>
				</label>
			</th>
			<td>
				<select id="con_id" name="con_id"
					<?php echo helper_get_tab_index() ?>
					class="autofocus input-sm">
				<?php 
					print_assign_to_option_list( 0,0 ) ;
				?>
				</select>
			</td>
		</tr>
		<tr>
			<th class="category">
				<label for="mail">
					<?php echo plugin_lang_get( 'mail','Defcon' ) ?>
				</label>
			</th>
			<td>
				<input type="text" name="mail" size="100" maxlength="100" autocomplete='none'>
			</td>
		</tr>		
<?php
	}

function defcon_create_project2( $p_event, $t_project_id ) {
		$t_consultant_id	= intval(gpc_get_string( 'con_id'));
		$t_mail			  	= gpc_get_string( 'mail' );

		// first update the plugin table
		$query = "select * from {plugin_Defcon_project} WHERE project_id= $t_project_id";
		$result = db_query($query);
		$count= db_num_rows($result);
		if ($count>0){
			$query = "update {plugin_Defcon_project} SET consultant_id=$t_consultant_id, generalmail = '$t_mail' WHERE project_id= $t_project_id";
			$result = db_query($query);
		} else{
            $query = "INSERT INTO {plugin_Defcon_project} (project_id,consultant_id,generalmail) values ($t_project_id, $t_consultant_id, '$t_mail')";
			$result = db_query($query);
		}
		// next update the mantis_project_user_list_table in case role is set to be Manager
		// Then consultant needs to be assigned Manager rights for this project ( ex-consultant will keep the existing Manager level)
		// In case roile is set to be Monitor, all is handled @ issue level
		$f_role = plugin_config_get( 'backup_consultant' );
		if ($f_role == 0){
			$query = "select * from {project_user_list} WHERE user_id= $t_consultant_id and project_id=$t_project_id";
			$result = db_query($query);
			$count= db_num_rows($result);
			if ($count>0){
				$query = "update {project_user_list} SET access_level=70 WHERE user_id= $t_consultant_id and project_id=$t_project_id";
				$result = db_query($query);
			} else{
				$query = "INSERT INTO {project_user_list} (project_id, user_id,access_level) values ($t_project_id, $t_consultant_id,70)";
				$result = db_query($query);
			}		
		}
	}

function defcon_update_project( $p_event, $t_project_id ) {
			$query = "select consultant_id,generalmail from {plugin_Defcon_project} WHERE project_id= $t_project_id";
			$result = db_query($query);
			if ($result){
				$row = db_fetch_array($result);
				if($row){
					$t_consultant_id = $row['consultant_id'];
					$t_mail = $row['generalmail'];
				} else {
					$t_consultant_id = plugin_config_get( 'backup_consultant' ) ;
					$t_mail = "";
				}
			}

		echo '<tr class="spacer"><td colspan="6"></td></tr>';
		echo '<tr class="hidden"></tr>';
?>
		<tr>
			<th class="category">
				<label for="reporter_id">
					<?php echo plugin_lang_get( 'consultant','Defcon' ) ?>
				</label>
			</th>
			<td>
				<select id="con_id" name="con_id"
					<?php echo helper_get_tab_index() ?>
					class="autofocus input-sm">
				<?php 
					print_assign_to_option_list( intval($t_consultant_id),intval($t_project_id) ) ;
				?>
				</select>
			</td>
		</tr>
				<tr>
			<th class="category">
				<label for="mail">
					<?php echo plugin_lang_get( 'mail','Defcon' ) ?>
				</label>
			</th>
			<td>
				<input type="text" name="mail" size="100" maxlength="100" value="<?php echo $t_mail ?>" autocomplete='none'>
			</td>
		</tr>
		
<?php
		echo '<tr class="spacer"><td colspan="6"></td></tr>';
		echo '<tr class="hidden"></tr>';
	}

function defcon_update_project2( $p_event, $t_project_id ) {
		$t_consultant_id = intval(gpc_get_string( 'con_id'));
		$t_mail			  	= gpc_get_string( 'mail' );
	
		// first update the plugin table
		$query = "select * from {plugin_Defcon_project} WHERE project_id= $t_project_id ";
		$result = db_query($query);
		$count= db_num_rows($result);
		if ($count>0){
			$query = "update {plugin_Defcon_project} SET consultant_id=$t_consultant_id, generalmail='$t_mail' WHERE project_id= $t_project_id";
			$result = db_query($query);
		} else{
            $query = "INSERT INTO {plugin_Defcon_project} (project_id,consultant_id, generalmail) values ($t_project_id, $t_consultant_id, '$t_mail')";
			$result = db_query($query);
		}
		// next update the mantis_project_user_list_table in case role is set to be Manager
		// Then consultant needs to be assigned Manager rights for this project ( ex-consultant will keep the existing Manager level)
		// In case roile is set to be Monitor, all is handled @ issue level
		$f_role = plugin_config_get( 'backup_consultant' );
		if ($f_role == 0){
			$query = "select * from {project_user_list} WHERE user_id= $t_consultant_id and project_id=$t_project_id";
			$result = db_query($query);
			$count= db_num_rows($result);
			if ($count>0){
				$query = "update {project_user_list} SET access_level=70 WHERE user_id= $t_consultant_id and project_id=$t_project_id";
				$result = db_query($query);
			} else{
				$query = "INSERT INTO {project_user_list} (project_id, user_id,access_level) values ($t_project_id, $t_consultant_id,70)";
				$result = db_query($query);
			}		
		}		
	}

	function defcon_delete_project($p_event,$t_project_id) {
		$query = "delete from {plugin_Defcon_project} WHERE project_id= $t_project_id";
		$result = db_query($query);
		return;
	}
	
	function defcon_create_issue( $p_event, $t_project_id ) {

		# allow to change Concultant_id (if access level is higher than defined)
		$t_user_id = auth_get_current_user_id();
		$t_access_level = user_get_access_level( $t_user_id, $t_project_id );
		$query1 = "Select consultant_id from {plugin_Defcon_project} where project_id=$t_project_id";
		$result1 = db_query($query1);
		if ($result1){
			$row1 = db_fetch_array($result1);
			if ($row1){
				$t_consultant_id = $row1['consultant_id'];
			} else {
				$t_consultant_id = plugin_config_get( 'backup_consultant' );
			}
		}
		$con_id = $t_consultant_id ;
		if ( $t_access_level >= plugin_config_get( 'update_threshold' ) ) {

			echo '<tr class="spacer"><td colspan="6"></td></tr>';
			echo '<tr class="hidden"></tr>';
?>
			<tr>
				<th class="category">
					<label for="con_id">
						<?php echo plugin_lang_get( 'consultant','Defcon' ) ?>
					</label>
				</th>
				<td>
					<select id="con_id" name="con_id"
							<?php echo helper_get_tab_index() ?>
							class="autofocus input-sm">
						<?php 
						print_assign_to_option_list( intval($t_consultant_id), intval($t_project_id)) ;
						?>
					</select>
				</td>
			</tr>
<?php

		} else {
			$query1 = "Select username from {user} where id=$con_id";
			$result1 = db_query($query1);
			if ($result1){
				$row1 = db_fetch_array($result1);
				if($row1){
					$t_consultant = $row1['username'];
				} else {
					$t_consultant = "UNKNOWN" ;
				}
			}
			?>
			<input type="hidden" id="con_id" name="con_id" value="<?php echo $con_id ?>">
			<?php
			echo '<tr>';
			echo '<th class="bug-summary category">', plugin_lang_get( 'consultant', 'Defcon' ), '</th>';
			echo '<td class="bug-summary" >', $t_consultant, '</td>';
			echo '</tr>';	
		}
		
	}

	function defcon_create_issue2 ($p_event, $thisissue){
		$t_project_id= $thisissue->project_id;
		$t_bug_id= $thisissue->id;
		if (substr($_SERVER['HTTP_REFERER'],-19) <> 'bug_report_page.php'){
			$query = "select consultant_id from {plugin_Defcon_project} WHERE project_id= $t_project_id";
			$result = db_query($query);
			if ($result){
				$row = db_fetch_array($result);
				if($row){
					$t_consultant_id = $row['consultant_id'];
				} else {
					$t_consultant_id = plugin_config_get( 'backup_consultant' ) ;
				}
			}		
		} else {
			$t_consultant_id = intval(gpc_get_string( 'con_id'));
		}

		// first update the plugin table
		$query = "select * from {plugin_Defcon_issue} WHERE issue_id= $t_bug_id";
		$result = db_query($query);
		$count= db_num_rows($result);
		if ($count>0){
			$query = "update {plugin_Defcon_issue} SET consultant_id=$consultant_id WHERE issue_id= $t_bug_id";
			$result = db_query($query);
		} else{
            $query = "INSERT INTO {plugin_Defcon_issue} (issue_id,consultant_id) values ($t_bug_id, $t_consultant_id)";
			$result = db_query($query);
		}
		// next update the mantis_project_user_list_table in case role is set to be Manager
		// Then consultant needs to be assigned Manager rights for this project ( ex-consultant will keep the existing Manager level)
		// In case roile is set to be Monitor, all is handled @ issue level
		$f_role = plugin_config_get( 'backup_consultant' );
		if ($f_role == 0){
			$query = "select * from {project_user_list} WHERE user_id= $t_consultant_id and project_id=$t_project_id";
			$result = db_query($query);
			$count= db_num_rows($result);
			if ($count>0){
				$query = "update {project_user_list} SET access_level=70 WHERE user_id= $t_consultant_id and project_id=$t_project_id";
				$result = db_query($query);
			} else{
				$query = "INSERT INTO {project_user_list} (project_id, user_id,access_level) values ($t_project_id, $t_consultant_id,70)";
				$result = db_query($query);
			}		
		} else {
			// we need to ensure that the consultant is monitoring this issue
			$query = "select * from {bug_monitor} WHERE user_id= $t_consultant_id and bug_id=$t_bug_id";
			$result = db_query($query);
			$count= db_num_rows($result);
			if ($count ==0){
				$query = "INSERT INTO {bug_monitor} (user_id, bug_id) values ($t_consultant_id,$t_bug_id)";
				$result = db_query($query);	
			}
		}

	}

	function defcon_view_issue ( $thisissue, $t_issue_id){	
		$query1 = "Select username from {plugin_Defcon_issue} as a, {user} as b where a.consultant_id=b.id and issue_id=$t_issue_id";
		$result1 = db_query($query1);
		if ($result1){
			$row1 = db_fetch_array($result1);
			if($row1){
				$t_consultant = $row1['username'];
			} else {
				$t_consultant = "UNKNOWN" ;
			}
		}
		echo '<tr class="spacer"><td colspan="6"></td></tr>';
		echo '<tr class="hidden"></tr>';
		echo '<tr>';
		echo '<th class="bug-summary category">', plugin_lang_get( 'consultant', 'Defcon' ), '</th>';
		echo '<td class="bug-summary" >', $t_consultant, '</td>';
		echo '</tr>';
	}

	function defcon_myview() {
 		 include 'plugins/Defcon/pages/myview_defcon.php';
	}
	
	function defcon_update_issue( $p_event, $t_issue_id ) {
		
		# retrieve project_id
		$query1 = "Select project_id from {bug} where id=$t_issue_id";
		$result1 = db_query($query1);
		if ($result1){
			$row1 = db_fetch_array($result1);
			if($row1){
				$t_project_id = $row1['project_id'];
			} else {
				$t_project_id = 0 ;
			}
		}
	
		# allow to change Concultant_id (if access level is higher than defined)
		$t_user_id = auth_get_current_user_id();
		$t_access_level = user_get_access_level( $t_user_id, $t_project_id );

		if ( $t_access_level >= plugin_config_get( 'update_threshold' ) ) {

			$query1 = "Select consultant_id from {plugin_Defcon_issue} where issue_id=$t_issue_id";
			$result1 = db_query($query1);
			if ($result1){
				$row1 = db_fetch_array($result1);
				if ($row1){
					$t_consultant_id = $row1['consultant_id'];
				} else {
					$t_consultant_id = 0 ;
				}
			}
		
			echo '<tr class="spacer"><td colspan="6"></td></tr>';
			echo '<tr class="hidden"></tr>';
?>
			<tr>
				<th class="category">
					<label for="reporter_id">
						<?php echo plugin_lang_get( 'consultant','Defcon' ) ?>
					</label>
				</th>
				<td>
					<select id="con_id" name="con_id"
							<?php echo helper_get_tab_index() ?>
							class="autofocus input-sm">
						<?php 
						print_assign_to_option_list( intval($t_consultant_id), intval($t_project_id)) ;
						?>
					</select>
				</td>
			</tr>
<?php

		} else {
				echo '<tr>';
			echo '<th class="bug-summary category">', plugin_lang_get( 'consultant', 'Defcon' ), '</th>';
			echo '<td class="bug-summary" >', $t_consultant, '</td>';
			echo '</tr>';	
		}
		
	}

	function defcon_update_issue2($p_event,$p_bug_data) {
		if (substr($_SERVER['HTTP_REFERER'],-19) <> 'bug_update_page.php'){
			return;
		}
		$t_bug_id = $p_bug_data-> id;
		$t_project_id = $p_bug_data-> project_id;
		$t_consultant_id = intval(gpc_get_string( 'con_id'));
	
		// first update the plugin table
		$query = "select * from {plugin_Defcon_issue} WHERE issue_id= $t_bug_id";
		$result = db_query($query);
		$count= db_num_rows($result);
		if ($count>0){
			$query = "update {plugin_Defcon_issue} SET consultant_id=$t_consultant_id WHERE issue_id= $t_bug_id";
			$result = db_query($query);
		} else{
            $query = "INSERT INTO {plugin_Defcon_issue} (issue_id,consultant_id) values ($t_bug_id, $t_consultant_id)";
			$result = db_query($query);
		}
		// next update the mantis_project_user_list_table in case role is set to be Manager
		// Then consultant needs to be assigned Manager rights for this project ( ex-consultant will keep the existing Manager level)
		// In case roile is set to be Monitor, all is handled @ issue level
		$f_role = plugin_config_get( 'backup_consultant' );
		if ($f_role == 0){
			$query = "select * from {project_user_list} WHERE user_id= $t_consultant_id and project_id=$t_project_id";
			$result = db_query($query);
			$count= db_num_rows($result);
			if ($count>0){
				$query = "update {project_user_list} SET access_level=70 WHERE user_id= $t_consultant_id and project_id=$t_project_id";
				$result = db_query($query);
			} else{
				$query = "INSERT INTO {project_user_list} (project_id, user_id,access_level) values ($t_project_id, $t_consultant_id,70)";
				$result = db_query($query);
			}		
		} else {
			// we need to ensure that the consultant is monitoring this issue
			$query = "select * from {bug_monitor} WHERE user_id= $t_consultant_id and bug_id=$t_bug_id";
			$result = db_query($query);
			$count= db_num_rows($result);
			if ($count ==0){
				$query = "INSERT INTO {bug_monitor} (user_id, bug_id) values ($t_consultant_id,$t_bug_id)";
				$result = db_query($query);	
			}
		}
		
		return;
	}

	function defcon_delete_issue($p_event,$t_issue_id) {
		$query = "delete from {plugin_Defcon_issue} WHERE issue_id= $t_issue_id";
		$result = db_query($query);
		return;
	}
		function schema() {
		return array(
			array( 'CreateTableSQL', array( plugin_table( 'project' ), "
						id 			I       NOTNULL AUTOINCREMENT UNSIGNED PRIMARY,
						project_id			I	NOT NULL ,
						consultant_id		I	NOT NULL ,
						generalmail			C(100)
						" ) ),
			array( 'CreateTableSQL', array( plugin_table( 'issue' ), "
						id		 			I   NOTNULL AUTOINCREMENT UNSIGNED PRIMARY,
						issue_id			I	NOT NULL ,
						consultant_id		I	NOT NULL 
						" ) ),
		);
	} 
}
