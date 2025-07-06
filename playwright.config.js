import { defineConfig } from '@playwright/test';

const baseConfig = require('@wordpress/scripts/config/playwright.config.js');

const config = defineConfig({
	...baseConfig,
	fullyParallel: false, // Disable parallel execution for WordPress tests
	forbidOnly: !!process.env.CI,
	workers: process.env.CI ? 1 : undefined, // Use single worker in CI
	retries: 0,
	testDir: './tests/e2e',
	timeout: 60000,
});

export default config;
