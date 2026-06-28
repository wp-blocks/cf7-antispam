const { request } = require('@playwright/test');
const { RequestUtils } = require('@wordpress/e2e-test-utils-playwright');

/**
 * Global setup for E2E tests
 * https://aki-hamano.blog/en/2023/11/05/block-e2e/
 */

async function globalSetup(config) {
	const storageState = config.projects[0].use?.storageState || config.use?.storageState;
	const baseURL = process.env.WP_BASE_URL || config.projects[0].use?.baseURL || config.use?.baseURL || 'http://localhost:8888';
	const storageStatePath =
		typeof storageState === 'string' ? storageState : undefined;
	const requestContext = await request.newContext({
		baseURL,
	});
	const requestUtils = new RequestUtils(requestContext, {
		storageStatePath,
	});
	// Authenticate and save the storageState to disk.
	await requestUtils.setupRest();
	// Reset the test environment before running the tests.
	await Promise.all([
		requestUtils.activateTheme('twentytwentyone'),
		requestUtils.deleteAllPosts(),
		requestUtils.deleteAllBlocks(),
		requestUtils.resetPreferences(),
	]);
	await requestContext.dispose();
}
export default globalSetup;
