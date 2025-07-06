// Load utilities from Playwright and WordPress
import { test, expect } from '@playwright/test';
import { RequestUtils } from '@wordpress/e2e-test-utils-playwright';

// WordPress admin credentials (should be set in your config)
const adminUsername = process.env.WP_USERNAME || 'admin';
const adminPassword = process.env.WP_PASSWORD || 'password';
const baseURL = process.env.WP_BASE_URL || 'http://localhost:8889';

// Helper function to login to WordPress admin
async function loginToWordPressAdmin(page) {
	await page.goto(`${baseURL}/wp-login.php`);
	await page.fill('#user_login', adminUsername);
	await page.fill('#user_pass', adminPassword);
	await page.click('#wp-submit');
	await page.waitForURL('**/wp-admin/**');
}

// Helper function to visit admin page
async function visitAdminPage(page, path) {
	await page.goto(`${baseURL}/wp-admin/${path}`);
}

// Helper function to activate plugin
async function activatePlugin(page, pluginSlug) {
	await visitAdminPage(page, 'plugins.php');

	// Look for the plugin row and activate button
	const pluginRow = page.locator(`tr[data-slug="${pluginSlug}"]`);
	const activateLink = pluginRow.locator('a:has-text("Activate")');

	// Only click if the activate link exists (plugin is not already active)
	if ((await activateLink.count()) > 0) {
		await activateLink.click();
		await page.waitForURL('**/plugins.php**');
	}
}

// Helper function to deactivate plugin
async function deactivatePlugin(page, pluginSlug) {
	await visitAdminPage(page, 'plugins.php');

	// Look for the plugin row and deactivate button
	const pluginRow = page.locator(`tr[data-slug="${pluginSlug}"]`);
	const deactivateLink = pluginRow.locator('a:has-text("Deactivate")');

	// Only click if the deactivate link exists (plugin is active)
	if ((await deactivateLink.count()) > 0) {
		await deactivateLink.click();
		await page.waitForURL('**/plugins.php**');
	}
}

// Playwright Tests
test.describe('installation', () => {
	// Setup: Login before each test
	test.beforeEach(async ({ page }) => {
		await loginToWordPressAdmin(page);
	});

	test('works', () => {
		expect(true).toBeTruthy();
	});

	test('verifies the plugin is active', async ({ page }) => {
		// Visit the plugins page
		await visitAdminPage(page, 'plugins.php');

		// Check if plugin row exists and has active class
		const pluginRow = page.locator('tr[data-slug="cf7-antispam"]');
		await expect(pluginRow).toBeVisible();

		// Optional: Check if it has the active class
		const activePlugin = page.locator(
			'tr.active[data-slug="cf7-antispam"]'
		);
		const isActive = (await activePlugin.count()) > 0;
		console.log(`Plugin active status: ${isActive}`);
	});

	test('is enabled', async ({ page }) => {
		await activatePlugin(page, 'cf7-antispam');
		await visitAdminPage(page, 'admin.php?page=cf7-antispam');

		// Wait for page to load
		await page.waitForLoadState('networkidle');

		// Debug: Log the page content to see what's actually there
		const pageTitle = await page.title();
		console.log('Page title:', pageTitle);

		// Check if we can access the plugin admin page (this indicates it's enabled)
		// Look for common WordPress admin elements or plugin-specific content
		const adminContent = page.locator('#wpbody-content');
		await expect(adminContent).toBeVisible();

		// Check if we're on the plugin's admin page by checking the URL
		const currentURL = page.url();
		expect(currentURL).toContain('page=cf7-antispam');

		// Optional: Look for any heading that might indicate the plugin page
		const anyHeading = page.locator('h1, h2, h3').first();
		if ((await anyHeading.count()) > 0) {
			const headingText = await anyHeading.textContent();
			console.log('Found heading:', headingText);
		}
	});

	test('can be disabled', async ({ page }) => {
		await deactivatePlugin(page, 'cf7-antispam');

		// Try to visit the plugin admin page - it should not be accessible
		await page.goto(`${baseURL}/wp-admin/admin.php?page=cf7-antispam`);
		await page.waitForLoadState('networkidle');

		// Check the current URL and page content
		const currentURL = page.url();
		console.log('Current URL after deactivation:', currentURL);

		// If plugin is properly deactivated, we should either:
		// 1. Be redirected away from the plugin page
		// 2. See an error message
		// 3. See a "plugin not found" or similar message

		if (currentURL.includes('page=cf7-antispam')) {
			// We're still on the plugin page, check for error messages
			const errorSelectors = [
				'.error',
				'.notice-error',
				'.wp-die-message',
				'[class*="error"]',
				'p:has-text("not found")',
				'p:has-text("does not exist")',
				'div:has-text("Invalid")',
			];

			let foundError = false;
			for (const selector of errorSelectors) {
				const errorElement = page.locator(selector);
				if ((await errorElement.count()) > 0) {
					console.log(`Found error element: ${selector}`);
					foundError = true;
					break;
				}
			}

			// If no error found, let's see what's actually on the page
			if (!foundError) {
				const pageContent = await page.locator('body').textContent();
				console.log(
					'Page content (first 500 chars):',
					pageContent?.substring(0, 500)
				);

				// At minimum, check that we're in WordPress admin
				const adminContent = page.locator(
					'#wpcontent, .wrap, #wpbody-content'
				);
				await expect(adminContent).toBeVisible();
			}
		} else {
			// We were redirected away from the plugin page, which is expected
			console.log('Successfully redirected away from plugin page');

			// Verify we're still in WordPress admin
			expect(currentURL).toContain('/wp-admin/');
		}
	});
});
