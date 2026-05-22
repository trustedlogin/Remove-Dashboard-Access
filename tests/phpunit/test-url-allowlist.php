<?php
/**
 * Tests for the admin-editable URL allow-list (1.3.0 feature).
 *
 * The feature exposes a textarea on Settings → Dashboard Access where an
 * admin can paste any number of URLs that should be exempt from the lock.
 * Sanitization on save converts each line to a same-origin relative path;
 * matching reuses the existing pagenow + GET-subset gate.
 */

class Test_URL_Allowlist extends RDA_TestCase {

	/**
	 * @var RDA_Options
	 */
	protected $options;

	public function set_up() {
		parent::set_up();
		$this->options = new RDA_Options();
	}

	// ----------------------------------------------------------------------
	// sanitize_url_allowlist — normalization on save
	// ----------------------------------------------------------------------

	public function test_sanitize_empty_input_returns_empty_string() {
		$this->assertSame( '', $this->options->sanitize_url_allowlist( '' ) );
		$this->assertSame( '', $this->options->sanitize_url_allowlist( null ) );
	}

	public function test_sanitize_single_relative_path_kept_verbatim() {
		$this->assertSame(
			'/wp-admin/admin.php?page=trustedlogin-secrets',
			$this->options->sanitize_url_allowlist( '/wp-admin/admin.php?page=trustedlogin-secrets' )
		);
	}

	public function test_sanitize_absolute_same_host_url_converted_to_relative() {
		$absolute = home_url( '/wp-admin/admin.php?page=foo' );
		$this->assertSame(
			'/wp-admin/admin.php?page=foo',
			$this->options->sanitize_url_allowlist( $absolute )
		);
	}

	public function test_sanitize_absolute_foreign_host_dropped() {
		$result = $this->options->sanitize_url_allowlist( 'https://evil.example/wp-admin/admin.php' );
		$this->assertSame( '', $result, 'External hosts must not be allowed-listed; they can never match a same-origin request.' );
	}

	public function test_sanitize_protocol_relative_url_dropped() {
		$result = $this->options->sanitize_url_allowlist( '//evil.example/wp-admin/admin.php' );
		$this->assertSame( '', $result, 'Protocol-relative URLs invite host-confusion bypasses; reject outright.' );
	}

	public function test_sanitize_missing_leading_slash_is_prepended() {
		$this->assertSame(
			'/wp-admin/admin.php',
			$this->options->sanitize_url_allowlist( 'wp-admin/admin.php' )
		);
	}

	public function test_sanitize_strips_fragments() {
		$this->assertSame(
			'/wp-admin/admin.php?page=foo',
			$this->options->sanitize_url_allowlist( '/wp-admin/admin.php?page=foo#section' )
		);
	}

	public function test_sanitize_trims_whitespace_per_line() {
		$raw = "   /wp-admin/admin.php   \n  /wp-admin/profile.php  ";
		$this->assertSame(
			"/wp-admin/admin.php\n/wp-admin/profile.php",
			$this->options->sanitize_url_allowlist( $raw )
		);
	}

	public function test_sanitize_blank_lines_dropped() {
		$raw = "\n/wp-admin/admin.php\n\n\n/wp-admin/profile.php\n";
		$this->assertSame(
			"/wp-admin/admin.php\n/wp-admin/profile.php",
			$this->options->sanitize_url_allowlist( $raw )
		);
	}

	public function test_sanitize_dedupes_after_normalization() {
		$raw = home_url( '/wp-admin/admin.php?page=foo' ) . "\n/wp-admin/admin.php?page=foo";
		$this->assertSame(
			'/wp-admin/admin.php?page=foo',
			$this->options->sanitize_url_allowlist( $raw ),
			'Absolute and relative spellings of the same URL must dedupe.'
		);
	}

	public function test_sanitize_drops_mixed_invalid_lines_keeps_valid() {
		$raw  = "https://evil.example/foo\n";
		$raw .= "//evil.example/bar\n";
		$raw .= "/wp-admin/admin.php?page=keep-me\n";
		$raw .= "   \n";

		$this->assertSame(
			'/wp-admin/admin.php?page=keep-me',
			$this->options->sanitize_url_allowlist( $raw )
		);
	}

	// ----------------------------------------------------------------------
	// parse_url_allowlist — turn raw textarea into {pagenow, params} entries
	// ----------------------------------------------------------------------

	public function test_parse_empty_returns_empty_array() {
		$access  = $this->make_access();
		$entries = $this->invoke( $access, 'parse_url_allowlist', array( '' ) );
		$this->assertSame( array(), $entries );
	}

	public function test_parse_extracts_pagenow_and_params() {
		$access  = $this->make_access();
		$entries = $this->invoke(
			$access,
			'parse_url_allowlist',
			array( '/wp-admin/admin.php?page=trustedlogin-secrets' )
		);

		$this->assertCount( 1, $entries );
		$this->assertSame( 'admin.php', $entries[0]['pagenow'] );
		$this->assertSame( array( 'page' => 'trustedlogin-secrets' ), $entries[0]['params'] );
	}

	public function test_parse_path_only_emits_empty_params_array() {
		$access  = $this->make_access();
		$entries = $this->invoke(
			$access,
			'parse_url_allowlist',
			array( '/wp-admin/admin-post.php' )
		);

		$this->assertCount( 1, $entries );
		$this->assertSame( 'admin-post.php', $entries[0]['pagenow'] );
		$this->assertSame( array(), $entries[0]['params'] );
	}

	public function test_parse_directory_path_normalizes_pagenow_to_index_php() {
		$access  = $this->make_access();
		$entries = $this->invoke(
			$access,
			'parse_url_allowlist',
			array( '/wp-admin/?foo=bar' )
		);

		$this->assertCount( 1, $entries );
		$this->assertSame(
			'index.php',
			$entries[0]['pagenow'],
			'/wp-admin/ requests have $pagenow === "index.php"; matcher must use the same key.'
		);
		$this->assertSame( array( 'foo' => 'bar' ), $entries[0]['params'] );
	}

	public function test_parse_multiple_lines_returns_multiple_entries() {
		$access  = $this->make_access();
		$entries = $this->invoke(
			$access,
			'parse_url_allowlist',
			array( "/wp-admin/admin.php?page=foo\n/wp-admin/admin-post.php" )
		);

		$this->assertCount( 2, $entries );
	}

	// ----------------------------------------------------------------------
	// is_allowed_page integration — does a parsed entry actually allow the
	// request through the matcher?
	// ----------------------------------------------------------------------

	public function test_url_entry_with_query_matches_exact_request() {
		// Register a fake admin page so F#2 page-registration check passes.
		$this->login_as( 'administrator' );
		add_menu_page( 'TL Secrets', 'TL Secrets', 'read', 'trustedlogin-secrets', '__return_null' );

		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin.php?page=trustedlogin-secrets',
		) );

		$this->set_pagenow( 'admin.php' );
		$_GET = array( 'page' => 'trustedlogin-secrets' );

		$this->assertTrue(
			$this->invoke( $access, 'is_allowed_page' ),
			'URL allowlist entry must match an identical request.'
		);
	}

	public function test_url_entry_with_query_matches_request_with_extra_params() {
		$this->login_as( 'administrator' );
		add_menu_page( 'TL Secrets', 'TL Secrets', 'read', 'trustedlogin-secrets', '__return_null' );

		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin.php?page=trustedlogin-secrets',
		) );

		$this->set_pagenow( 'admin.php' );
		$_GET = array(
			'page'   => 'trustedlogin-secrets',
			'action' => 'view',
			'id'     => '42',
		);

		$this->assertTrue(
			$this->invoke( $access, 'is_allowed_page' ),
			'Subset matcher: extra GET params on the request must not break the match.'
		);
	}

	public function test_url_entry_blocks_when_required_param_missing() {
		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin.php?page=trustedlogin-secrets',
		) );

		$this->set_pagenow( 'admin.php' );
		$_GET = array( 'page' => 'something-else' );

		$this->assertFalse(
			$this->invoke( $access, 'is_allowed_page' ),
			'Different page value must NOT match.'
		);
	}

	public function test_path_only_url_entry_allows_any_get_params() {
		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin-post.php',
		) );

		$this->set_pagenow( 'admin-post.php' );
		$_GET = array( 'action' => 'do-thing', 'nonce' => 'abc' );

		$this->assertTrue(
			$this->invoke( $access, 'is_allowed_page' ),
			'Path-only entry must allow any GET params on the request.'
		);
	}

	public function test_url_allowlist_does_not_affect_unrelated_pages() {
		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin-post.php',
		) );

		$this->set_pagenow( 'edit.php' );

		$this->assertFalse(
			$this->invoke( $access, 'is_allowed_page' ),
			'URL allowlist for admin-post.php must NOT allow edit.php.'
		);
	}

	public function test_rda_allowlist_filter_still_runs_after_url_merge() {
		$filter = static function ( $allowlist ) {
			$allowlist['custom-filter-page.php'] = array();
			return $allowlist;
		};
		add_filter( 'rda_allowlist', $filter );

		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin-post.php',
		) );

		$this->set_pagenow( 'custom-filter-page.php' );

		$this->assertTrue(
			$this->invoke( $access, 'is_allowed_page' ),
			'rda_allowlist filter must still apply on top of the URL-allowlist merge.'
		);

		remove_filter( 'rda_allowlist', $filter );
	}

	// ----------------------------------------------------------------------
	// UI — checkbox + textarea render correctly
	// ----------------------------------------------------------------------

	public function test_url_allowlist_cb_renders_widefat_textarea() {
		update_option( 'rda_url_allowlist', "/wp-admin/admin.php?page=foo" );

		// Re-initialize options object so get_settings cache picks up the update.
		$options = new RDA_Options();

		ob_start();
		$options->url_allowlist_cb();
		$markup = ob_get_clean();

		$this->assertStringContainsString( '<textarea', $markup );
		$this->assertStringContainsString( 'name="rda_url_allowlist"', $markup );
		$this->assertStringContainsString( 'class="widefat', $markup );
		$this->assertStringContainsString( '/wp-admin/admin.php?page=foo', $markup, 'Textarea must echo the stored value.' );
	}
}
