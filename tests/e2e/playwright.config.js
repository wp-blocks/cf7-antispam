/**
 * External dependencies
 */
import { defineConfig } from '@playwright/test';

/**
 * WordPress dependencies
 */
import baseConfig from '@wordpress/scripts/config/playwright.config.js';

const config = defineConfig({
	...baseConfig,
	globalSetup: require.resolve('./global-setup.js'),
	webServer: {
		...baseConfig.webServer,
		command: 'npm run wp-env:test',
		port: 8888,
	},
	use: {
		...baseConfig.use,
		baseURL: process.env.WP_BASE_URL || 'http://localhost:8888',
	},
	fullyParallel: false, // Disable parallel execution for WordPress tests
	forbidOnly: !!process.env.CI,
	workers: process.env.CI ? 1 : undefined, // Use single worker in CI
	retries: 0,
	testDir: '.',
	timeout: 60000,
});

export default config;
