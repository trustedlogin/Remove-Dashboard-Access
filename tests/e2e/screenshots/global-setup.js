// Authenticates to the wp-env dev site once and saves the session cookies
// so each screenshot spec starts already logged in. Without this every
// spec would pay a wp-login.php roundtrip.

const fs = require( 'fs' );
const path = require( 'path' );
const { execSync } = require( 'child_process' );
const { chromium } = require( '@playwright/test' );

// Defaults match wp-env's stock admin user.
const ADMIN_USER = process.env.WP_ADMIN_USER || 'admin';
const ADMIN_PASS = process.env.WP_ADMIN_PASS || 'password';

// Subscriber fixture for screenshot-2 (restricted-user profile page).
const SUB_USER = 'rda-screenshot-subscriber';
const SUB_PASS = 'password';
const SUB_EMAIL = 'rda-screenshot-subscriber@example.com';

// Idempotently create the subscriber fixture user via wp-env's cli container.
// Subsequent runs hit "user exists" and continue silently.
function ensureSubscriberUser() {
	try {
		execSync(
			`npx wp-env run cli wp user create ${ SUB_USER } ${ SUB_EMAIL } ` +
				`--role=subscriber --user_pass=${ SUB_PASS } ` +
				`--display_name="Screenshot Subscriber"`,
			{ stdio: 'pipe' }
		);
	} catch ( error ) {
		// `wp user create` fails when the user already exists — that's the
		// expected steady-state. Re-throw only for unrelated errors.
		const stderr = ( error.stderr || '' ).toString();
		if ( ! stderr.includes( 'already' ) && ! stderr.includes( 'exists' ) ) {
			throw error;
		}
	}
}

// Log a user in and persist the session cookies to disk.
async function saveStorage( browser, baseURL, username, password, file ) {
	const context = await browser.newContext();
	const page = await context.newPage();

	await page.goto( `${ baseURL }/wp-login.php` );
	await page.fill( '#user_login', username );
	await page.fill( '#user_pass', password );
	await Promise.all( [
		page.waitForURL( () => true ), // accept whichever landing URL the user gets
		page.click( '#wp-submit' ),
	] );

	await context.storageState( { path: file } );
	await context.close();
}

module.exports = async ( config ) => {
	const baseURL = config.projects[ 0 ].use.baseURL;
	const authDir = path.join( __dirname, '..', '.auth' );

	fs.mkdirSync( authDir, { recursive: true } );

	ensureSubscriberUser();

	const browser = await chromium.launch();
	try {
		await saveStorage(
			browser,
			baseURL,
			ADMIN_USER,
			ADMIN_PASS,
			path.join( authDir, 'admin.json' )
		);
		await saveStorage(
			browser,
			baseURL,
			SUB_USER,
			SUB_PASS,
			path.join( authDir, 'subscriber.json' )
		);
	} finally {
		await browser.close();
	}
};
