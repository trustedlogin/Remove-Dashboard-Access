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
// Picked a plain personal name so the "Howdy, …" in the admin bar reads
// like a real customer account — not "Screenshot Subscriber" which clearly
// announces "I am a test fixture" and weakens the marketing screenshot.
const SUB_USER = 'sarah-mitchell';
const SUB_PASS = 'password';
const SUB_EMAIL = 'sarah-mitchell@example.com';
const SUB_DISPLAY = 'Sarah Mitchell';

// Idempotently create the subscriber fixture user via wp-env's cli container.
// Subsequent runs hit "user exists" and continue silently.
function ensureSubscriberUser() {
	try {
		execSync(
			`npx wp-env run cli wp user create ${ SUB_USER } ${ SUB_EMAIL } ` +
				`--role=subscriber --user_pass=${ SUB_PASS } ` +
				`--display_name="${ SUB_DISPLAY }"`,
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

	// One-time housekeeping: an earlier revision of this file created
	// `rda-screenshot-subscriber`. If that user is still in the wp-env
	// database, remove it so the only subscriber on the system matches the
	// current fixture and the screenshots don't accidentally pick it up.
	try {
		execSync(
			`npx wp-env run cli wp user delete rda-screenshot-subscriber --yes`,
			{ stdio: 'pipe' }
		);
	} catch ( _ignored ) {
		// User already gone, or never existed. No action needed.
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

// Set the wp-env site's `blogname` to a generic "My Website" so the
// admin-bar shortcut in screenshot-2 and the "← Go to …" link on
// wp-login.php in screenshot-3 read as something a reader would imagine
// for their own site — not "Remove-Dashboard-Access" (the plugin's slug,
// which leaks "I am a test fixture" into the screenshots).
function ensureSiteName() {
	try {
		execSync(
			`npx wp-env run cli wp option update blogname "My Website"`,
			{ stdio: 'pipe' }
		);
	} catch ( error ) {
		// Don't fail the whole test run if the option update hiccups —
		// the screenshots just won't have the polished site name.
		// Surface the stderr to the console for debugging.
		process.stderr.write(
			`global-setup: could not set blogname — ${ ( error.stderr || error.message ).toString() }\n`
		);
	}
}

module.exports = async ( config ) => {
	const baseURL = config.projects[ 0 ].use.baseURL;
	const authDir = path.join( __dirname, '..', '.auth' );

	fs.mkdirSync( authDir, { recursive: true } );

	ensureSubscriberUser();
	ensureSiteName();

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
