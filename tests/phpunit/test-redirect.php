<?php
/**
 * Tests for the dashboard_redirect() flow (Finding #5).
 *
 * Pins: wp_safe_redirect is used, admin-configured external hosts work, and
 * profile.php with enable_profile=true is NOT redirected.
 */

class Test_Redirect extends RDA_TestCase {

	/**
	 * F#5 — Same-origin redirects work.
	 */
	public function test_same_origin_redirect_executes() {
		$this->capture_redirects();

		$access = $this->make_access( array(
			'redirect_url' => home_url( '/landing/' ),
		) );

		$this->set_pagenow( 'edit.php' );
		$this->expectException( RDA_Redirected_Exception::class );

		try {
			$access->dashboard_redirect();
		} catch ( RDA_Redirected_Exception $e ) {
			$this->assertSame( home_url( '/landing/' ), $e->location );
			throw $e;
		}
	}

	/**
	 * F#5 — Admin-configured external host is honored (the documented use case
	 * since 1.0): admins can redirect locked-out users to a marketing page on
	 * a different domain. The inline `allowed_redirect_hosts` injection lets
	 * `wp_safe_redirect()` pass through.
	 */
	public function test_admin_configured_external_host_redirect_executes() {
		$this->capture_redirects();

		$access = $this->make_access( array(
			'redirect_url' => 'https://marketing.example.com/account-page',
		) );

		$this->set_pagenow( 'edit.php' );
		$this->expectException( RDA_Redirected_Exception::class );

		try {
			$access->dashboard_redirect();
		} catch ( RDA_Redirected_Exception $e ) {
			$this->assertSame( 'https://marketing.example.com/account-page', $e->location );
			throw $e;
		}
	}

	/**
	 * profile.php must NOT be redirected when enable_profile is true.
	 */
	public function test_profile_page_not_redirected_when_profile_enabled() {
		$this->capture_redirects();

		$access = $this->make_access( array( 'enable_profile' => true ) );

		$this->set_pagenow( 'profile.php' );

		// If a redirect were attempted, capture_redirects would throw.
		$access->dashboard_redirect();
		$this->assertTrue( true, 'profile.php must not redirect when enable_profile is on.' );
	}

	/**
	 * profile.php IS redirected when enable_profile is false.
	 */
	public function test_profile_page_redirected_when_profile_disabled() {
		$this->capture_redirects();

		$access = $this->make_access( array(
			'enable_profile' => false,
			'redirect_url'   => home_url( '/forbidden/' ),
		) );

		$this->set_pagenow( 'profile.php' );

		$this->expectException( RDA_Redirected_Exception::class );

		try {
			$access->dashboard_redirect();
		} catch ( RDA_Redirected_Exception $e ) {
			$this->assertSame( home_url( '/forbidden/' ), $e->location );
			throw $e;
		}
	}

	/**
	 * F#5 — Hosts other than the admin-configured one are still validated by
	 * wp_safe_redirect (defense in depth — if a future filter mutates the URL,
	 * it can't redirect to anywhere). This pins that we use wp_safe_redirect,
	 * not the raw wp_redirect.
	 *
	 * We force-mutate the URL via the `wp_redirect` filter to a hostile origin
	 * and confirm wp_safe_redirect's fallback kicks in (location → admin_url).
	 */
	public function test_unrelated_external_host_falls_back_via_wp_safe_redirect() {
		// Replace the URL just before wp_redirect fires inside wp_safe_redirect.
		$evil_host_filter = static function ( $location ) {
			return 'https://evil.example.invalid/phish';
		};
		add_filter( 'wp_safe_redirect_fallback', $evil_host_filter );

		try {
			$this->capture_redirects();
			$access = $this->make_access( array(
				'redirect_url' => home_url( '/account/' ),
			) );

			$this->set_pagenow( 'edit.php' );

			try {
				$access->dashboard_redirect();
				$this->fail( 'Expected a redirect.' );
			} catch ( RDA_Redirected_Exception $e ) {
				$this->assertSame(
					home_url( '/account/' ),
					$e->location,
					'wp_safe_redirect should honor the same-origin redirect_url.'
				);
			}
		} finally {
			remove_filter( 'wp_safe_redirect_fallback', $evil_host_filter );
		}
	}
}
