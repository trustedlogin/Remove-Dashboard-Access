<?php
/**
 * Tests for the settings sanitize callbacks (Finding #4).
 *
 * The lock can be silently disabled if an admin saves a degenerate cap (empty
 * string, 'read', arbitrary text) — these tests pin the validated behavior.
 */

class Test_Sanitizers extends RDA_TestCase {

	/**
	 * @var RDA_Options
	 */
	protected $options;

	public function set_up() {
		parent::set_up();
		$this->options = new RDA_Options();
	}

	/**
	 * F#4 — sanitize_access_switch must accept the four documented values
	 * (the three role-default caps + the literal 'capability').
	 */
	public function test_sanitize_access_switch_accepts_documented_values() {
		$cases = array( 'manage_options', 'edit_others_posts', 'publish_posts', 'capability' );

		foreach ( $cases as $value ) {
			$this->assertSame(
				$value,
				$this->options->sanitize_access_switch( $value ),
				"sanitize_access_switch should accept '{$value}' unchanged."
			);
		}
	}

	/**
	 * F#4 — Empty / null / arbitrary strings must fall back to a safe default
	 * (manage_options), never silently store the garbage value.
	 */
	public function test_sanitize_access_switch_rejects_garbage() {
		$garbage = array( '', 'read', 'foobar', '<script>alert(1)</script>', '0', null );

		foreach ( $garbage as $value ) {
			$this->assertSame(
				'manage_options',
				$this->options->sanitize_access_switch( $value ),
				"sanitize_access_switch must reject " . var_export( $value, true ) . " and return 'manage_options'."
			);
		}
	}

	/**
	 * F#4 — sanitize_access_cap must accept any capability that any role grants.
	 */
	public function test_sanitize_access_cap_accepts_valid_wp_caps() {
		$valid = array( 'manage_options', 'edit_posts', 'publish_posts', 'edit_others_posts', 'read' );

		foreach ( $valid as $cap ) {
			$this->assertSame(
				$cap,
				$this->options->sanitize_access_cap( $cap ),
				"sanitize_access_cap should accept the registered WP capability '{$cap}'."
			);
		}
	}

	/**
	 * F#4 — Empty / unknown / dangerous strings fall back to rda_access_switch.
	 */
	public function test_sanitize_access_cap_rejects_garbage() {
		update_option( 'rda_access_switch', 'manage_options' );

		$garbage = array( '', 'definitely_not_a_real_cap', '<script>', '0', null );

		foreach ( $garbage as $value ) {
			$this->assertSame(
				'manage_options',
				$this->options->sanitize_access_cap( $value ),
				"sanitize_access_cap must reject " . var_export( $value, true ) . " and fall back to manage_options."
			);
		}
	}

	/**
	 * sanitize_redirect_url still escapes with esc_url_raw and defaults to home_url.
	 */
	public function test_sanitize_redirect_url_passes_through_safe_urls() {
		$safe = 'https://example.com/landing';
		$this->assertSame( $safe, $this->options->sanitize_redirect_url( $safe ) );

		$this->assertSame( home_url(), $this->options->sanitize_redirect_url( '' ) );
	}

	/**
	 * sanitize_redirect_url strips disallowed schemes (javascript:, data:).
	 */
	public function test_sanitize_redirect_url_strips_unsafe_schemes() {
		$this->assertSame( '', $this->options->sanitize_redirect_url( 'javascript:alert(1)' ) );
	}

	/**
	 * sanitize_login_message strips tags via sanitize_text_field.
	 */
	public function test_sanitize_login_message_strips_html() {
		$dirty = 'Welcome <script>alert(1)</script> back!';
		$clean = $this->options->sanitize_login_message( $dirty );

		$this->assertStringNotContainsString( '<script>', $clean );
		$this->assertStringContainsString( 'Welcome', $clean );
	}

	/**
	 * sanitize_enable_profile coerces to a bool.
	 */
	public function test_sanitize_enable_profile_coerces_to_bool() {
		$this->assertTrue( $this->options->sanitize_enable_profile( 1 ) );
		$this->assertTrue( $this->options->sanitize_enable_profile( '1' ) );
		$this->assertFalse( $this->options->sanitize_enable_profile( 0 ) );
		$this->assertFalse( $this->options->sanitize_enable_profile( '' ) );
	}

	/**
	 * sanitize_lock_ajax returns a strict 0/1 — no leaking truthy strings,
	 * arrays, or arbitrary input into the rda_lock_ajax option.
	 */
	public function test_sanitize_lock_ajax_returns_strict_bool_int() {
		$this->assertSame( 1, $this->options->sanitize_lock_ajax( 1 ) );
		$this->assertSame( 1, $this->options->sanitize_lock_ajax( '1' ) );
		$this->assertSame( 1, $this->options->sanitize_lock_ajax( 'on' ) );

		$this->assertSame( 0, $this->options->sanitize_lock_ajax( 0 ) );
		$this->assertSame( 0, $this->options->sanitize_lock_ajax( '' ) );
		$this->assertSame( 0, $this->options->sanitize_lock_ajax( null ) );
	}
}
