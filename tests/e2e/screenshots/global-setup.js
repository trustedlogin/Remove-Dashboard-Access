// Authenticates to the wp-env dev site once and saves the session cookies
// so each screenshot spec starts already logged in. Without this every
// spec would pay a wp-login.php roundtrip.

const fs = require( 'fs' );
const path = require( 'path' );
const { chromium } = require( '@playwright/test' );

// Defaults match wp-env's stock admin user.
const ADMIN_USER =
	process.env.WP_ADMIN_USER || 'admin';
const ADMIN_PASS =
	process.env.WP_ADMIN_PASS || 'password';

module.exports = async ( config ) => {
	const baseURL = config.projects[ 0 ].use.baseURL;
	const authDir = path.join( __dirname, '..', '.auth' );
	const stateFile = path.join( authDir, 'admin.json' );

	fs.mkdirSync( authDir, { recursive: true } );

	const browser = await chromium.launch();
	const context = await browser.newContext();
	const page = await context.newPage();

	await page.goto( `${ baseURL }/wp-login.php` );
	await page.fill( '#user_login', ADMIN_USER );
	await page.fill( '#user_pass', ADMIN_PASS );
	await Promise.all( [
		page.waitForURL( ( url ) => url.pathname.startsWith( '/wp-admin' ) ),
		page.click( '#wp-submit' ),
	] );

	await context.storageState( { path: stateFile } );
	await browser.close();
};
