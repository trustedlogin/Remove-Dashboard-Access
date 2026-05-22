<?php
/**
 * Tests for the allowlist matcher (Findings #2, #3, #7).
 *
 * F#2 — admin.php?page=<slug> must verify the submenu is actually registered.
 * F#3 — `array()` page entry means "no GET constraint," not "never matches."
 * F#7 — Subset-based matcher, not exact-count.
 */

class Test_Allowlist extends RDA_TestCase {

	/**
	 * F#3 — `'admin-post.php' => array()` (default allowlist shape) means
	 * "allow admin-post.php regardless of $_GET."
	 */
	public function test_empty_page_entry_allows_request_with_no_get() {
		$this->set_pagenow( 'admin-post.php' );
		$_GET = array();

		$access = $this->make_access();

		$this->assertTrue(
			$this->invoke( $access, 'is_allowed_page' ),
			'admin-post.php with no GET params should be allowed by the empty-entry rule.'
		);
	}

	/**
	 * F#3 — empty-entry allowance also covers requests carrying GET params,
	 * since "no constraints" applies regardless.
	 */
	public function test_empty_page_entry_allows_request_with_extra_get() {
		$this->set_pagenow( 'admin-post.php' );
		$_GET = array( 'action' => 'something', 'nonce' => 'abc' );

		$access = $this->make_access();

		$this->assertTrue(
			$this->invoke( $access, 'is_allowed_page' ),
			'admin-post.php with GET params should still be allowed.'
		);
	}

	/**
	 * Pages NOT in the allowlist always fail.
	 */
	public function test_unknown_page_is_blocked() {
		$this->set_pagenow( 'edit.php' );
		$_GET = array();

		$access = $this->make_access();

		$this->assertFalse(
			$this->invoke( $access, 'is_allowed_page' ),
			'edit.php is not in the allowlist and must not be allowed.'
		);
	}

	/**
	 * F#7 — Subset matcher: legitimate sub-flows of an allowed page (with extra
	 * GET params like 2FA `otp=…`) must still match the allowlist entry.
	 */
	public function test_subset_matcher_allows_extra_params() {
		$this->login_as( 'administrator' );
		// add_menu_page's underlying add_action is gated by current_user_can()
		// at registration time, so we log in as admin to make the hook stick.
		add_menu_page( 'WFLS', 'WFLS', 'read', 'WFLS', '__return_null' );

		$this->set_pagenow( 'admin.php' );
		$_GET = array(
			'page'        => 'WFLS',
			'wfls-action' => 'otp',
			'otp'         => '123456',
		);

		$access = $this->make_access();

		$this->assertTrue(
			$this->invoke( $access, 'is_allowed_page' ),
			'WFLS sub-flow with extra GET params must still match the allowlist.'
		);
	}

	/**
	 * F#7 — Subset matcher still rejects when the required key is missing or
	 * the value differs from the allowed value.
	 */
	public function test_subset_matcher_rejects_wrong_value() {
		$this->set_pagenow( 'admin.php' );
		$_GET = array( 'page' => 'NOT_WFLS' );

		$access = $this->make_access();

		$this->assertFalse(
			$this->invoke( $access, 'is_allowed_page' ),
			'admin.php with page=NOT_WFLS must NOT match the WFLS allowlist entry.'
		);
	}

	public function test_subset_matcher_rejects_missing_key() {
		$this->set_pagenow( 'admin.php' );
		$_GET = array( 'unrelated' => 'value' );

		$access = $this->make_access();

		$this->assertFalse(
			$this->invoke( $access, 'is_allowed_page' ),
			'admin.php with no page key must NOT match the WFLS allowlist entry.'
		);
	}

	/**
	 * F#2 — `admin.php?page=WFLS` must only resolve to an allowed page when
	 * the WFLS submenu is actually registered. With Wordfence not installed,
	 * the URL must NOT expose the admin chrome.
	 */
	public function test_admin_php_blocked_when_target_page_not_registered() {
		$this->set_pagenow( 'admin.php' );
		$_GET = array( 'page' => 'WFLS' );

		$access = $this->make_access();

		// Reset admin menu globals so no pages are registered for this test.
		$GLOBALS['_registered_pages'] = array();
		$GLOBALS['admin_page_hooks']  = array();

		$this->assertFalse(
			$this->invoke( $access, 'is_allowed_page' ),
			'admin.php?page=WFLS must be blocked when WFLS is not registered.'
		);
	}

	/**
	 * F#2 — When the target page IS registered (e.g. Wordfence is installed),
	 * the WFLS allowlist entry works as documented.
	 */
	public function test_admin_php_allowed_when_target_page_registered() {
		$this->set_pagenow( 'admin.php' );
		$_GET = array( 'page' => 'WFLS' );

		// Pretend Wordfence registered the WFLS admin page.
		$this->login_as( 'administrator' );
		// add_menu_page's underlying add_action is gated by current_user_can()
		// at registration time, so we log in as admin to make the hook stick.
		add_menu_page( 'WFLS', 'WFLS', 'read', 'WFLS', '__return_null' );

		$access = $this->make_access();

		$this->assertTrue(
			$this->invoke( $access, 'is_allowed_page' ),
			'admin.php?page=WFLS must be allowed when WFLS IS registered.'
		);
	}

	/**
	 * F#3 — The `rda_allowlist` filter still works and supports the new
	 * empty-array shape for third-party integrations.
	 */
	public function test_filter_can_add_open_admin_page_entry() {
		$filter = static function ( $allowlist ) {
			$allowlist['custom-page.php'] = array();
			return $allowlist;
		};
		add_filter( 'rda_allowlist', $filter );

		$this->set_pagenow( 'custom-page.php' );
		$_GET = array( 'whatever' => 'goes' );

		$access = $this->make_access();

		$this->assertTrue(
			$this->invoke( $access, 'is_allowed_page' ),
			'rda_allowlist filter must accept empty-array entries.'
		);

		remove_filter( 'rda_allowlist', $filter );
	}
}
