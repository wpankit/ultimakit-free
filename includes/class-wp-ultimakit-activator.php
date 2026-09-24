<?php

/**
 * Fired during plugin activation
 *
 * @link       https://xwpankit.com
 * @since      1.0.0
 *
 * @package    UltimaKit
 * @subpackage UltimaKit/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    UltimaKit
 * @subpackage UltimaKit/includes
 * @author     Ankit Panchal <developer@wpultimakit.com>
 */
class UltimaKit_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// Check if the options array already exists
		if ( false === get_option( 'ultimakit_options', false ) ) {

			$admin                  = new UltimaKit_Module_Manager();
			$all_modules            = $admin->getAllModules();
			$default_module_options = array();
			foreach ( $all_modules as $module ) {
				if ( false === $admin->isModuleActive( $module['id'] ) ) {
					$default_module_options[ $module['id'] ] = array( 'enabled' => false );
				}
			}

			// Options array doesn't exist, so insert the default settings
			update_option( 'ultimakit_options', $default_module_options, 'no' );
			update_option( 'ultimakit_uninstall_settings', 'off', 'no' );
		}

		global $wpdb;
		$table_name      = $wpdb->prefix . 'ultimakit_module_settings';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			module_name VARCHAR(255) NOT NULL,
			setting_key VARCHAR(255) NOT NULL,
			setting_value LONGTEXT,
			autoload BOOLEAN DEFAULT FALSE,
			UNIQUE KEY module_setting (module_name, setting_key)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Check if migration has already been performed
		$migration_completed = get_option( 'ultimakit_migration_completed' );
		if ( $migration_completed ) {
			return;
		}

		// Retrieve the old serialized settings option
		$old_settings = get_option( 'ultimakit_options' );

		if ( ! $old_settings || ! is_array( $old_settings ) ) {
			return; // Exit if no old settings are found or if data is not in expected format
		}

		// Loop through each module's settings and insert into the custom table
		foreach ( $old_settings as $module_name => $settings ) {
			foreach ( $settings as $setting_key => $setting_value ) {
				// Insert each setting into the new custom settings table
				$autoload = ( $setting_key === 'enabled' ); // Set autoload true for "enabled" settings only
				self::ultimakit_update_module_setting( $module_name, $setting_key, $setting_value, $autoload );
			}
		}

		// Set the migration completion flag
		update_option( 'ultimakit_migration_completed', true );
	}

	public static function ultimakit_update_module_setting( $module_name, $setting_key, $setting_value, $autoload = false ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ultimakit_module_settings';

		$wpdb->replace(
			$table_name,
			array(
				'module_name'   => $module_name,
				'setting_key'   => $setting_key,
				'setting_value' => maybe_serialize( $setting_value ),
				'autoload'      => $autoload,
			)
		);
	}
}
