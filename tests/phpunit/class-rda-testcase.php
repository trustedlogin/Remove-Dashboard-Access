<?php
/**
 * Shared base test case for Remove Dashboard Access security tests.
 */

if ( ! class_exists( 'RDA_Redirected_Exception' ) ) {
	/**
	 * Thrown by the `wp_redirect` filter so we can assert the would-be redirect
	 * URL without letting `dashboard_redirect()` actually call `exit;`.
	 */
	class RDA_Redirected_Exception extends \RuntimeException {

		/**
		 * @var string The URL the plugin tried to redirect to.
		 */
		public $location;

		/**
		 * @var int The HTTP status code passed to wp_redirect.
		 */
		public $status;

		public function __construct( $location, $status = 302 ) {
			$this->location = (string) $location;
			$this->status   = (int) $status;
			parent::__construct( "Redirect to: {$location} (status {$status})" );
		}
	}
}

abstract class RDA_TestCase extends WP_UnitTestCase {

	/**
	 * @var RDA_Remove_Access|null Instance under test (when created via $this->make_access()).
	 */
	protected $access;

	/**
	 * @var array The original $_GET so tests can mutate freely.
	 */
	protected $original_get;

	/**
	 * @var \Closure|null Redirect-capture filter; removed in tearDown if set.
	 */
	private $redirect_capture_filter;

	public function set_up() {
		parent::set_up();

		$this->original_get = $_GET;
		$_GET               = array();
	}

	public function tear_down() {
		$_GET = $this->original_get;

		if ( $this->access instanceof RDA_Remove_Access ) {
			remove_action( 'init',          array( $this->access, 'is_user_allowed' ) );
			remove_action( 'admin_init',    array( $this->access, 'dashboard_redirect' ) );
			remove_action( 'admin_head',    array( $this->access, 'hide_menus' ) );
			remove_action( 'admin_bar_menu', array( $this->access, 'hide_toolbar_items' ), 999 );
			$this->access = null;
		}

		if ( $this->redirect_capture_filter instanceof \Closure ) {
			remove_filter( 'wp_redirect', $this->redirect_capture_filter, 1 );
			$this->redirect_capture_filter = null;
		}

		parent::tear_down();
	}

	/**
	 * Build a fresh RDA_Remove_Access wired to the given options.
	 *
	 * @param array $settings Optional overrides; merged on top of plugin defaults.
	 * @return RDA_Remove_Access
	 */
	protected function make_access( array $settings = array() ) {
		$defaults = array(
			'access_switch'  => 'manage_options',
			'access_cap'     => 'manage_options',
			'enable_profile' => true,
			'redirect_url'   => home_url(),
			'login_message'  => '',
			'lock_ajax'      => 0,
			'url_allowlist'  => '',
		);

		$settings = array_merge( $defaults, $settings );

		$this->access = new RDA_Remove_Access( $settings['access_cap'], $settings );

		return $this->access;
	}

	/**
	 * Invoke a protected/private instance method by name.
	 *
	 * @param object $instance Object to invoke against.
	 * @param string $method   Method name.
	 * @param array  $args     Positional args.
	 * @return mixed
	 */
	protected function invoke( $instance, $method, array $args = array() ) {
		$ref = new \ReflectionMethod( $instance, $method );
		$ref->setAccessible( true );
		return $ref->invokeArgs( $instance, $args );
	}

	/**
	 * Hook `wp_redirect` so any redirect attempt throws RDA_Redirected_Exception
	 * with the URL + status, letting tests assert without actually exiting.
	 */
	protected function capture_redirects() {
		$this->redirect_capture_filter = static function ( $location, $status ) {
			throw new RDA_Redirected_Exception( $location, $status );
		};

		add_filter( 'wp_redirect', $this->redirect_capture_filter, 1, 2 );
	}

	/**
	 * Create a user with the given role and switch the current user to them.
	 *
	 * @param string $role WP role slug.
	 * @return int User ID.
	 */
	protected function login_as( $role ) {
		$user_id = self::factory()->user->create( array( 'role' => $role ) );
		wp_set_current_user( $user_id );
		return $user_id;
	}

	/**
	 * Force `$pagenow` to a specific value for the duration of a test.
	 *
	 * @param string $pagenow Value to set.
	 */
	protected function set_pagenow( $pagenow ) {
		$GLOBALS['pagenow'] = $pagenow;
	}
}
