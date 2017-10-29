<?php
/**
 * Tests toolbar-related functionality
 *
 * @since 1.2.0
 * @group options
 */
class RDA_Test_Options extends WP_UnitTestCase {

	/**
	 * Default caps fixture.
	 *
	 * @var array
	 * @static
	 */
	protected static $default_caps;

	/**
	 * Set up fixtures once.
	 */
	public static function wpSetUpBeforeClass() {
		$options = new RDA_Options();

		self::$default_caps = RDA_Options::get_default_caps();
	}

	/**
	 * Sets up before each test.
	 */
	public function setUp() {
		parent::setUp();

		// Author as current user to trigger lock_it_up().
		$this->user_id = $this->factory->user->create( array( 'role' => 'author' ) );
		wp_set_current_user( $this->user_id );
	}

	/**
	 * Admin
	 *
	 * @covers RDA_Options::get_warning_message()
	 */
	public function test_get_warning_message_with_admin_cap_switch_value_should_retrieve_admin_warning_string() {
		$options  = $this->set_up_RDA();
		$expected = "<strong>Warning:</strong> Your account doesn&#8217;t have the Admin capability, %s, which could lock you out of the dashboard.";

		$this->assertSame( $expected, $options->get_warning_message( self::$default_caps['admin'] ) );
	}

	/**
	 * Editor/Admin
	 *
	 * @covers RDA_Options::get_warning_message()
	 */
	public function test_get_warning_message_with_editor_cap_switch_value_should_retrieve_editor_admin_warning_string() {
		$options  = $this->set_up_RDA();
		$expected = "<strong>Warning:</strong> Your account doesn&#8217;t have the Editor or Admin capability, %s, which could lock you out of the dashboard.";

		$this->assertSame( $expected, $options->get_warning_message( self::$default_caps['editor'] ) );
	}

	/**
	 * Author/Editor/Admin
	 *
	 * @covers RDA_Options::get_warning_message()
	 */
	public function test_get_warning_message_with_author_switch_value_should_retrieve_author_warning_string() {
		$options  = $this->set_up_RDA();
		$expected = "<strong>Warning:</strong> Your account doesn&#8217;t have the Author, Editor, or Admin capability, %s, which could lock you out of the dashboard.";

		$this->assertSame( $expected, $options->get_warning_message( self::$default_caps['author'] ) );
	}

	/**
	 * capability:*
	 *
	 * @covers RDA_Options::get_warning_message()
	 */
	public function test_get_warning_message_with_capability_switch_should_retrieve_generic_warning_string() {
		$options  = $this->set_up_RDA();
		$expected = "<strong>Warning:</strong> Your account doesn&#8217;t have the %s capability, which could lock you out of the dashboard.";

		$this->assertSame( $expected, $options->get_warning_message( 'capability' ) );
	}

	/**
	 * *:*
	 *
	 * @covers RDA_Options::get_warning_message()
	 */
	public function test_get_warning_message_with_invalid_switch_should_retrieve_generic_warning_string() {
		$options  = $this->set_up_RDA();
		$expected = "<strong>Warning:</strong> Your account doesn&#8217;t have the %s capability, which could lock you out of the dashboard.";

		$this->assertSame( $expected, $options->get_warning_message( 'foo' ) );
	}

	/**
	 * @covers RDA_Options::get_default_caps()
	 */
	public function test_get_default_caps_should_retrieve_default_caps() {
		$expected = array(
			'admin'  => 'manage_options',
			'editor' => 'edit_others_posts',
			'author' => 'publish_posts'
		);

		$this->assertEqualSets( $expected, RDA_Options::get_default_caps() );
	}

	/**
	 * @covers RDA_Options::get_default_caps()
	 */
	public function test_get_default_caps_should_always_contain_an_admin_value_even_if_filtered() {
		add_filter( 'rda_default_caps_for_role', '__return_empty_array' );

		$this->assertArrayHasKey( 'admin', RDA_Options::get_default_caps() );

		remove_filter( 'rda_default_caps_for_role', '__return_empty_array' );
	}

	/**
	 * @covers RDA_Options::get_default_caps()
	 */
	public function test_get_default_caps_should_always_contain_an_editor_value_even_if_filtered() {
		add_filter( 'rda_default_caps_for_role', '__return_empty_array' );

		$this->assertArrayHasKey( 'editor', RDA_Options::get_default_caps() );

		remove_filter( 'rda_default_caps_for_role', '__return_empty_array' );
	}

	/**
	 * @covers RDA_Options::get_default_caps()
	 */
	public function test_get_default_caps_should_always_contain_an_author_value_even_if_filtered() {
		add_filter( 'rda_default_caps_for_role', '__return_empty_array' );

		$this->assertArrayHasKey( 'author', RDA_Options::get_default_caps() );

		remove_filter( 'rda_default_caps_for_role', '__return_empty_array' );
	}


	/**
	 * Sets up RDA_Remove_Access for testing.
	 *
	 * @since 1.2.0
	 *
	 * @return \RDA_Options Options instance.
	 */
	public function set_up_RDA( $profile_access = true ) {
		// Fire the class.
		$capability = 'manage_options';

		if ( true !== $profile_access ) {
			update_option( 'rda_enable_profile', 0 );
		}

		$options = new RDA_Options();

		$access = new RDA_Remove_Access( $capability, $options->settings );

		return $options;
	}

}
