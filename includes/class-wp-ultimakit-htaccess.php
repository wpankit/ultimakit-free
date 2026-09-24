<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Handle the htaccess file
 *
 * @link       https://xwpstack.com
 * @since      1.0.0
 *
 * @package           UltimaKit
 * @subpackage UltimaKit/includes
 */

class UltimaKit_Htaccess {

    const FILE_PATH     = ABSPATH . '.htaccess';
    const START_MARKER  = '# BEGIN UltimaKit: ';
    const END_MARKER    = '# END UltimaKit: ';

    /**
     * __construct
     *
     * @return void
     */
    public function __construct() {

    }

    /**
     * Add contents to the file
     */
    public static function add( $new_contents, $contents_id ) {

        $contents       = self::generate_the_new_contents( $contents_id );
        $start_marker   = self::START_MARKER . $contents_id;
		$end_marker     = self::END_MARKER . $contents_id;
        $contents       = $start_marker . "\n" . $new_contents . "\n" . $end_marker . "\n\n" . $contents;

        self::put_content( $contents );
    }

    /**
     * Remove contents from the file
     */
    public static function remove( $id ) {

        $contents = self::generate_the_new_contents( $id );

        self::put_content( $contents );
    }

    /**
     * Generate the new contents
     */
    private static function generate_the_new_contents( $contents_id ) {

        $contents = self::get_file_contents();

        if ( !$contents ) {
            return;
        }

        $start_marker   = self::START_MARKER . $contents_id;
		$end_marker     = self::END_MARKER . $contents_id;

        // Remove previous rules if exist.
        $contents = preg_replace( '/\s*?' . preg_quote( $start_marker, '/' ) . '.*' . preg_quote( $end_marker, '/' ) . '\s*?/isU', "\n\n", $contents );
		$contents = trim( $contents );

        return $contents;
    }

    /**
     * Get the file contents
     */
    private static function get_file_contents() {
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();

		$file_path = self::FILE_PATH;
    	$dir_path  = dirname( $file_path );

		if ( ! $wp_filesystem->is_dir( $dir_path ) ) {
			$wp_filesystem->mkdir( $dir_path, FS_CHMOD_DIR );
		}

		if ( ! $wp_filesystem->exists( $file_path ) ) {
			$wp_filesystem->put_contents( $file_path, '', FS_CHMOD_FILE );
		}

		if ( ! $wp_filesystem->is_writable( $file_path ) ) {
			return false;
		}

		$contents = $wp_filesystem->get_contents( $file_path );

		return ( false !== $contents ) ? $contents : false;
    }

    /**
     * Write contents to the file.
     */
    private static function put_content( $contents ) {
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();

		$file_path = self::FILE_PATH;
    	$dir_path  = dirname( $file_path );

		if ( ! $wp_filesystem->is_dir( $dir_path ) ) {
			$wp_filesystem->mkdir( $dir_path, FS_CHMOD_DIR );
		}

		$success = $wp_filesystem->put_contents( $file_path, $contents, FS_CHMOD_FILE );

		if ( ! $success ) {
			return false;
		}

		$chmod_file = fileperms( ABSPATH . 'index.php' ) & 0777 | 0644;
    	$wp_filesystem->chmod( $file_path, $chmod_file );

		return true;
    }
}
