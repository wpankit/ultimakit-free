<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://xwpankit.com
 * @since      1.0.0
 *
 * @package    UltimaKit
 * @subpackage UltimaKit/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    UltimaKit
 * @subpackage UltimaKit/includes
 * @author     Ankit Panchal <developer@wpultimakit.com>
 */
class UltimaKit_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {

		// Post Expiry Manager's daily check; the module schedules it again when it loads.
		$timestamp = wp_next_scheduled( 'wpuk_check_expired_posts' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'wpuk_check_expired_posts' );
		}
	}
}
