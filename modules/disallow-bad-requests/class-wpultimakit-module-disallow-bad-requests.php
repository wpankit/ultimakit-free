<?php
/**
 * Class UltimaKit_Module_Disallow_Bad_Requests
 *
 * @since 1.0.0
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Disallow_Bad_Requests
 *
 * This class provides methods to control the display of dashboard widgets based on user preferences.
 * It allows users to selectively hide certain dashboard widgets to streamline their WordPress dashboard
 * experience and improve usability.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Disallow_Bad_Requests extends UltimaKit_Module_Manager {
	/**
	 *
	 * @var string
	 */
	protected $ID = 'ultimakit_module_disallow_bad_requests';

	/**
	 * The name of the module.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * A brief description of what the module does.
	 *
	 * @var string
	 */
	protected $description;

	/**
	 * The pricing plan associated with the module.
	 *
	 * @var string
	 */
	protected $plan = 'free';

	/**
	 * The category of functionality the module falls under.
	 *
	 * @var string
	 */
	protected $category = 'Security';

	/**
	 * The type of module, indicating its platform or use case.
	 *
	 * @var string
	 */
	protected $type = 'WordPress';

	/**
	 * Flag indicating whether the module is active.
	 *
	 * @var bool
	 */
	protected $is_active;

	/**
	 * URL providing more detailed information about the module.
	 *
	 * @var string
	 */
	protected $read_more_link = 'disallow-bad-requests-in-wordpress';

	/**
	 * The settings associated with the module, if any.
	 *
	 * @var array
	 */
	protected $settings;

	const LONG_REQUEST_LENGTH = 2000;

	/**
	 *
	 * Initializes the module with default values for properties and prepares
	 * any necessary setup or hooks into WordPress. This may include setting
	 * initial values, registering hooks, or preparing resources needed for
	 * the module to function properly within WordPress.
	 */
	public function __construct() {
		$this->name        = __( 'Disallow Bad Requests', 'ultimakit-for-wp' );
		$this->description = __( 'Protect your site against a wide range of threats. check all incoming traffic and quietly blocks bad requests containing nasty stuff like eval(, base64_, and excessively long request-strings.', 'ultimakit-for-wp' );
		$this->is_active   = $this->isModuleActive( $this->ID );
		$this->settings    = 'no';
		$this->initializeModule();

        add_filter('ultimakit_module_class_map', function($map) {
			$map['ultimakit_module_disallow_bad_requests'] = 'UltimaKit_Module_Disallow_Bad_Requests';
			return $map;
		});
	}

	/**
	 * Initializes the specific module within the application.
	 *
	 * This function is responsible for performing the initial setup required to get the module
	 * up and running. This includes registering hooks and filters, enqueing styles and scripts,
	 * and any other preliminary setup tasks that need to be performed before the module can
	 * start functioning as expected.
	 *
	 * It's typically called during the plugin or theme's initialization phase, ensuring that
	 * all module dependencies are loaded and ready for use.
	 *
	 * @return void
	 */
	protected function initializeModule() {
		if ( $this->is_active ) {
            $this->check_request_uri();
			$this->check_query_string();
			$this->check_http_user_agent();
			$this->check_http_referrer();
		}
	}


	/**
     * Check the URI string
     */
    private function check_request_uri() {

        $request_uri_array  = array( '\/\.env', '\s', '<', '>', '\^', '`', '@@', '\?\?', '\/&&', '\\', '\/=', '\/:\/', '\/\/\/', '\.\.\.', '\/\*(.*)\*\/', '\+\+\+', '\{0\}', '0x00', '%00', '\(\/\(', '(\/|;|=|,)nt\.', '@eval', 'eval\(', 'union(.*)select', '\(null\)', 'base64_', '(\/|%2f)localhost', '(\/|%2f)pingserver', 'wp-config\.php', '(\/|\.)(s?ftp-?)?conf(ig)?(uration)?\.', '\/wwwroot', '\/makefile', 'crossdomain\.', 'self\/environ', 'usr\/bin\/perl', 'var\/lib\/php', 'etc\/passwd', 'etc\/hosts', 'etc\/motd', 'etc\/shadow', '\/https:', '\/http:', '\/ftp:', '\/file:', '\/php:', '\/cgi\/', '\.asp', '\.bak', '\.bash', '\.bat', '\.cfg', '\.cgi', '\.cmd', '\.conf', '\.db', '\.dll', '\.ds_store', '\.exe', '\/\.git', '\.hta', '\.htp', '\.init?', '\.jsp', '\.msi', '\.mysql', '\.pass', '\.pwd', '\.sql', '\/\.svn', '\.exec\(', '\)\.html\(', '\{x\.html\(', '\.php\([0-9]+\)', '(benchmark|sleep)(\s|%20)*\(', '\/(db|mysql)-?admin', '\/document_root', '\/error_log', 'indoxploi', '\/sqlpatch', 'xrumer', 'www\.(.*)\.cn', '%3Cscript', '\/vbforum(\/)?', '\/vbulletin(\/)?', '\{\$itemURL\}', '(\/bin\/)(cc|chmod|chsh|cpp|echo|id|kill|mail|nasm|perl|ping|ps|python|tclsh)(\/)?$', '((curl_|shell_)?exec|(f|p)open|function|fwrite|leak|p?fsockopen|passthru|phpinfo|posix_(kill|mkfifo|setpgid|setsid|setuid)|proc_(close|get_status|nice|open|terminate)|system)(.*)(\()(.*)(\))', '(\/)(^$|0day|c99|configbak|curltest|db|index\.php\/index|(my)?sql|(php|web)?shell|php-?info|temp00|vuln|webconfig)(\.php)' );
        $request_uri_string = '';

        if ( isset( $_SERVER['REQUEST_URI'] ) && ! empty( $_SERVER['REQUEST_URI'] ) ) {
            $request_uri_string = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
        }

        if ( strlen( $request_uri_string ) > self::LONG_REQUEST_LENGTH ) {
            $this->response_forbidden();
        }

	    if ( ! empty( $request_uri_string ) && preg_match( '/' . implode( '|', $request_uri_array ) . '/i', $request_uri_string, $matches ) ) {
            $this->response_forbidden();
        }
    }

    /**
     * Check the query string
     */
    private function check_query_string() {

        $query_string_array  = array( '\(0x', '0x3c62723e', ';!--=', '\(\)\}', ':;\};', '\.\.\/', '\/\*\*\/', '127\.0\.0\.1', 'localhost', 'loopback', '%0a', '%0d', '%00', '%2e%2e', '%0d%0a', '@copy', 'concat(.*)(\(|%28)', 'allow_url_(fopen|include)', '(c99|php|web)shell', 'auto_prepend_file', 'disable_functions?', 'gethostbyname', 'input_file', 'execute', 'safe_mode', 'file_(get|put)_contents', 'mosconfig', 'open_basedir', 'outfile', 'proc_open', 'root_path', 'user_func_array', 'path=\.', 'mod=\.', '(globals|request)(=|\[)', 'f(fclose|fgets|fputs|fsbuff)', '\$_(env|files|get|post|request|server|session)', '(\+|%2b)(concat|delete|get|select|union)(\+|%2b)', '(cmd|command)(=|%3d)(chdir|mkdir)', '(absolute_|base|root_)(dir|path)(=|%3d)(ftp|https?)', '(s)?(ftp|inurl|php)(s)?(:(\/|%2f|%u2215)(\/|%2f|%u2215))', '(\/|%2f)(=|%3d|\$&|_mm|cgi(\.|-)|inurl(:|%3a)(\/|%2f)|(mod|path)(=|%3d)(\.|%2e))', '(<|>|\'|")(.*)(\/\*|alter|base64|benchmark|cast|char|concat|convert|create|declare|delete|drop|encode|exec|fopen|function|html|insert|md5|request|script|select|set|union|update)' );
        $query_string_string = '';

        if ( isset( $_SERVER['QUERY_STRING'] ) && ! empty( $_SERVER['QUERY_STRING'] ) ) {
            $query_string_string = sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) );
        }

        if ( ! empty( $query_string_string ) && preg_match( '/' . implode( '|', $query_string_array ) . '/i', $query_string_string, $matches ) ) {
            $this->response_forbidden();
        }
    }

    /**
     * Check the HTTP user agent
     */
    private function check_http_user_agent() {

	    $user_agent_array  = array( '&lt;', '%0a', '%0d', '%27', '%3c', '%3e', '%00', '0x00', '\/bin\/bash', '360Spider', 'acapbot', 'acoonbot', 'alexibot', 'asterias', 'attackbot', 'backdorbot', 'base64_decode', 'becomebot', 'binlar', 'blackwidow', 'blekkobot', 'blexbot', 'blowfish', 'bullseye', 'bunnys', 'butterfly', 'careerbot', 'casper', 'checkpriv', 'cheesebot', 'cherrypick', 'chinaclaw', 'choppy', 'clshttp', 'cmsworld', 'copernic', 'copyrightcheck', 'cosmos', 'crescent', 'cy_cho', 'datacha', 'demon', 'diavol', 'discobot', 'disconnect', 'dittospyder', 'dotbot', 'dotnetdotcom', 'dumbot', 'emailcollector', 'emailsiphon', 'emailwolf', 'eval\(', 'exabot', 'extract', 'eyenetie', 'feedfinder', 'flaming', 'flashget', 'flicky', 'foobot', 'g00g1e', 'getright', 'gigabot', 'go-ahead-got', 'gozilla', 'grabnet', 'grafula', 'harvest', 'heritrix', 'httrack', 'icarus6j', 'jetbot', 'jetcar', 'jikespider', 'kmccrew', 'leechftp', 'libweb', 'linkextractor', 'linkscan', 'linkwalker', 'loader', 'lwp-download', 'masscan', 'miner', 'majestic', 'md5sum', 'mechanize', 'mj12bot', 'morfeus', 'moveoverbot', 'netmechanic', 'netspider', 'nicerspro', 'nikto', 'nutch', 'octopus', 'pagegrabber', 'planetwork', 'postrank', 'proximic', 'purebot', 'pycurl', 'queryn', 'queryseeker', 'radian6', 'radiation', 'realdownload', 'remoteview', 'rogerbot', 'scooter', 'seekerspider', 'semalt', '(c99|php|web)shell', 'shellshock', 'siclab', 'sindice', 'sistrix', 'sitebot', 'site(.*)copier', 'siteexplorer', 'sitesnagger', 'skygrid', 'smartdownload', 'snoopy', 'sosospider', 'spankbot', 'spbot', 'sqlmap', 'stackrambler', 'stripper', 'sucker', 'surftbot', 'sux0r', 'suzukacz', 'suzuran', 'takeout', 'teleport', 'telesoft', 'true_robots', 'turingos', 'turnit', 'unserialize', 'vampire', 'vikspider', 'voideye', 'webleacher', 'webreaper', 'webstripper', 'webvac', 'webviewer', 'webwhacker', 'winhttp', 'wwwoffle', 'woxbot', 'xaldon', 'xxxyy', 'yamanalab', 'yioopbot', 'youda', 'zeus', 'zmeu', 'zyborg' );
        $user_agent_string = '';

        if ( isset( $_SERVER['HTTP_USER_AGENT'] ) && ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
            $user_agent_string = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
        }

        if ( ! empty( $user_agent_string ) && preg_match( '/' . implode( '|', $user_agent_array ) . '/i', $user_agent_string, $matches ) ) {
            $this->response_forbidden();
        }
    }

    /**
     * Check the HTTP referrer
     */
    private function check_http_referrer() {

        $referrer_array  = array( 'blue\s?pill', 'ejaculat', 'erectile', 'erections', 'hoodia', 'huronriver', 'impotence', 'levitra', 'libido', 'lipitor', 'phentermin', 'pro[sz]ac', 'sandyauer', 'semalt\.com', 'todaperfeita', 'tramadol', 'ultram', 'unicauca', 'valium', 'viagra', 'vicodin', 'xanax', 'ypxaieo' );
        $referrer_string = '';

        if ( isset( $_SERVER['HTTP_REFERER'] ) && ! empty( $_SERVER['HTTP_REFERER'] ) ) {
            $referrer_string = sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
        }

        if ( strlen( $referrer_string ) > self::LONG_REQUEST_LENGTH ) {
            $this->response_forbidden();
        }

	    if ( ! empty( $referrer_string ) && preg_match( '/' . implode( '|', $referrer_array ) . '/i', $referrer_string, $matches ) ) {
            $this->response_forbidden();
        }
    }


    /**
     * Response to the bad requests with 403
     */
    private function response_forbidden() {

        header( 'HTTP/1.1 403 Forbidden' );
        header( 'Status: 403 Forbidden' );
        header( 'Connection: Close' );
        exit;
    }

}
