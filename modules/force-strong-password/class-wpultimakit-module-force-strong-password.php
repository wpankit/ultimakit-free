<?php
/**
 * Class UltimaKit_Module_Force_Strong_Password
 *
 * @since 1.8.5
 * @package    UltimaKit
 */

/**
 * Class UltimaKit_Module_Force_Strong_Password
 *
 * This class provides methods to enforce strong password requirements for WordPress users.
 * It validates passwords during user registration and password changes to ensure they meet
 * security standards including minimum length, complexity requirements, and common password checks.
 *
 * @since 1.0.0
 */
class UltimaKit_Module_Force_Strong_Password extends UltimaKit_Module_Manager {
	/**
	 * @var string
	 */
	protected $ID = 'ultimakit_module_force_strong_password';

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
	protected $read_more_link = 'force-strong-passwords-in-wordpress';

	/**
	 * The settings associated with the module, if any.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Constructs the Force Strong Password module instance.
	 *
	 * Initializes the module with default values for properties and prepares
	 * any necessary setup or hooks into WordPress. This may include setting
	 * initial values, registering hooks, or preparing resources needed for
	 * the module to function properly within WordPress.
	 */
	public function __construct() {
		$this->name        = __( 'Force Strong Password', 'ultimakit-for-wp' );
		$this->description = __( 'Require users to set strong passwords.', 'ultimakit-for-wp' );
		$this->is_active   = $this->isModuleActive( $this->ID );
		$this->settings    = 'no';
		$this->initializeModule();
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
			// Add password strength validation to user registration
			add_action( 'user_register', array( $this, 'validate_password_strength' ), 10, 1 );
			
			// Add password strength validation to profile updates
			add_action( 'profile_update', array( $this, 'validate_password_strength' ), 10, 1 );
			
			// Add password strength validation to password reset
			add_action( 'validate_password_reset', array( $this, 'validate_password_reset' ), 10, 2 );
			
			// Add password strength validation to admin user creation
			add_action( 'edit_user_created_user', array( $this, 'validate_password_strength' ), 10, 1 );
			
			// Add JavaScript to password fields for real-time validation
			add_action( 'admin_footer', array( $this, 'add_password_strength_script' ) );
			add_action( 'wp_footer', array( $this, 'add_password_strength_script' ) );
			
			// Add CSS for password strength indicator
			add_action( 'admin_head', array( $this, 'add_password_strength_styles' ) );
			add_action( 'wp_head', array( $this, 'add_password_strength_styles' ) );
		}
	}

	/**
	 * Validates password strength for user registration and profile updates.
	 *
	 * @param int $user_id The user ID.
	 * @return void
	 */
	public function validate_password_strength( $user_id ) {
		// Get the password from POST data
		$password = isset( $_POST['pass1'] ) ? $_POST['pass1'] : '';
		
		// If no password is being set, skip validation
		if ( empty( $password ) ) {
			return;
		}

		// Validate password strength
		$validation_result = $this->check_password_strength( $password );
		
		if ( ! $validation_result['valid'] ) {
			// Remove the user if it was just created
			if ( did_action( 'user_register' ) ) {
				wp_delete_user( $user_id );
			}
			
			// Set error message
			wp_die( 
				esc_html( $validation_result['message'] ), 
				__( 'Password Strength Error', 'ultimakit-for-wp' ), 
				array( 'back_link' => true ) 
			);
		}
	}

	/**
	 * Validates password strength for password reset.
	 *
	 * @param WP_Error $errors WP_Error object.
	 * @param WP_User $user User object.
	 * @return void
	 */
	public function validate_password_reset( $errors, $user ) {
		$password = isset( $_POST['pass1'] ) ? $_POST['pass1'] : '';
		
		if ( ! empty( $password ) ) {
			$validation_result = $this->check_password_strength( $password );
			
			if ( ! $validation_result['valid'] ) {
				$errors->add( 'password_strength', $validation_result['message'] );
			}
		}
	}

	/**
	 * Checks the strength of a password.
	 *
	 * @param string $password The password to check.
	 * @return array Array with 'valid' boolean and 'message' string.
	 */
	private function check_password_strength( $password ) {
		$errors = array();
		
		// Check minimum length (8 characters)
		if ( strlen( $password ) < 8 ) {
			$errors[] = __( 'Password must be at least 8 characters long.', 'ultimakit-for-wp' );
		}
		
		// Check for uppercase letters
		if ( ! preg_match( '/[A-Z]/', $password ) ) {
			$errors[] = __( 'Password must contain at least one uppercase letter.', 'ultimakit-for-wp' );
		}
		
		// Check for lowercase letters
		if ( ! preg_match( '/[a-z]/', $password ) ) {
			$errors[] = __( 'Password must contain at least one lowercase letter.', 'ultimakit-for-wp' );
		}
		
		// Check for numbers
		if ( ! preg_match( '/[0-9]/', $password ) ) {
			$errors[] = __( 'Password must contain at least one number.', 'ultimakit-for-wp' );
		}
		
		// Check for special characters
		if ( ! preg_match( '/[^A-Za-z0-9]/', $password ) ) {
			$errors[] = __( 'Password must contain at least one special character.', 'ultimakit-for-wp' );
		}
		
		// Check for common passwords
		$common_passwords = array(
			'password', '123456', '123456789', 'qwerty', 'abc123', 'password123',
			'admin', 'letmein', 'welcome', 'monkey', 'dragon', 'master', 'hello',
			'freedom', 'whatever', 'qazwsx', 'trustno1', 'jordan', 'harley',
			'ranger', 'iwantu', 'jennifer', 'hunter', 'buster', 'soccer',
			'batman', 'andrew', 'tigger', 'sunshine', 'iloveyou', 'fuckme',
			'2000', 'charlie', 'robert', 'thomas', 'hockey', 'ranger',
			'daniel', 'starwars', 'klaster', '112233', 'george', 'computer',
			'michele', 'jessica', 'pepper', '1111', 'zxcvbn', '555555',
			'11111111', '131313', 'freedom', '7777777', 'pass', 'maggie',
			'159753', 'aaaaaa', 'ginger', 'princess', 'joshua', 'cheese',
			'amanda', 'summer', 'love', 'ashley', 'nicole', 'chelsea',
			'biteme', 'matthew', 'access', 'yankees', '987654321', 'dallas',
			'austin', 'thunder', 'taylor', 'matrix', 'mobilemail', 'mom',
			'monitor', 'monitoring', 'montana', 'moon', 'moscow'
		);
		
		if ( in_array( strtolower( $password ), $common_passwords ) ) {
			$errors[] = __( 'Password is too common. Please choose a more unique password.', 'ultimakit-for-wp' );
		}
		
		// Check for sequential characters
		if ( preg_match( '/(.)\1{2,}/', $password ) ) {
			$errors[] = __( 'Password cannot contain more than 2 consecutive identical characters.', 'ultimakit-for-wp' );
		}
		
		// Check for keyboard sequences
		$keyboard_sequences = array(
			'qwerty', 'asdfgh', 'zxcvbn', '123456', '654321',
			'qwertyuiop', 'asdfghjkl', 'zxcvbnm'
		);
		
		foreach ( $keyboard_sequences as $sequence ) {
			if ( stripos( $password, $sequence ) !== false ) {
				$errors[] = __( 'Password contains keyboard sequences which are not allowed.', 'ultimakit-for-wp' );
				break;
			}
		}
		
		if ( empty( $errors ) ) {
			return array(
				'valid'   => true,
				'message' => __( 'Password meets strength requirements.', 'ultimakit-for-wp' )
			);
		} else {
			return array(
				'valid'   => false,
				'message' => implode( ' ', $errors )
			);
		}
	}

	/**
	 * Adds JavaScript for real-time password strength validation.
	 *
	 * @return void
	 */
	public function add_password_strength_script() {
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Function to check password strength
			function checkPasswordStrength(password) {
				var errors = [];
				
				// Check minimum length
				if (password.length < 8) {
					errors.push('<?php echo esc_js( __( 'Password must be at least 8 characters long.', 'ultimakit-for-wp' ) ); ?>');
				}
				
				// Check for uppercase letters
				if (!/[A-Z]/.test(password)) {
					errors.push('<?php echo esc_js( __( 'Password must contain at least one uppercase letter.', 'ultimakit-for-wp' ) ); ?>');
				}
				
				// Check for lowercase letters
				if (!/[a-z]/.test(password)) {
					errors.push('<?php echo esc_js( __( 'Password must contain at least one lowercase letter.', 'ultimakit-for-wp' ) ); ?>');
				}
				
				// Check for numbers
				if (!/[0-9]/.test(password)) {
					errors.push('<?php echo esc_js( __( 'Password must contain at least one number.', 'ultimakit-for-wp' ) ); ?>');
				}
				
				// Check for special characters
				if (!/[^A-Za-z0-9]/.test(password)) {
					errors.push('<?php echo esc_js( __( 'Password must contain at least one special character.', 'ultimakit-for-wp' ) ); ?>');
				}
				
				return errors;
			}
			
			// Add validation to password fields
			$('input[name="pass1"], input[name="user_pass"], input[name="password"]').on('input', function() {
				var password = $(this).val();
				var errors = checkPasswordStrength(password);
				var $field = $(this);
				
				// Remove existing error messages
				$field.siblings('.password-strength-error').remove();
				
				// Add error messages if any
				if (errors.length > 0) {
					var errorHtml = '<div class="password-strength-error" style="color: #dc3232; font-size: 12px; margin-top: 5px;">';
					errorHtml += '<strong><?php echo esc_js( __( 'Password Requirements:', 'ultimakit-for-wp' ) ); ?></strong><br>';
					errorHtml += errors.join('<br>');
					errorHtml += '</div>';
					$field.after(errorHtml);
				}
			});
		});
		</script>
		<?php
	}

	/**
	 * Adds CSS styles for password strength indicators.
	 *
	 * @return void
	 */
	public function add_password_strength_styles() {
		?>
		<style type="text/css">
		.password-strength-error {
			background-color: #fff5f5;
			border: 1px solid #fed7d7;
			border-radius: 4px;
			padding: 8px 12px;
			margin-top: 5px;
		}
		
		.password-strength-error strong {
			color: #c53030;
		}
		
		.password-strength-error br {
			margin-bottom: 2px;
		}
		</style>
		<?php
	}
} 