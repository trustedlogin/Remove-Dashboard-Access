<?php
/**
 * Adversarial tests for the URL allow-list (1.3.0).
 *
 * Each test below is a deliberate attempt to break the allow-list — either
 * to smuggle a foreign destination through `sanitize_url_allowlist`, to
 * trick the matcher into a false-positive match, or to use the allow-list
 * as a pivot to reach admin pages the plugin is supposed to gate.
 *
 * Failures here mean a real security regression. Add new attack ideas to
 * this file rather than the happy-path test-url-allowlist.php.
 */

class Test_URL_Allowlist_Attacks extends RDA_TestCase {

	/**
	 * @var RDA_Options
	 */
	protected $options;

	public function set_up() {
		parent::set_up();
		$this->options = new RDA_Options();
	}

	// ----------------------------------------------------------------------
	// Host confusion attacks against sanitize_url_allowlist
	// ----------------------------------------------------------------------

	public function test_authority_substitution_with_at_sign_rejected() {
		// userinfo `home.test` followed by `@evil.example` host — wp_parse_url
		// MUST identify the actual host as 'evil.example', and we reject it.
		$result = $this->options->sanitize_url_allowlist( 'https://home.test@evil.example/wp-admin/admin.php' );
		$this->assertSame(
			'',
			$result,
			'host-pivot attack via @ in URL must be rejected.'
		);
	}

	public function test_userinfo_on_same_host_is_stripped() {
		// Legitimate-looking entry with userinfo — host is still home.test;
		// we should accept the path but drop the userinfo from storage.
		$result = $this->options->sanitize_url_allowlist( 'https://admin:secret@' . wp_parse_url( home_url(), PHP_URL_HOST ) . '/wp-admin/admin.php?page=foo' );
		$this->assertSame(
			'/wp-admin/admin.php?page=foo',
			$result,
			'userinfo must not survive into the stored allow-list line.'
		);
	}

	public function test_mixed_case_scheme_and_host_match() {
		$host    = strtoupper( wp_parse_url( home_url(), PHP_URL_HOST ) );
		$raw     = 'HTTPS://' . $host . '/wp-admin/admin.php?page=foo';
		$result  = $this->options->sanitize_url_allowlist( $raw );
		$this->assertSame(
			'/wp-admin/admin.php?page=foo',
			$result,
			'host comparison must be case-insensitive.'
		);
	}

	public function test_trailing_dot_in_host_rejected() {
		$host   = wp_parse_url( home_url(), PHP_URL_HOST );
		$result = $this->options->sanitize_url_allowlist( 'https://' . $host . './wp-admin/admin.php' );
		$this->assertSame(
			'',
			$result,
			'trailing-dot host is structurally different from the canonical host and must be rejected.'
		);
	}

	public function test_subdomain_attack_rejected() {
		$host   = 'evil.' . wp_parse_url( home_url(), PHP_URL_HOST );
		$result = $this->options->sanitize_url_allowlist( 'https://' . $host . '/wp-admin/admin.php' );
		$this->assertSame(
			'',
			$result,
			'a subdomain of the canonical host must NOT be accepted as same-host.'
		);
	}

	public function test_punycode_homoglyph_rejected() {
		// An IDN host that looks like the real host but is a different domain.
		// The raw bytes differ, so byte-compare must reject.
		$result = $this->options->sanitize_url_allowlist( 'https://xn--exmple-z3a.test/wp-admin/admin.php' );
		$this->assertSame(
			'',
			$result,
			'IDN/punycode homoglyph hosts must NOT match the canonical host.'
		);
	}

	public function test_javascript_uri_rejected_outright() {
		// A `javascript:` "URL" must be rejected outright — never stored, never
		// rendered back into the textarea, never given any path to execution.
		$result = $this->options->sanitize_url_allowlist( 'javascript:alert(1)' );
		$this->assertSame( '', $result, 'javascript: URI must be rejected.' );
	}

	public function test_data_uri_rejected_outright() {
		$result = $this->options->sanitize_url_allowlist( 'data:text/html,<script>alert(1)</script>' );
		$this->assertSame( '', $result, 'data: URI must be rejected.' );
	}

	public function test_file_uri_rejected_outright() {
		$result = $this->options->sanitize_url_allowlist( 'file:///etc/passwd' );
		$this->assertSame( '', $result, 'file: URI must be rejected.' );
	}

	public function test_arbitrary_custom_scheme_rejected() {
		$result = $this->options->sanitize_url_allowlist( 'evil-scheme:payload' );
		$this->assertSame( '', $result, 'any non-http(s) scheme must be rejected.' );
	}

	// ----------------------------------------------------------------------
	// "Wildcard" / overly-broad entries
	// ----------------------------------------------------------------------

	public function test_bare_root_slash_rejected() {
		// `/` would map to pagenow=index.php with no params and silently
		// whitelist the entire dashboard. Reject outright.
		$result = $this->options->sanitize_url_allowlist( '/' );
		$this->assertSame(
			'',
			$result,
			'a single-slash entry is an "allow everything" footgun and must be rejected.'
		);
	}

	public function test_bare_wp_admin_root_rejected() {
		// `/wp-admin/` would map to pagenow=index.php (the dashboard) with no
		// param constraints — would expose the entire backend if accepted.
		$result = $this->options->sanitize_url_allowlist( '/wp-admin/' );
		$this->assertSame(
			'',
			$result,
			'`/wp-admin/` whitelists the whole dashboard root; must be rejected.'
		);
	}

	public function test_directory_path_without_query_rejected() {
		// Any path ending in `/` without query string is too broad — same
		// reason as `/wp-admin/`.
		$result = $this->options->sanitize_url_allowlist( '/wp-admin/users/' );
		$this->assertSame(
			'',
			$result,
			'directory-style entries without GET constraints must be rejected.'
		);
	}

	public function test_directory_path_with_query_accepted() {
		// Sanity inverse — once a query string is present, the entry is no
		// longer "allow everything" and we accept it.
		$result = $this->options->sanitize_url_allowlist( '/wp-admin/?page=specific' );
		$this->assertNotSame( '', $result, 'directory + query entries are narrow enough to be accepted.' );
		$this->assertStringContainsString( 'page=specific', $result );
	}

	// ----------------------------------------------------------------------
	// Matcher false-positives
	// ----------------------------------------------------------------------

	public function test_subscriber_whitelisting_users_php_does_not_grant_access() {
		// Even with /wp-admin/users.php in the allow-list, the subscriber
		// still can't read the user list — WP core's user_can_access_admin_page
		// gate runs independently. The allow-list only suppresses OUR redirect.
		// We assert that the subscriber is "not redirected away by RDA," not
		// that they have access.

		$this->login_as( 'subscriber' );

		$access = $this->make_access( array(
			'access_cap'    => 'manage_options',
			'url_allowlist' => '/wp-admin/users.php',
		) );

		$this->set_pagenow( 'users.php' );
		$_GET = array();

		// is_allowed_page returns true (RDA stays its hand), but separately
		// the subscriber lacks list_users — WP will 403 in menu.php. So this
		// test is documenting that the allow-list does NOT itself grant caps.
		$this->assertTrue(
			$this->invoke( $access, 'is_allowed_page' ),
			'URL allowlist entry causes is_allowed_page to return true.'
		);

		$this->assertFalse(
			current_user_can( 'list_users' ),
			'Subscriber still lacks list_users — allow-list does not escalate.'
		);
	}

	public function test_request_with_only_subset_of_required_params_blocked() {
		// Allow-list requires page=foo AND id=42. Request has only page=foo.
		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin.php?page=foo&id=42',
		) );

		$this->set_pagenow( 'admin.php' );
		$_GET = array( 'page' => 'foo' );

		$this->assertFalse(
			$this->invoke( $access, 'is_allowed_page' ),
			'a request missing one of the required params must NOT match.'
		);
	}

	public function test_array_param_does_not_match_scalar_required() {
		// Allow-list requires page=foo (scalar). Request sends page[]=foo.
		// Strict !== must reject the array-vs-string mismatch.
		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin.php?page=foo',
		) );

		$this->set_pagenow( 'admin.php' );
		$_GET = array( 'page' => array( 'foo' ) );

		$this->assertFalse(
			$this->invoke( $access, 'is_allowed_page' ),
			'page[]=foo (array) must NOT match a scalar page=foo allow-list entry.'
		);
	}

	public function test_duplicate_query_keeps_last_value() {
		// `?page=foo&page=bar` — parse_str behavior: 'bar' wins. So a
		// whitelist of `?page=foo` should NOT match a request whose final
		// $_GET['page'] is 'bar'.
		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin.php?page=foo',
		) );

		$this->set_pagenow( 'admin.php' );
		$_GET = array( 'page' => 'bar' );

		$this->assertFalse(
			$this->invoke( $access, 'is_allowed_page' ),
			'when PHP collapses duplicate keys to last-write-wins, the matcher must compare against the final value.'
		);
	}

	public function test_cross_pagenow_entries_do_not_leak() {
		// Allow-list for admin-post.php must not accept users.php requests.
		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin-post.php',
		) );

		$this->set_pagenow( 'users.php' );

		$this->assertFalse(
			$this->invoke( $access, 'is_allowed_page' ),
			'allow-listing one pagenow must not silently allow a different pagenow.'
		);
	}

	// ----------------------------------------------------------------------
	// pagenow normalization (mirror WP core's $pagenow logic)
	// ----------------------------------------------------------------------

	public function test_pagenow_strips_trailing_slash_on_file_path() {
		// `/wp-admin/users.php/` — WP's regex sets $pagenow='users.php', so
		// our parse must do the same. Otherwise an admin's entry never
		// matches the real request.
		$access  = $this->make_access();
		$entries = $this->invoke(
			$access,
			'parse_url_allowlist',
			array( '/wp-admin/users.php/' )
		);

		$this->assertCount( 1, $entries );
		$this->assertSame( 'users.php', $entries[0]['pagenow'] );
	}

	public function test_pagenow_adds_php_suffix_when_missing() {
		// `/wp-admin/users` (no .php) — WP appends .php internally. Our parse
		// must too.
		$access  = $this->make_access();
		$entries = $this->invoke(
			$access,
			'parse_url_allowlist',
			array( '/wp-admin/users' )
		);

		$this->assertCount( 1, $entries );
		$this->assertSame( 'users.php', $entries[0]['pagenow'] );
	}

	public function test_pagenow_lowercased() {
		// Admin types path with uppercase filename. WP lowercases when
		// computing $pagenow. We must too, or the entry never matches.
		$access  = $this->make_access();
		$entries = $this->invoke(
			$access,
			'parse_url_allowlist',
			array( '/wp-admin/Users.PHP' )
		);

		$this->assertCount( 1, $entries );
		$this->assertSame( 'users.php', $entries[0]['pagenow'] );
	}

	// ----------------------------------------------------------------------
	// Storage / round-trip integrity
	// ----------------------------------------------------------------------

	public function test_xss_payload_in_textarea_renders_escaped() {
		// Admin types something with HTML tags; sanitizer should keep it as
		// a relative path string. On render the textarea must escape it so
		// it doesn't break out of the <textarea>.
		$payload = '</textarea><script>alert(1)</script>';
		update_option( 'rda_url_allowlist', $payload );

		$options = new RDA_Options();

		ob_start();
		$options->url_allowlist_cb();
		$markup = ob_get_clean();

		$this->assertStringNotContainsString(
			'</textarea><script>',
			$markup,
			'a literal </textarea> escape must NOT appear in the rendered markup.'
		);
		$this->assertStringNotContainsString( '<script>alert(1)</script>', $markup );
	}

	public function test_null_byte_stripped() {
		// A null byte in the stored value could confuse strpos-based code
		// that uses the value later. The sanitizer should drop or strip nulls.
		$result = $this->options->sanitize_url_allowlist( "/wp-admin/admin.php?page=foo\0evil" );
		$this->assertStringNotContainsString(
			"\0",
			$result,
			'null bytes must not survive into stored URL allow-list entries.'
		);
	}

	public function test_pathological_long_input_does_not_crash() {
		// 64 KB of repeated lines — ensure the sanitizer doesn't blow up
		// or hang. We don't assert behavior beyond "completes successfully."
		$line = '/wp-admin/admin.php?page=long' . str_repeat( 'x', 100 );
		$raw  = str_repeat( $line . "\n", 1000 );

		$result = $this->options->sanitize_url_allowlist( $raw );

		$this->assertIsString( $result );
		// After dedupe we should have one entry (all 1000 lines are
		// identical post-trim).
		$this->assertCount( 1, explode( "\n", $result ) );
	}

	public function test_path_traversal_stored_but_never_matches() {
		// `/wp-admin/../etc/passwd` is stored verbatim — but since RDA only
		// does string compare against the parsed pagenow, it can never match
		// a real request URI.
		$result = $this->options->sanitize_url_allowlist( '/wp-admin/../etc/passwd' );

		// Accepted as-is (we don't path-normalize) but matching is harmless:
		$access = $this->make_access( array( 'url_allowlist' => $result ) );
		$this->set_pagenow( 'edit.php' );
		$_GET = array();

		$this->assertFalse(
			$this->invoke( $access, 'is_allowed_page' ),
			'path-traversal entries cannot grant access to anything real.'
		);
	}

	// ----------------------------------------------------------------------
	// Wildcard misuse / over-broad globs
	// ----------------------------------------------------------------------

	public function test_wildcard_does_not_bypass_page_registration_check() {
		// Even with `?page=*` in the allow-list, an unregistered page slug
		// must still be blocked by F#2's page-registration check. Wildcards
		// widen which VALUES match, not which CHECKS get skipped.
		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin.php?page=*',
		) );

		$this->set_pagenow( 'admin.php' );
		$_GET = array( 'page' => 'unregistered-ghost-page' );

		// Reset admin-page globals so nothing is registered.
		$GLOBALS['admin_page_hooks']  = array();
		$GLOBALS['_registered_pages'] = array();

		$this->assertFalse(
			$this->invoke( $access, 'is_allowed_page' ),
			'`?page=*` must NOT smuggle access to an unregistered page slug.'
		);
	}

	public function test_wildcard_regex_metachars_treated_literally() {
		// A pattern like `tl[.*]secrets` must NOT be interpreted as a regex
		// character class. Only `*` is a wildcard; everything else is literal.
		$this->login_as( 'administrator' );
		add_menu_page( 'Literal', 'Literal', 'read', 'tl[.*]secrets', '__return_null' );

		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin.php?page=tl[.*]secrets',
		) );

		$this->set_pagenow( 'admin.php' );

		// Literal slug must match.
		$_GET = array( 'page' => 'tl[.*]secrets' );
		$this->assertTrue(
			$this->invoke( $access, 'is_allowed_page' ),
			'A pattern of "tl[.*]secrets" must match exactly that string.'
		);

		// A "different but-glob-match-if-treated-as-regex" value must NOT match.
		$_GET = array( 'page' => 'tlAsecrets' ); // would match if `.` was a regex meta
		$this->assertFalse(
			$this->invoke( $access, 'is_allowed_page' ),
			'Non-`*` regex metachars must be matched literally, not as regex.'
		);
	}

	public function test_wildcard_matcher_is_linear_under_pathological_pattern() {
		// Patterns like `*****foo*****` against a long subject without the
		// "foo" substring used to be a PCRE concern. The linear matcher
		// must complete quickly regardless. We don't measure wall-clock
		// here (CI variance), but we do confirm the right answer.
		$this->login_as( 'administrator' );
		add_menu_page( 'X', 'X', 'read', 'x', '__return_null' );

		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin.php?page=*****x*****',
		) );

		$this->set_pagenow( 'admin.php' );

		// Long subject containing the required substring → match.
		$_GET = array( 'page' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaxbbbbbbbbbbbbbbbbb' );
		// `x` isn't a registered slug under this subject; the test is
		// about matcher behavior, not page registration. Use a value
		// that IS a registered slug:
		$_GET = array( 'page' => 'x' );
		$this->assertTrue(
			$this->invoke( $access, 'is_allowed_page' ),
			'pathological-multi-star pattern must still match its anchor segment.'
		);

		// Long subject with NO required substring → no match (and fast).
		$_GET = array( 'page' => str_repeat( 'y', 5000 ) );
		$start = microtime( true );
		$result = $this->invoke( $access, 'is_allowed_page' );
		$elapsed = microtime( true ) - $start;

		$this->assertFalse( $result );
		$this->assertLessThan(
			1.0,
			$elapsed,
			'pathological pattern against 5KB subject must finish in well under a second.'
		);
	}

	public function test_double_at_sign_in_url_uses_actual_host() {
		// `https://home.test@home.test@evil.example/foo` — PHP's parse_url
		// reports HOST as the segment after the LAST `@`, which is
		// `evil.example` here. Reject.
		$result = $this->options->sanitize_url_allowlist( 'https://home.test@home.test@evil.example/wp-admin/admin.php' );
		$this->assertSame(
			'',
			$result,
			'double-@ chain must resolve to the final host and reject if it differs from home.'
		);
	}

	public function test_url_encoded_scheme_rejected() {
		// `%6Aavascript:` — even if some downstream code URL-decodes, our
		// scheme check sees it as a relative path with a `:` before any `/`,
		// which trips the non-http(s) scheme guard.
		$result = $this->options->sanitize_url_allowlist( '%6Aavascript:alert(1)' );
		$this->assertSame(
			'',
			$result,
			'URL-encoded scheme must not slip past the relative-path scheme guard.'
		);
	}

	public function test_backslash_url_does_not_create_phantom_host() {
		// `https:\\home.test\foo` is malformed under RFC 3986. wp_parse_url
		// returns null host (or partial parse). We must not accept the path
		// part as if it came from a same-host absolute URL.
		$result = $this->options->sanitize_url_allowlist( 'https:\\\\home.test\\wp-admin\\admin.php' );
		$this->assertSame(
			'',
			$result,
			'backslash-style URLs must not be accepted as same-host.'
		);
	}

	public function test_wildcard_does_not_match_empty_string_when_anchored() {
		// `?page=foo*` should NOT match `?page=` (empty value). Subscribers
		// shouldn't be able to satisfy a wildcard with a degenerate empty
		// string and reach a page that requires a real slug.
		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin.php?page=tl-*',
		) );

		$this->set_pagenow( 'admin.php' );
		$_GET = array( 'page' => '' );

		$this->assertFalse(
			$this->invoke( $access, 'is_allowed_page' ),
			'wildcard `tl-*` must not match an empty value; prefix must be present.'
		);
	}

	// ----------------------------------------------------------------------
	// Filter precedence — can the rda_allowlist filter undo or amplify?
	// ----------------------------------------------------------------------

	public function test_filter_can_remove_a_user_added_entry() {
		// A site-specific mu-plugin should be able to override what the
		// admin pasted into the textarea (e.g. revoke a now-dangerous entry).
		$filter = static function ( $allowlist ) {
			unset( $allowlist['admin-post.php'] );
			return $allowlist;
		};
		add_filter( 'rda_allowlist', $filter );

		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin-post.php',
		) );

		$this->set_pagenow( 'admin-post.php' );

		$this->assertFalse(
			$this->invoke( $access, 'is_allowed_page' ),
			'rda_allowlist filter must be able to drop a user-added entry.'
		);

		remove_filter( 'rda_allowlist', $filter );
	}

	// ----------------------------------------------------------------------
	// Logged-in user attacks against an already-saved rule
	//
	// Threat model: admin pasted a specific rule (e.g.
	// `/wp-admin/admin.php?page=trustedlogin-secrets`). A subscriber
	// crafts a request that tries to bypass or stretch the rule using
	// encoding tricks, multibyte/unicode confusion, whitespace, or
	// container-type mismatches in $_GET. None of these should succeed.
	// ----------------------------------------------------------------------

	public function test_subscriber_attack_mixed_case_param_key_blocked() {
		// Rule requires `page=trustedlogin-secrets`. Attacker sends `Page=…`
		// (capital P). PHP $_GET is case-sensitive for keys, so the rule's
		// required key isn't present in the request → no match.
		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin.php?page=trustedlogin-secrets',
		) );

		$this->set_pagenow( 'admin.php' );
		$_GET = array( 'Page' => 'trustedlogin-secrets' );

		$this->assertFalse(
			$this->invoke( $access, 'is_allowed_page' ),
			'A case-different param key (Page vs page) must NOT satisfy the rule.'
		);
	}

	public function test_subscriber_attack_mixed_case_param_value_blocked() {
		// Strict !== — values are case-sensitive.
		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin.php?page=trustedlogin-secrets',
		) );

		$this->set_pagenow( 'admin.php' );
		$_GET = array( 'page' => 'TRUSTEDLOGIN-SECRETS' );

		$this->assertFalse(
			$this->invoke( $access, 'is_allowed_page' ),
			'Uppercase value must NOT satisfy a lowercase rule.'
		);
	}

	public function test_subscriber_attack_trailing_whitespace_in_value_blocked() {
		// Real-world request URL: `?page=trustedlogin-secrets%20` →
		// $_GET['page'] = 'trustedlogin-secrets '. The trailing space
		// makes it byte-different from the rule.
		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin.php?page=trustedlogin-secrets',
		) );

		$this->set_pagenow( 'admin.php' );
		$_GET = array( 'page' => 'trustedlogin-secrets ' );

		$this->assertFalse(
			$this->invoke( $access, 'is_allowed_page' ),
			'A trailing space added by the attacker must NOT match.'
		);
	}

	public function test_subscriber_attack_zero_width_space_in_value_blocked() {
		// Zero-width space (U+200B) makes the string look identical to a
		// human but is byte-different. Strict compare must reject.
		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin.php?page=trustedlogin-secrets',
		) );

		$this->set_pagenow( 'admin.php' );
		$_GET = array( 'page' => 'trustedlogin-secrets' . "\xE2\x80\x8B" );

		$this->assertFalse(
			$this->invoke( $access, 'is_allowed_page' ),
			'Zero-width characters appended to a value must NOT satisfy the rule.'
		);
	}

	public function test_subscriber_attack_homoglyph_in_value_blocked() {
		// Cyrillic 'е' (U+0435) looks identical to Latin 'e' but is a
		// different code point. Without explicit unicode normalization
		// (which we don't do — by design), homoglyphs must fail strict !==.
		$rule_value = 'trustedlogin-secrets';
		$attack_value = 'trust' . "\xD0\xB5" . 'dlogin-s' . "\xD0\xB5" . 'cr' . "\xD0\xB5" . 'ts'; // Cyrillic е

		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin.php?page=' . $rule_value,
		) );

		$this->set_pagenow( 'admin.php' );
		$_GET = array( 'page' => $attack_value );

		$this->assertFalse(
			$this->invoke( $access, 'is_allowed_page' ),
			'Cyrillic homoglyph value must NOT satisfy a Latin rule.'
		);
	}

	public function test_subscriber_attack_encoded_null_in_value_blocked() {
		// `?page=foo%00bar` → $_GET['page']="foo\0bar". Strict !== blocks.
		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin.php?page=foo',
		) );

		$this->set_pagenow( 'admin.php' );
		$_GET = array( 'page' => "foo\0bar" );

		$this->assertFalse(
			$this->invoke( $access, 'is_allowed_page' ),
			'Encoded null + extra payload in value must NOT match scalar `foo`.'
		);
	}

	public function test_subscriber_attack_encoded_newline_in_value_blocked() {
		// CRLF/LF injection attempt; rule says exactly `foo` so the
		// request value `foo\n…` must NOT match.
		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin.php?page=foo',
		) );

		$this->set_pagenow( 'admin.php' );
		$_GET = array( 'page' => "foo\n/wp-admin/users.php" );

		$this->assertFalse(
			$this->invoke( $access, 'is_allowed_page' ),
			'Newline + extra payload must NOT match an exact-value rule.'
		);
	}

	public function test_subscriber_attack_encoded_slash_in_value_blocked() {
		// `?page=foo%2Fbar` → $_GET['page']='foo/bar'. Different from 'foo'.
		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin.php?page=foo',
		) );

		$this->set_pagenow( 'admin.php' );
		$_GET = array( 'page' => 'foo/bar' );

		$this->assertFalse(
			$this->invoke( $access, 'is_allowed_page' ),
			'Encoded slash extending the value must NOT match.'
		);
	}

	public function test_subscriber_attack_array_notation_in_param_blocked() {
		// `?page[]=trustedlogin-secrets` → $_GET['page'] is an array.
		// is_string check in matcher rejects.
		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin.php?page=trustedlogin-secrets',
		) );

		$this->set_pagenow( 'admin.php' );
		$_GET = array( 'page' => array( 'trustedlogin-secrets' ) );

		$this->assertFalse(
			$this->invoke( $access, 'is_allowed_page' ),
			'Array-form `page[]=` request must NOT satisfy a scalar rule.'
		);
	}

	public function test_subscriber_attack_associative_array_in_param_blocked() {
		// `?page[key]=trustedlogin-secrets` → $_GET['page'] is an associative array.
		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin.php?page=trustedlogin-secrets',
		) );

		$this->set_pagenow( 'admin.php' );
		$_GET = array( 'page' => array( 'key' => 'trustedlogin-secrets' ) );

		$this->assertFalse(
			$this->invoke( $access, 'is_allowed_page' ),
			'Associative-array request must NOT satisfy a scalar rule.'
		);
	}

	public function test_url_encoded_value_equivalent_to_unencoded_does_match() {
		// SANITY CHECK (not an attack): if the attacker URL-encodes the
		// SAME bytes the rule expects, PHP decodes them before populating
		// $_GET — so the match should succeed. This is by design and
		// distinct from the encoding-confusion attacks above.
		$this->login_as( 'administrator' );
		add_menu_page( 'TL', 'TL', 'read', 'trustedlogin-secrets', '__return_null' );

		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin.php?page=trustedlogin-secrets',
		) );

		$this->set_pagenow( 'admin.php' );
		// PHP has already URL-decoded $_GET — simulate by populating with
		// the decoded value (which is what PHP would do for the encoded URL).
		$_GET = array( 'page' => 'trustedlogin-secrets' );

		$this->assertTrue(
			$this->invoke( $access, 'is_allowed_page' ),
			'Decoded equivalent must match (sanity check on the URL-encoding round-trip).'
		);
	}

	public function test_subscriber_attack_overlong_utf8_in_value_blocked() {
		// Overlong UTF-8 encoding of ASCII chars (e.g. /usr/bin/passwd
		// can be encoded multiple ways). Each is byte-different from the
		// canonical form. Rule says `foo`; attacker sends an overlong
		// 4-byte encoding that decodes to look like `foo` visually but
		// is byte-different.
		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin.php?page=foo',
		) );

		$this->set_pagenow( 'admin.php' );
		// Overlong (invalid) encoding of 'f' as 0xC1 0x86 + literal 'oo'.
		// $_GET would contain these bytes if attacker crafted the URL.
		$_GET = array( 'page' => "\xC1\x86oo" );

		$this->assertFalse(
			$this->invoke( $access, 'is_allowed_page' ),
			'Overlong UTF-8 of `f` plus `oo` must NOT match scalar rule `foo`.'
		);
	}

	public function test_subscriber_attack_extra_required_key_synthesized_blocked() {
		// Allow-list requires `page=foo&id=42`. Attacker tries to fake
		// the second param via array notation collapse.
		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin.php?page=foo&id=42',
		) );

		$this->set_pagenow( 'admin.php' );
		// Attacker only knows `page=foo` exists in the rule. Sends just that.
		$_GET = array( 'page' => 'foo' );

		$this->assertFalse(
			$this->invoke( $access, 'is_allowed_page' ),
			'Subset-match must still demand EVERY required param; missing id=42 blocks.'
		);
	}

	public function test_subscriber_attack_extra_unrelated_param_does_not_satisfy() {
		// Rule requires `page=foo`. Attacker sends a different page value
		// PLUS the right one — but the right key collapses to the LAST
		// value via standard PHP semantics. Confirm the matcher checks
		// the COLLAPSED final value, not any prior value.
		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin.php?page=foo',
		) );

		$this->set_pagenow( 'admin.php' );
		// Simulating what PHP would build after parsing
		// `?page=foo&page=evil-payload`:
		$_GET = array( 'page' => 'evil-payload' );

		$this->assertFalse(
			$this->invoke( $access, 'is_allowed_page' ),
			'Last-write-wins duplicate-key handling must drop earlier safe value, not preserve it.'
		);
	}

	public function test_subscriber_attack_path_info_appended_does_not_bypass_pagenow() {
		// `/wp-admin/admin.php/extra/path?page=foo` — WP's $pagenow regex
		// strips PATH_INFO down to 'admin.php'. So a path-info-decorated
		// request still resolves to pagenow=admin.php and follows the same
		// allow-list rules — no extra surface, no bypass. (Documenting
		// the expected behavior: extra path-info doesn't open new paths.)
		$this->login_as( 'administrator' );
		add_menu_page( 'Foo', 'Foo', 'read', 'foo', '__return_null' );

		$access = $this->make_access( array(
			'url_allowlist' => '/wp-admin/admin.php?page=foo',
		) );

		// In a real request, WP sets $pagenow='admin.php' regardless of
		// PATH_INFO. We simulate that final value here.
		$this->set_pagenow( 'admin.php' );
		$_GET = array( 'page' => 'foo' );

		$this->assertTrue(
			$this->invoke( $access, 'is_allowed_page' ),
			'PATH_INFO decoration does not change which rule applies — the page=foo rule matches normally.'
		);
	}

	// ----------------------------------------------------------------------
	// helpers
	// ----------------------------------------------------------------------

	/**
	 * Pull the first scheme-like prefix out of a stored value, if any.
	 *
	 * @param string $stored
	 * @return string The scheme up to ':' or empty.
	 */
	private function extract_scheme_only( $stored ) {
		if ( preg_match( '#^/?([a-z][a-z0-9+\-.]*):#i', $stored, $m ) ) {
			return $m[1] . ':';
		}
		return '';
	}
}
