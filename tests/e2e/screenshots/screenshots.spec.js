// Regenerates the three WP.org screenshots in .wordpress-org/assets/.
//
// The subjects line up with the descriptions in readme.txt:
//
//   1. The Dashboard Access Controls settings in Settings → Dashboard Access.
//   2. Allow users to access their profile settings (only).
//   3. Optional login message.
//
// Run with `npm run screenshots`. Each test overwrites the corresponding
// screenshot-N.png in .wordpress-org/assets/. Commit the changes when
// you're happy with the result — the next push to main will ship them.

const { test, expect } = require( '@playwright/test' );
const path = require( 'path' );

const SETTINGS_URL = '/wp-admin/options-general.php?page=dashboard-access';
const ASSETS_DIR = path.join( __dirname, '..', '..', '..', '.wordpress-org', 'assets' );
const LOGIN_MESSAGE_SAMPLE =
	'Welcome — please sign in to continue.';

test.describe.configure( { mode: 'serial' } );

test.describe( 'WP.org screenshots', () => {
	test( 'screenshot-1: Dashboard Access Controls settings panel', async ( { page } ) => {
		// Make sure the form is in its clean default state before capturing.
		await page.goto( SETTINGS_URL );
		await page.locator( 'input[name="rda_access_switch"][value="manage_options"]' ).check();
		await page.locator( 'input[name="rda_login_message"]' ).fill( '' );
		await page.locator( '#submit' ).click();
		await page.waitForLoadState( 'networkidle' );

		await page.goto( SETTINGS_URL );

		await expect(
			page.getByRole( 'heading', { name: 'Dashboard Access Settings' } )
		).toBeVisible();

		// `.wrap` is the entire settings shell (heading + form + submit).
		await page.locator( '.wrap' ).screenshot( {
			path: path.join( ASSETS_DIR, 'screenshot-1.png' ),
		} );
	} );

	test( 'screenshot-2: User Profile Access option', async ( { page } ) => {
		await page.goto( SETTINGS_URL );

		// Locate the table row for the User Profile Access setting.
		const row = page.locator( 'tr', {
			has: page.locator( 'th', { hasText: 'User Profile Access:' } ),
		} );

		await expect( row ).toBeVisible();
		await row.screenshot( {
			path: path.join( ASSETS_DIR, 'screenshot-2.png' ),
		} );
	} );

	test( 'screenshot-3: Optional login message on wp-login.php', async ( { page, browser } ) => {
		// Set a sample login message via the real settings form.
		await page.goto( SETTINGS_URL );
		await page.locator( 'input[name="rda_login_message"]' )
			.fill( LOGIN_MESSAGE_SAMPLE );
		await page.locator( '#submit' ).click();
		await page.waitForLoadState( 'networkidle' );

		// Snapshot the login screen with a fresh unauthenticated context, so
		// no admin cookie leaks into the rendered chrome.
		const anonymous = await browser.newContext( { storageState: undefined } );
		const loginPage = await anonymous.newPage();

		try {
			await loginPage.goto( '/wp-login.php' );
			const loginBox = loginPage.locator( '#login' );

			await expect( loginBox ).toBeVisible();
			await expect(
				loginBox.getByText( LOGIN_MESSAGE_SAMPLE )
			).toBeVisible();

			await loginBox.screenshot( {
				path: path.join( ASSETS_DIR, 'screenshot-3.png' ),
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
