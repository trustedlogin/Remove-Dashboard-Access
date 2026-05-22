// Playwright config for the WP.org-screenshots suite.
//
// We don't use @wordpress/scripts' wrapped Playwright here because that one
// is Gutenberg-tuned (auto-waits for editor, etc.). For deterministic
// screenshots of a vanilla settings page we want the bare runner.

const { defineConfig } = require( '@playwright/test' );

const baseURL =
	process.env.WP_BASE_URL ||
	`http://localhost:${ process.env.WP_ENV_PORT || 9888 }`;

module.exports = defineConfig( {
	testDir: './tests/e2e/screenshots',
	// One worker — screenshots mutate plugin options and must run in order.
	workers: 1,
	fullyParallel: false,
	reporter: [ [ 'list' ] ],
	timeout: 60_000,
	expect: { timeout: 5_000 },
	use: {
		baseURL,
		// `gk:screenshot/references/resolution.md` says 3× DPR for docs,
		// but the WP-admin chrome rendered at our viewport × 3 blows past the
		// 2000-px-on-longest-side ceiling the same skill enforces. The skill's
		// own escape hatch — "drop to deviceScaleFactor: 2 (then ≤ 1000 px CSS)"
		// — applies here. 2× still renders crisply on retina.
		deviceScaleFactor: 2,
		// Narrower viewport than the dev default so 2× output stays ≤ 1920 px
		// wide and the cropped form-table doesn't trail off into empty WP-admin
		// gutter on either side.
		viewport: { width: 960, height: 720 },
		// Don't auto-screenshot on failure; we capture deliberately per test.
		screenshot: 'off',
		video: 'off',
		trace: 'off',
		// Locale + timezone pinned so the rendered chrome doesn't drift between
		// contributors' machines.
		locale: 'en-US',
		timezoneId: 'UTC',
	},
	globalSetup: require.resolve(
		'./tests/e2e/screenshots/global-setup.js'
	),
	projects: [
		{
			name: 'chromium',
			use: {
				storageState: 'tests/e2e/.auth/admin.json',
			},
		},
	],
} );
