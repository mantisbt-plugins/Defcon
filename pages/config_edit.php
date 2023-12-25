<?php
/**
 * Defcon plugin for MantisBT
 * https://github.com/mantisbt-plugins/Defcon
 *
 * Copyright (c) 2022  Cas Nuy
 *
 * This file is part of the CustomReporter plugin for MantisBT.
 *
 * CustomReporter is free software: you can redistribute it and/or modify
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

# authenticate
auth_reauthenticate();
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

# Read results
form_security_validate( 'plugin_defcon_config_update' );
$f_update_threshold = gpc_get_int( 'plugin_defcon_threshold', DEVELOPER );
$f_myview_status = gpc_get_int( 'plugin_defcon_status', 90 );
$f_backup_consultant = gpc_get_int( 'plugin_defcon_backup', 1 );
$f_role = gpc_get_int( 'plugin_defcon_role', 1 );
$f_historic = gpc_get_int( 'plugin_defcon_historic', 1 );
# update results
plugin_config_set( 'update_threshold', $f_update_threshold );
plugin_config_set( 'myview_status', $f_myview_status );
plugin_config_set( 'backup_consultant', $f_backup_consultant );
plugin_config_set( 'role', $f_role );
plugin_config_set( 'historic', $f_historic );

form_security_purge( 'plugin_defcon_config_update' );

# redirect
print_successful_redirect( plugin_page( 'config', true ) );
