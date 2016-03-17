<?php
/**
 * @covers RDA_Remove_Access::is_user_allowed()
 * @group capabilities
 */
class RDA_Test_Capabilities extends WP_UnitTestCase {

	/**
	 * User ID.
	 *
	 * @since 1.2.0
	 * @var int
	 */
	public $user_id = 0;

	/**
	 * @since 1.2.0
	 */
	public function setUp() {
		parent::setUp();

		$this->user_id = $this->factory->user->create( array( 'role' => 'author' ) );

		wp_set_current_user( $this->user_id );
	}

	/**
	 * @since 1.2.0
	 */
	public function test_user_with_allowed_capability_is_allowed() {
		$capability = 'edit_posts';
		$options    = new RDA_Options();

		$access = new RDA_Remove_Access( $capability, $options->settings );

		$this->assertSame( $capability, $access->capability );
		$this->assertTrue( $access->is_user_allowed() );
	}

	/**
	 * @since 1.2.0
	 */
	public function test_user_without_allowed_capability_is_not_allowed() {
		$capability = 'manage_options';
		$options    = new RDA_Options();

		$access = new RDA_Remove_Access( $capability, $options->settings );

		$this->assertSame( $capability, $access->capability );
		$this->assertFalse( $access->is_user_allowed() );
	}

}
