<?php
/**
 * Tests for the core access-control gate (Finding #1 — default AJAX behavior + strict mode).
 */

class Test_Access_Control extends RDA_TestCase {

	/**
	 * Subscribers (no manage_options) should land on `lock_it_up()` and have
	 * the admin_init redirect hook registered.
	 */
	public function test_subscriber_triggers_lock() {
		$this->login_as( 'subscriber' );
		$access = $this->make_access( array( 'access_cap' => 'manage_options' ) );

		$access->is_user_allowed();

		$this->assertTrue(
			(bool) has_action( 'admin_init', array( $access, 'dashboard_redirect' ) ),
			'Subscribers should have the dashboard_redirect hook registered.'
		);
	}

	/**
	 * Admins should pass the cap check and NOT trigger the lock.
	 */
	public function test_admin_does_not_trigger_lock() {
		$this->login_as( 'administrator' );
		$access = $this->make_access( array( 'access_cap' => 'manage_options' ) );

		$access->is_user_allowed();

		$this->assertFalse(
			has_action( 'admin_init', array( $access, 'dashboard_redirect' ) ),
			'Admins should NOT have the dashboard_redirect hook registered.'
		);
	}

	/**
	 * If `$this->capability` is empty (e.g. a filter zeroed it), the plugin
	 * should bail out of the construction phase entirely and never register
	 * the init hook. This pins the "empty cap = no restriction" behavior.
	 */
	public function test_empty_capability_constructor_bails() {
		$this->login_as( 'subscriber' );
		$access = new RDA_Remove_Access( '', array() );

		// Constructor short-circuits, so init hook never registers.
		$this->assertFalse(
			has_action( 'init', array( $access, 'is_user_allowed' ) ),
			'Constructor with empty cap must NOT register the init hook.'
		);
	}

	/**
	 * Default AJAX behavior: the lock is bypassed (matches the
	 * documented "AJAX endpoints do their own cap checks" carve-out).
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_ajax_default_skips_lock() {
		if ( ! defined( 'DOING_AJAX' ) ) {
			define( 'DOING_AJAX', true );
		}

		$this->login_as( 'subscriber' );
		$access = $this->make_access();

		$access->is_user_allowed();

		$this->assertFalse(
			has_action( 'admin_init', array( $access, 'dashboard_redirect' ) ),
			'Default AJAX path must NOT register the lock.'
		);
	}

	/**
	 * Strict AJAX mode: when `rda_strict_ajax` returns true, the lock applies
	 * to AJAX too. Subscribers hitting admin-ajax get the redirect hook.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_ajax_strict_mode_applies_lock() {
		if ( ! defined( 'DOING_AJAX' ) ) {
			define( 'DOING_AJAX', true );
		}

		add_filter( 'rda_strict_ajax', '__return_true' );

		$this->login_as( 'subscriber' );
		$access = $this->make_access();

		$access->is_user_allowed();

		$this->assertTrue(
			(bool) has_action( 'admin_init', array( $access, 'dashboard_redirect' ) ),
			'Strict AJAX mode must register the lock for non-cap-holders.'
		);

		remove_filter( 'rda_strict_ajax', '__return_true' );
	}

	/**
	 * The `rda_lock_ajax` setting (admin checkbox) seeds the default for
	 * `rda_strict_ajax`. When the setting is ON and no filter overrides it,
	 * AJAX requests get the lock just like a regular admin URL.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_ajax_setting_enabled_applies_lock() {
		if ( ! defined( 'DOING_AJAX' ) ) {
			define( 'DOING_AJAX', true );
		}

		$this->login_as( 'subscriber' );
		$access = $this->make_access( array( 'lock_ajax' => 1 ) );

		$access->is_user_allowed();

		$this->assertTrue(
			(bool) has_action( 'admin_init', array( $access, 'dashboard_redirect' ) ),
			'rda_lock_ajax=1 must register the lock on AJAX requests without needing a filter.'
		);
	}

	/**
	 * The `rda_strict_ajax` filter has the final word — code can force the
	 * lock OFF even when the admin checkbox is ON.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_filter_overrides_setting() {
		if ( ! defined( 'DOING_AJAX' ) ) {
			define( 'DOING_AJAX', true );
		}

		// Setting ON, but filter forces OFF.
		add_filter( 'rda_strict_ajax', '__return_false' );

		$this->login_as( 'subscriber' );
		$access = $this->make_access( array( 'lock_ajax' => 1 ) );

		$access->is_user_allowed();

		$this->assertFalse(
			has_action( 'admin_init', array( $access, 'dashboard_redirect' ) ),
			'rda_strict_ajax filter must be able to override the setting.'
		);

		remove_filter( 'rda_strict_ajax', '__return_false' );
	}

	/**
	 * Setting off + no filter = current default behavior (AJAX exempt).
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_ajax_setting_disabled_keeps_bypass() {
		if ( ! defined( 'DOING_AJAX' ) ) {
			define( 'DOING_AJAX', true );
		}

		$this->login_as( 'subscriber' );
		$access = $this->make_access( array( 'lock_ajax' => 0 ) );

		$access->is_user_allowed();

		$this->assertFalse(
			has_action( 'admin_init', array( $access, 'dashboard_redirect' ) ),
			'rda_lock_ajax=0 (default) must preserve the AJAX-exempt behavior.'
		);
	}
}
