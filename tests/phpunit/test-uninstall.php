<?php
/**
 * Tests for uninstall.php (Finding #6).
 *
 * Direct web access without the WP_UNINSTALL_PLUGIN constant must be a no-op.
 * Real uninstall (constant defined) must delete every rda_* option.
 */

class Test_Uninstall extends RDA_TestCase {

	/**
	 * @var string Absolute path to the plugin's uninstall.php.
	 */
	private $uninstall_file;

	public function set_up() {
		parent::set_up();
		$this->uninstall_file = dirname( __DIR__, 2 ) . '/uninstall.php';

		// Seed every option this plugin owns so we can observe deletion.
		update_option( 'rda_access_switch',  'manage_options' );
		update_option( 'rda_access_cap',     'manage_options' );
		update_option( 'rda_redirect_url',   home_url() );
		update_option( 'rda_enable_profile', 1 );
		update_option( 'rda_login_message',  '' );
		update_option( 'rda_lock_ajax',      0 );
	}

	/**
	 * F#6 — Without `WP_UNINSTALL_PLUGIN`, the guard must short-circuit and
	 * leave every option intact. (Achieved by `return;` at file top-level.)
	 */
	public function test_uninstall_no_op_without_guard_constant() {
		$this->assertFalse(
			defined( 'WP_UNINSTALL_PLUGIN' ),
			'Precondition: WP_UNINSTALL_PLUGIN must not be defined in normal test runs.'
		);

		require $this->uninstall_file;

		$this->assertSame( 'manage_options', get_option( 'rda_access_switch' ) );
		$this->assertSame( 'manage_options', get_option( 'rda_access_cap' ) );
		$this->assertSame( home_url(),       get_option( 'rda_redirect_url' ) );
		$this->assertEquals( 1,              get_option( 'rda_enable_profile' ) );
		$this->assertSame( '',               get_option( 'rda_login_message' ) );
	}

	/**
	 * F#6 — With `WP_UNINSTALL_PLUGIN` defined (the real WP uninstall path),
	 * every rda_* option is removed.
	 *
	 * Uses `@runInSeparateProcess` because the constant cannot be undefined
	 * once set, and we don't want to taint the rest of the suite.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_uninstall_deletes_options_with_guard() {
		update_option( 'rda_access_switch',  'manage_options' );
		update_option( 'rda_access_cap',     'manage_options' );
		update_option( 'rda_redirect_url',   home_url() );
		update_option( 'rda_enable_profile', 1 );
		update_option( 'rda_login_message',  '' );

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'remove-dashboard-access/remove-dashboard-access.php' );
		}

		require dirname( __DIR__, 2 ) . '/uninstall.php';

		$this->assertFalse( get_option( 'rda_access_switch' ),  'rda_access_switch must be deleted.' );
		$this->assertFalse( get_option( 'rda_access_cap' ),     'rda_access_cap must be deleted.' );
		$this->assertFalse( get_option( 'rda_redirect_url' ),   'rda_redirect_url must be deleted.' );
		$this->assertFalse( get_option( 'rda_enable_profile' ), 'rda_enable_profile must be deleted.' );
		$this->assertFalse( get_option( 'rda_login_message' ),  'rda_login_message must be deleted.' );
		$this->assertFalse( get_option( 'rda_lock_ajax' ),      'rda_lock_ajax must be deleted.' );
	}
}
