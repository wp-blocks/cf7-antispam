import { defineConfig } from '@playwright/test';

import baseConfig from '@wordpress/scripts/config/playwright.config.js';

const config = defineConfig({
	...baseConfig,
	globalSetup: require.resolve('./global-setup.js'),
	fullyParallel: false, // Disable parallel execution for WordPress tests
	forbidOnly: !!process.env.CI,
	workers: process.env.CI ? 1 : undefined, // Use single worker in CI
	retries: 0,
	testDir: '.',
	timeout: 60000,
});

export default config;
