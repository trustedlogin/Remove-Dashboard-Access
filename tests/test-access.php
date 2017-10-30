<?php
/**
 * Tests RDA_Remove_Access functionality
 *
 * @since 1.2.0
 * @group access
 */
class RDA_Test_Access extends WP_UnitTestCase {

	/**
	 * @covers RDA_Remove_Access::get_allowed_pages()
	 */
	public function test_get_allowed_pages_should_always_return_an_array() {
		$access = $this->set_up_RDA();

		$this->assertSame( 'array', gettype( $access->get_allowed_pages() ) );
	}

	/**
	 * @covers RDA_Remove_Access::get_allowed_pages()
	 */
	public function test_get_allowed_pages_array_should_always_contain_profile_php() {
		$access = $this->set_up_RDA();

		$this->assertTrue( in_array( 'profile.php', $access->get_allowed_pages(), true ) );
	}

	/**
	 * Sets up RDA_Remove_Access for testing.
	 *
	 * @since 1.2.0
	 *
	 * @return \RDA_Remove_Access RDA_Remove_Access instance.
	 */
	public function set_up_RDA( $profile_access = true ) {
		// Fire the class.
		$capability = 'manage_options';

		if ( true !== $profile_access ) {
			update_option( 'rda_enable_profile', 0 );
		}

		$options = new RDA_Options();

		$access = new RDA_Remove_Access( $capability, $options->settings );

		return $access;
	}

}
