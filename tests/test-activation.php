<?php
/**
 * Tests settings set upon activation of the plugin
 *
 * @since 1.2.0
 * @covers RDA_Options::activate()
 * @group activation
 */
class RDA_Test_Activation extends WP_UnitTestCase {

	/**
	 * @since 1.2.0
	 */
	public function setUp() {
		parent::setUp();

		// "Activate" the plugin to populate the defaults option values.
		@RDA_Options::activate();
	}
	/**
	 * @since 1.2.0
	 */
	public function test_activation_access_switch_setting_default() {
		$switch_option = get_option( 'rda_access_switch' );

		$this->assertNotFalse( $switch_option );
		$this->assertSame( 'manage_options', $switch_option );
	}

	/**
	 * @since 1.2.0
	 */
	public function test_activation_access_cap_setting_default() {
		$access_cap_option = get_option( 'rda_access_cap' );

		$this->assertNotFalse( $access_cap_option );
		$this->assertSame( 'manage_options', $access_cap_option );
	}

	/**
	 * @since 1.2.0
	 */
	public function test_activation_redirect_url_setting_default() {
		$redirect_url_option = get_option( 'rda_redirect_url' );

		$this->assertNotFalse( $redirect_url_option );
		$this->assertSame( esc_url( WP_TESTS_DOMAIN ), $redirect_url_option );
	}

	/**
	 * @since 1.2.0
	 */
	public function test_activation_enable_profile_setting_default() {
		$enable_profile_option = get_option( 'rda_enable_profile' );

		$this->assertNotFalse( $enable_profile_option );
		$this->assertSame( 1, $enable_profile_option );
	}

	/**
	 * @since 1.2.0
	 */
	public function test_activation_login_message_default() {
		$login_message_option = get_option( 'rda_login_message' );

		$this->assertEmpty( $login_message_option );
	}
}