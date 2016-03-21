<?php
/**
 * Tests toolbar-related functionality
 *
 * @since 1.2.0
 * @covers RDA_Remove_Access::hide_toolbar_actions()
 * @group toolbar
 */
class RDA_Test_Toolbar extends WP_UnitTestCase {

	/**
	 * Admin bar instance.
	 *
	 * @since 1.2.0
	 * @access protected
	 * @var WP_Admin_Bar $wp_admin_bar
	 */
	protected $wp_admin_bar;

	/**
	 * @since 1.2.0
	 */
	public function setUp() {
		parent::setUp();

		// Author as current user to trigger lock_it_up().
		$this->user_id = $this->factory->user->create( array( 'role' => 'author' ) );
		wp_set_current_user( $this->user_id );
	}

	/**
	 * @since 1.2.0
	 */
	public function test_default_hidden_front_end_toolbar_nodes_are_removed() {
		$this->set_up_RDA();

		$wp_admin_bar = $this->get_standard_admin_bar();

		$back_end_nodes_to_remove = array( 'about', 'comments', 'new-content' );

		$nodes = $wp_admin_bar->get_nodes();

		$this->assertFalse( in_array( $nodes, $back_end_nodes_to_remove ) );
		$this->assertArrayHasKey( 'edit-profile', $nodes );
	}

	/**
	 * @since 1.2.0
	 */
	public function test_default_hidden_front_end_toolbar_nodes_are_removed_including_profile() {
		$this->set_up_RDA( $profile_access = false );

		$wp_admin_bar = $this->get_standard_admin_bar();

		$back_end_nodes_to_remove = array( 'about', 'comments', 'new-content', 'edit-profile' );

		$this->assertFalse( in_array( $wp_admin_bar->get_nodes(), $back_end_nodes_to_remove ) );
	}

	/**
	 * @since 1.2.0
	 */
	public function test_default_hidden_back_end_toolbar_nodes_are_removed() {
		$this->set_up_RDA();

		$wp_admin_bar = $this->get_standard_admin_bar();

		$front_end_nodes_to_remove = array( 'about', 'dashboard', 'comments', 'new-content', 'edit' );

		$nodes = $wp_admin_bar->get_nodes();

		$this->assertFalse( in_array( $nodes, $front_end_nodes_to_remove ) );
		$this->assertArrayHasKey( 'edit-profile', $nodes );
	}

	/**
	 * @since 1.2.0
	 */
	public function test_default_hidden_back_end_toolbar_nodes_are_removed_including_profile() {
		$this->set_up_RDA( $profile_access = false );

		$wp_admin_bar = $this->get_standard_admin_bar();

		$front_end_nodes_to_remove = array( 'about', 'dashboard', 'comments', 'new-content', 'edit', 'edit-profile' );

		$this->assertFalse( in_array( $wp_admin_bar->get_nodes(), $front_end_nodes_to_remove ) );
	}

	/**
	 * Sets up RDA_Remove_Access for testing.
	 *
	 * @since 1.2.0
	 */
	public function set_up_RDA( $profile_access = true ) {
		// Fire the class.
		$capability = 'manage_options';

		if ( true !== $profile_access ) {
			update_option( 'rda_enable_profile', 0 );
		}

		$options = new RDA_Options();

		$access = new RDA_Remove_Access( $capability, $options->settings );
	}

	/**
	 * Utility method used to set up the Toolbar.
	 *
	 * @since 1.2.0
	 *
	 * @return WP_Admin_Bar Admin bar instance.
	 */
	protected function get_standard_admin_bar() {
		global $wp_admin_bar;

		_wp_admin_bar_init();

		$this->assertTrue( is_admin_bar_showing() );
		$this->assertInstanceOf( 'WP_Admin_Bar', $wp_admin_bar );

		do_action_ref_array( 'admin_bar_menu', array( &$wp_admin_bar ) );

		return $wp_admin_bar;
	}
}
