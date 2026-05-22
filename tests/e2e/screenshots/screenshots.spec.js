// Regenerates the three WP.org screenshots in .wordpress-org/assets/.
//
// The subjects line up with the descriptions in readme.txt:
//
//   1. The Dashboard Access Controls settings in Settings → Dashboard Access.
//      (The form-table section — not the WP page header above it.)
//   2. Allow users to access their profile settings (only).
//      (The /wp-admin/profile.php page rendered as a restricted subscriber
//       would see it — simplified sidebar + just-their-profile chrome.)
//   3. Optional login message on wp-login.php.
//
// Composition rules are pinned by `gk:screenshot` —
//   skills/screenshot/references/padding.md   → multi-component cluster: 48 px
//   skills/screenshot/references/resolution.md → cap longest side ≤ 2000 px
// Viewport (960 × 720) + 2× DPR gives a 1920-px-wide ceiling — comfortably
// inside the tooling cap.
//
// Run with `npm run screenshots`. Each test overwrites the corresponding
// screenshot-N.png in .wordpress-org/assets/. Commit the changes when
// you're happy with the result.

const { test, expect } = require( '@playwright/test' );
const path = require( 'path' );

const SETTINGS_URL = '/wp-admin/options-general.php?page=dashboard-access';
const ASSETS_DIR = path.join( __dirname, '..', '..', '..', '.wordpress-org', 'assets' );
// Sample text used in screenshot-3 — chosen so the rendered message obviously
// reads as a custom restriction notice an admin would set, not a generic
// greeting. The on-brand framing makes the screenshot self-explanatory in the
// wordpress.org listing without a caption.
const LOGIN_MESSAGE_SAMPLE =
	'Dashboard access is restricted. Please sign in with an authorized account.';
// Example redirect destination shown in screenshot-1's Redirect URL field.
// Picked an example.com URL on purpose — readers shouldn't see a localhost
// address and wonder whether the screenshot was meant for them.
const REDIRECT_URL_SAMPLE = 'https://example.com/my-dashboard/';
// Example URLs pre-populated into the Allowed URLs textarea so the field
// reads as configured in screenshot-1. Generic on purpose (no plugin slugs
// from a specific vendor) so readers can transpose to their own site.
const ALLOWED_URLS_SAMPLE =
	'/wp-admin/admin.php?page=customer-portal\n/wp-admin/admin.php?page=customer-*';

// Composition constants — one padding number per flow per
// gk:screenshot/references/padding.md "consistency across siblings".
const PADDING = 48;
const VIEWPORT = { width: 960, height: 720 };
const DEVICE_SCALE_FACTOR = 2;

const SUBSCRIBER_STATE = path.join(
	__dirname,
	'..',
	'.auth',
	'subscriber.json'
);

test.describe.configure( { mode: 'serial' } );

test.describe( 'WP.org screenshots', () => {
	test( 'screenshot-1: Dashboard Access Controls section', async ( { page } ) => {
		// Reset to clean defaults, with two deliberate exceptions:
		//   - Pre-fill the Login Message input with the same sample text
		//     screenshot-3 displays on wp-login.php. The two screenshots
		//     then tell one consecutive story — "this is where you set
		//     the message" (s1) and "this is how users see it" (s3) —
		//     instead of leaving the field empty in s1 and making readers
		//     infer the connection.
		//   - Set the Redirect URL to an example.com path rather than
		//     leaving the wp-env localhost default. Readers shouldn't see
		//     a localhost address in marketing screenshots.
		await page.goto( SETTINGS_URL );
		await page
			.locator( 'input[name="rda_access_switch"][value="manage_options"]' )
			.check();
		await page
			.locator( 'input[name="rda_redirect_url"]' )
			.fill( REDIRECT_URL_SAMPLE );
		await page
			.locator( 'input[name="rda_login_message"]' )
			.fill( LOGIN_MESSAGE_SAMPLE );
		await page
			.locator( 'textarea[name="rda_url_allowlist"]' )
			.fill( ALLOWED_URLS_SAMPLE );
		await page.locator( '#submit' ).click();
		await page.waitForLoadState( 'networkidle' );

		await page.goto( SETTINGS_URL );

		// Hide all WP-admin chrome — sidebar, admin bar, footer — so the
		// captured `.wrap` sits on a clean gray background with our 48 px
		// padding evenly on all four sides. Two reasons:
		//   1. Without this, the sidebar bleeds into the left edge of the
		//      clip (its bbox is past the viewport's x=0).
		//   2. With the chrome hidden, an element-screenshot of `.wrap`
		//      auto-scrolls to capture the full settings form even when
		//      it overflows the 720-px viewport — which it does once
		//      AJAX Requests + Allowed URLs are added.
		await page.addStyleTag( {
			content: `
				#adminmenumain, #adminmenuwrap, #adminmenuback,
				#wpadminbar, #wpfooter,
				#screen-meta, #screen-meta-links { display: none !important; }
				html.wp-toolbar { padding-top: 0 !important; }
				#wpcontent, #wpbody, #wpbody-content {
					margin-left: 0 !important;
					padding: 0 !important;
				}
				.wrap {
					padding: ${ PADDING }px !important;
					margin: 0 !important;
					min-height: 0 !important;
				}
			`,
		} );

		await expect(
			page.locator( 'h2', { hasText: 'Dashboard Access Controls' } )
		).toBeVisible();

		const wrap = page.locator( '.wrap' ).first();
		await wrap.screenshot( {
			path: path.join( ASSETS_DIR, 'screenshot-1.png' ),
		} );
	} );

	test( 'screenshot-2: Restricted user profile page (subscriber view)', async ( {
		browser,
	} ) => {
		// Open a fresh subscriber-authed context. global-setup created the
		// rda-screenshot-subscriber user + saved its session.
		const subContext = await browser.newContext( {
			viewport: VIEWPORT,
			deviceScaleFactor: DEVICE_SCALE_FACTOR,
			storageState: SUBSCRIBER_STATE,
			locale: 'en-US',
			timezoneId: 'UTC',
		} );
		const subPage = await subContext.newPage();

		try {
			// Subscribers get redirected away from /wp-admin/ by the lock —
			// but profile.php is explicitly allowed when enable_profile=true,
			// which is the default. Go straight there.
			await subPage.goto( '/wp-admin/profile.php' );

			await expect(
				subPage.getByRole( 'heading', { name: 'Profile' } )
			).toBeVisible();

			// Overview shot — per gk:screenshot/references/padding.md "don't
			// pad overview shots". Top of viewport down to a fixed height so
			// the aspect stays close to the 2014 original's ~2:1.
			await subPage.screenshot( {
				path: path.join( ASSETS_DIR, 'screenshot-2.png' ),
				clip: { x: 0, y: 0, width: VIEWPORT.width, height: 540 },
			} );
		} finally {
			await subContext.close();
		}
	} );

	test( 'screenshot-3: Optional login message on wp-login.php', async ( {
		page,
		browser,
	} ) => {
		// Set a sample login message via the real settings form.
		await page.goto( SETTINGS_URL );
		await page
			.locator( 'input[name="rda_login_message"]' )
			.fill( LOGIN_MESSAGE_SAMPLE );
		await page.locator( '#submit' ).click();
		await page.waitForLoadState( 'networkidle' );

		// Fresh unauthenticated context — no admin cookie leaks into the
		// rendered login chrome.
		const anonymous = await browser.newContext( {
			viewport: VIEWPORT,
			deviceScaleFactor: DEVICE_SCALE_FACTOR,
			storageState: undefined,
			locale: 'en-US',
			timezoneId: 'UTC',
		} );
		const loginPage = await anonymous.newPage();

		try {
			await loginPage.goto( '/wp-login.php' );

			const loginBox = loginPage.locator( '#login' );
			const lostPassword = loginPage
				.locator( '#nav a, p#nav a' )
				.filter( { hasText: /Lost your password/i } )
				.first();

			await expect( loginBox ).toBeVisible();
			await expect(
				loginBox.getByText( LOGIN_MESSAGE_SAMPLE )
			).toBeVisible();
			await expect( lostPassword ).toBeVisible();

			const loginBoxBox = await loginBox.boundingBox();
			const lostPasswordBox = await lostPassword.boundingBox();

			if ( ! loginBoxBox || ! lostPasswordBox ) {
				throw new Error(
					'Could not resolve bounding boxes for screenshot-3 framing.'
				);
			}

			// Clip from the #login top edge with 48 px padding, down through
			// the "Lost your password?" link with 48 px below it. Cuts off
			// any extra below-form links (e.g., "← Go to <site>") so the
			// aspect stays close to the original's ~1:1.
			const top = Math.max( 0, loginBoxBox.y - PADDING );
			const bottom = lostPasswordBox.y + lostPasswordBox.height + PADDING;

			await loginPage.screenshot( {
				path: path.join( ASSETS_DIR, 'screenshot-3.png' ),
				clip: {
					x: Math.max( 0, loginBoxBox.x - PADDING ),
					y: top,
					width: loginBoxBox.width + PADDING * 2,
					height: bottom - top,
				},
			} );
		} finally {
			await anonymous.close();
		}

		// Reset the login message so the option doesn't leak across runs.
		await page.goto( SETTINGS_URL );
		await page.locator( 'input[name="rda_login_message"]' ).fill( '' );
		await page.locator( '#submit' ).click();
		await page.waitForLoadState( 'networkidle' );
	} );
} );
