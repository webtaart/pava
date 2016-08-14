<?php
// No PHP runtime calculated yet. Try to see if test is finished.
if ( 0 == pb_backupbuddy::$options['tested_php_runtime'] ) {
	backupbuddy_core::php_runtime_test_results();
}

// Check if Godaddy Managed WordPress hosting.
if ( defined( 'GD_SYSTEM_PLUGIN_DIR' ) ) {
	echo '<br>';
	pb_backupbuddy::disalert( 'godaddy_managed_wp_detected', '<h3>' . __( 'Uh oh! Possible problem detected...', 'it-l10n-backupbuddy' ) . '</h3><br>' . __( '<span style="font-size:1.5em;font-weight:bold;">GoDaddy Managed WordPress Hosting Detected</span><br><br>GoDaddy\'s Managed WordPress Hosting is currently experiencing a problem resulting in the WordPress cron not working properly which is known to break WordPress\' built-in scheduling and automation functionality, a core WordPress feature, from working. GoDaddy is aware of this issue and hopes to fix it but they do not have an ETA currently.  If you would like more information about this ongoing issue you can contact their support.<br><br>Unfortunately BackupBuddy, along with most other WordPress backup plugins (and even other plugins unrelated to backups) requires the WordPress cron to function properly.<br><br>We do have a partial workaround but it is only useful for manual traditional backups (non-Stash Live) and is very slow.  If you\'d like to give this a try please go to BackupBuddy -> "Settings" page -> "Advanced Settings / Troubleshooting" tab -> Check the box "Force internal cron" -> Scroll down and "Save" the settings.  This may help you be able to make a manual traditional backup though it may be slow and is not guaranteed.<br><br>Unfortunately until GoDaddy fixes this Managed WordPress Hosting problem you may experienced difficulty or significant delays in creating backups.', 'it-l10n-backupbuddy' ) );
}


// Multisite Export. This file loaded from multisite_export.php.
if ( isset( $export_only ) && ( $export_only === true ) ) {
	if ( pb_backupbuddy::_GET( 'backupbuddy_backup' ) == '' ) {
		// Do nothing.
	} elseif ( pb_backupbuddy::_GET( 'backupbuddy_backup' ) == 'export' ) {
		require_once( '_backup-perform.php' );
	} else {
		die( '{Unknown backup type.}' );
	}
	
	return;
}



if ( pb_backupbuddy::_GET( 'custom' ) != '' ) { // Custom page.
	
	if ( pb_backupbuddy::_GET( 'custom' ) == 'remoteclient' ) {
		//require_once( '_remote_client.php' );
		die( 'Fatal Error #847387344: Obselete URL. Use remoteClient AJAX URL.' );
	} else {
		die( 'Unknown custom page. Error #4385489545.' );
	}
	
} else { // Normal backup page.
	
	if ( pb_backupbuddy::_GET( 'zip_viewer' ) != '' ) {
		require_once( '_zip_viewer.php' );
	} elseif ( pb_backupbuddy::_GET( 'backupbuddy_backup' ) == '' ) {
		require_once( '_backup-home.php' );
	} else {
		require_once( '_backup-perform.php' );
	}

}
