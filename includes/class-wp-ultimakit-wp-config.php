<?php
/**
 * Handle the wp-config file
 *
 * @link       https://xwpstack.com
 * @since      1.0.0
 *
 * @package           UltimaKit
 * @subpackage UltimaKit/includes
 */

class UltimaKit_WP_Config {
    /**
     * __construct
     *
     * @return void
     */
    public function __construct() {
    }

    /**
     * get_wp_config_path
     *
     * Finds wp-config.php the same way wp-load.php does: in ABSPATH, or one level up
     * when that directory is not itself a WordPress install. Returns an empty string
     * when neither exists. Assuming ABSPATH used to create an empty wp-config.php there
     * on sites that keep it one level up, and WordPress then loaded the empty file.
     *
     * @return string
     */
    public static function get_wp_config_path() {
        if ( file_exists( ABSPATH . 'wp-config.php' ) ) {
            return ABSPATH . 'wp-config.php';
        }

        if ( @file_exists( dirname( ABSPATH ) . '/wp-config.php' ) && ! @file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {
            return dirname( ABSPATH ) . '/wp-config.php';
        }

        return '';
    }

    /**
     * get_wp_config_content
     *
     * @return string The file contents, or an empty string when it cannot be read.
     */
    public static function get_wp_config_content() {
        $wp_config_path = self::get_wp_config_path();

        if ( '' === $wp_config_path || ! is_readable( $wp_config_path ) ) {
            return '';
        }

        $wp_config_content = file_get_contents( $wp_config_path );
        return ( false === $wp_config_content ) ? '' : $wp_config_content;
    }

    /**
     * Write wp-config.php, refusing anything that could take the site down.
     *
     * Every real wp-config.php loads wp-settings.php, so new contents that no longer do
     * (an empty string, or a failed preg_replace returning null) are never written.
     * On multisite the file configures the whole network, so only super admins write it.
     *
     * @param  string|null $wp_config_content New file contents.
     * @return int|false Bytes written, or false when nothing was written.
     */
    private static function write_wp_config( $wp_config_content ) {
        $wp_config_path = self::get_wp_config_path();

        if ( '' === $wp_config_path || ! is_writable( $wp_config_path ) ) {
            return false;
        }

        if ( is_multisite() && ! is_super_admin() ) {
            return false;
        }

        if ( ! is_string( $wp_config_content ) || false === strpos( $wp_config_content, 'wp-settings.php' ) ) {
            return false;
        }

        return file_put_contents( $wp_config_path, $wp_config_content, LOCK_EX );
    }

    /**
     * is_constant_defined_in_wp_config
     *
     * @param  mixed $constant_name
     * @return void
     */
    public static function is_constant_defined_in_wp_config( $constant_name ) {
        $wp_config_content = self::get_wp_config_content();

        // Delete comments from the wp-config file for better parsing
        $wp_config_content = preg_replace('/\/\*.*?\*\//s', '', $wp_config_content);

        // Check if the constant is defined in the wp-config file
        $pattern = '/define\s*\(\s*[\'"]' . preg_quote($constant_name, '/') . '[\'"]\s*,\s*[\s\S]*?\);/';
        preg_match_all($pattern, (string) $wp_config_content, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as $match) {
            $const_pos = $match[1];
            // Check if the constant is commented out
            $line_start = strrpos(substr($wp_config_content, 0, $const_pos), "\n") + 1;
            $line = substr($wp_config_content, $line_start, $const_pos - $line_start);
            if (!preg_match('/^\s*\/\/|^\s*#/', $line)) {
                return true;
            }
        }
        return false;
    }


    /**
     * replace_or_add_constant
     *
     * @param  mixed $constant_name
     * @param  mixed $new_value
     * @param  mixed $wp_config_content
     * @return void
     */
    public static function replace_or_add_constant( $constant_name, $new_value, $type = null ) {
        if( self::is_constant_defined_in_wp_config( $constant_name ) ) {
            // we replace the constant value
            return self::replace_constant_value( $constant_name, $new_value, $type );
        } else {
            // we add the constant at the start of the file just after the opening php tag
            return self::add_constant( $constant_name, $new_value );
        }
    }

    /**
     * add_constant
     *
     * @param  mixed $constant_name
     * @param  mixed $constant_value
     * @param  mixed $position
     * @return void
     */
    public static function add_constant( $constant_name, $constant_value ) {
        $wp_config_content = self::get_wp_config_content();

        if ( '' === $wp_config_content ) {
            return false;
        }

		//phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
        $wp_config_content = preg_replace( '/<\?php/', "<?php\ndefine( '" . $constant_name . "', " . var_export($constant_value, true) . " );", $wp_config_content, 1 );
        return self::write_wp_config( $wp_config_content );
    }

    /**
     * replace_constant_value
     *
     * @param  mixed $constant_name
     * @param  mixed $constant_value
     * @return void
     */
    public static function replace_constant_value( $constant_name, $constant_value, $type = null ) {
        $wp_config_content = self::get_wp_config_content();

        if ( '' === $wp_config_content ) {
            return false;
        }

        if( $type == "string") {
            $pattern_single_quote = '/define\s*\(\s*[\'"]' . preg_quote( $constant_name, '/' ) . '[\']/';
            if( preg_match( $pattern_single_quote, $wp_config_content ) ) {
                $pattern = '/define\s*\(\s*[\'"]' . preg_quote( $constant_name, '/' ) . '[\'"]\s*,\s*[\'][\s\S]*?[\']\s*\);/';
            } else {
                $pattern = '/define\s*\(\s*[\'"]' . preg_quote( $constant_name, '/' ) . '[\'"]\s*,\s*["][\s\S]*?["]\s*\);/';
            }
        } else {
            $pattern = '/define\s*\(\s*[\'"]' . preg_quote( $constant_name, '/' ) . '[\'"]\s*,\s*[\s\S]*?\);/';
        }

		//phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
        $replacement = "define( '" . $constant_name . "', " . var_export($constant_value, true) . " );";

        $wp_config_content = preg_replace( $pattern, $replacement, $wp_config_content );

        return self::write_wp_config( $wp_config_content );
    }

    /**
     * remove_constant
     *
     * @param  mixed $constant_name
     * @return void
     */
    public static function remove_constant( $constant_name ) {
        $wp_config_content = self::get_wp_config_content();

        if ( '' === $wp_config_content ) {
            return false;
        }

        $pattern = '/define\s*\(\s*[\'"]' . preg_quote( $constant_name, '/' ) . '[\'"]\s*,\s*[\s\S]*?\);/';

        $wp_config_content = preg_replace( $pattern, '', $wp_config_content );

        return self::write_wp_config( $wp_config_content );
    }

	/**
	 * Change value of a PHP variable
	 *
	 * @param string $variable_name
	 */
	public static function change_php_variable( $variable_name, $new_value ) {
		$wp_config_content = self::get_wp_config_content();

		if ( '' === $wp_config_content ) {
			return false;
		}

		$pattern           = '/\$' . $variable_name . '\s=(.*)\;/';
		//phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
		$new_variable      = "\n\$" . $variable_name . " = " . var_export( $new_value, true ) . ";\n";
        $wp_config_content = preg_replace( $pattern, $new_variable, $wp_config_content );

		return self::write_wp_config( $wp_config_content );
	}
}
