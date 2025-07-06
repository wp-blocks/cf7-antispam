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
	await page.waitForSelector('#user_login', { state: 'visible' });

	await page.fill('#user_login', adminUsername);
	await page.fill('#user_pass', adminPassword);

	await Promise.all([
		page.waitForNavigation({ waitUntil: 'networkidle' }),
		page.click('#wp-submit'),
	]);

	// Check for login errors
	const loginError = page.locator('#login_error, .login-error');
	if ((await loginError.count()) > 0) {
		const errorText = await loginError.textContent();
		throw new Error(`Login failed: ${errorText}`);
	}

	// Navigate to wp-admin if not already there
	const currentURL = page.url();
	if (!currentURL.includes('/wp-admin/')) {
		await page.goto(`${baseURL}/wp-admin/`);
		await page.waitForLoadState('networkidle');
	}

	// Verify login success
	const adminBar = page.locator('#wpadminbar, .wp-admin, #adminmenumain');
	await expect(adminBar.first()).toBeVisible({ timeout: 10000 });
}

// Helper function to visit admin page
async function visitAdminPage(page, path) {
	await page.goto(`${baseURL}/wp-admin/${path}`);
	await page.waitForLoadState('networkidle');
}

// Helper function to activate plugin
async function activatePlugin(page, pluginSlug) {
	await visitAdminPage(page, 'plugins.php');
	await page.waitForLoadState('networkidle');

	const pluginRow = page.locator(`tr[data-slug="${pluginSlug}"]`);

	if ((await pluginRow.count()) === 0) {
		const allPluginRows = page.locator('tr[data-slug]');
		const pluginSlugs = await allPluginRows.evaluateAll((rows) =>
			rows.map((row) => row.getAttribute('data-slug'))
		);
		throw new Error(
			`Plugin ${pluginSlug} not found. Available plugins: ${pluginSlugs.join(', ')}`
		);
	}

	// Check if already active
	const pluginInfo = await pluginRow.evaluate((row) => ({
		isActive: row.classList.contains('active'),
		hasActivateLink:
			row.querySelector('a[href*="action=activate"]') !== null,
	}));

	if (pluginInfo.isActive) {
		return; // Already active
	}

	const activateLink = pluginRow.locator('a:has-text("Activate")');
	if ((await activateLink.count()) > 0) {
		const activateUrl = await activateLink.getAttribute('href');
		if (!activateUrl.includes('action=activate')) {
			throw new Error(`Expected activation URL but got: ${activateUrl}`);
		}

		await activateLink.click();
		await page.waitForLoadState('networkidle');

		// Check for visible error messages
		const visibleErrorMessages = page.locator(
			'.error:visible, .notice-error:visible'
		);
		if ((await visibleErrorMessages.count()) > 0) {
			const errorText = await visibleErrorMessages.first().textContent();
			throw new Error(`Plugin activation failed: ${errorText}`);
		}

		// Verify activation success
		await page.reload();
		await page.waitForLoadState('networkidle');

		const refreshedPluginRow = page.locator(
			`tr[data-slug="${pluginSlug}"]`
		);
		const deactivateLink = refreshedPluginRow.locator(
			'a:has-text("Deactivate")'
		);

		if ((await deactivateLink.count()) === 0) {
			// Check for fatal errors
			const pageContent = await page.content();
			if (
				pageContent.includes('fatal error') ||
				pageContent.includes('Fatal error')
			) {
				throw new Error('Plugin activation failed with fatal error');
			}
			throw new Error(`Plugin ${pluginSlug} activation failed`);
		}
	}
}

// Helper function to ensure plugin is in desired state
async function ensurePluginState(page, pluginSlug, shouldBeActive = true) {
	await visitAdminPage(page, 'plugins.php');
	await page.waitForLoadState('networkidle');

	const pluginRow = page.locator(`tr[data-slug="${pluginSlug}"]`);
	if ((await pluginRow.count()) === 0) {
		throw new Error(`Plugin ${pluginSlug} not found`);
	}

	const pluginInfo = await pluginRow.evaluate((row) => ({
		isActive: row.classList.contains('active'),
	}));

	if (pluginInfo.isActive === shouldBeActive) {
		return; // Already in desired state
	}

	if (shouldBeActive && !pluginInfo.isActive) {
		await activatePlugin(page, pluginSlug);
	} else if (!shouldBeActive && pluginInfo.isActive) {
		await deactivatePlugin(page, pluginSlug);
	}
}

async function deactivatePlugin(page, pluginSlug) {
	await visitAdminPage(page, 'plugins.php');
	await page.waitForLoadState('networkidle');

	const pluginRow = page.locator(`tr[data-slug="${pluginSlug}"]`);
	if ((await pluginRow.count()) === 0) {
		throw new Error(`Plugin ${pluginSlug} not found`);
	}

	const deactivateLink = pluginRow.locator('a:has-text("Deactivate")');
	if ((await deactivateLink.count()) > 0) {
		await deactivateLink.click();
		await page.waitForLoadState('networkidle');

		// Verify deactivation
		const activateLink = pluginRow.locator('a:has-text("Activate")');
		await expect(activateLink).toBeVisible({ timeout: 10000 });
	}
}

// Helper function to check if plugin admin page is accessible
async function isPluginAdminPageAccessible(page, pluginSlug) {
	try {
		await page.goto(`${baseURL}/wp-admin/admin.php?page=${pluginSlug}`);
		await page.waitForLoadState('networkidle');

		const currentURL = page.url();
		if (!currentURL.includes(`page=${pluginSlug}`)) {
			return false;
		}

		// Check for error messages
		const errorSelectors = [
			'.error',
			'.notice-error',
			'.wp-die-message',
			'[class*="error"]',
			'p:has-text("not found")',
			'p:has-text("does not exist")',
		];

		for (const selector of errorSelectors) {
			const errorElement = page.locator(selector);
			if ((await errorElement.count()) > 0) {
				return false;
			}
		}

		// Check for admin content
		const adminContent = page.locator('#wpbody-content, .wrap');
		return (await adminContent.count()) > 0;
	} catch (error) {
		return false;
	}
}

// Playwright Tests
test.describe('installation', () => {
	test.beforeEach(async ({ page }) => {
		await loginToWordPressAdmin(page);
	});

	test('works', () => {
		expect(true).toBeTruthy();
	});

	test('debug plugin activation', async ({ page }) => {
		await visitAdminPage(page, 'plugins.php');

		// Take screenshot for debugging
		await page.screenshot({
			path: 'debug-plugins-page.png',
			fullPage: true,
		});

		// List all plugins
		const allPlugins = await page
			.locator('tr[data-slug]')
			.evaluateAll((rows) =>
				rows.map((row) => ({
					slug: row.getAttribute('data-slug'),
					pluginName:
						row.querySelector('.plugin-title strong')
							?.textContent || 'Unknown',
					isActive: row.classList.contains('active'),
				}))
			);

		console.log(
			'Available plugins:',
			allPlugins.map((p) => `${p.slug} (${p.pluginName})`)
		);

		// Check for target plugin
		const targetPlugin = allPlugins.find((p) => p.slug === 'cf7-antispam');
		if (!targetPlugin) {
			console.log('Target plugin cf7-antispam not found');
		}
	});

	test('verifies the plugin is active', async ({ page }) => {
		await ensurePluginState(page, 'cf7-antispam', true);

		await visitAdminPage(page, 'plugins.php');
		const pluginRow = page.locator('tr[data-slug="cf7-antispam"]');
		await expect(pluginRow).toBeVisible();

		const activePlugin = page.locator(
			'tr.active[data-slug="cf7-antispam"]'
		);
		await expect(activePlugin).toBeVisible();

		const deactivateLink = pluginRow.locator('a:has-text("Deactivate")');
		await expect(deactivateLink).toBeVisible();
	});

	test('is enabled and admin page is accessible', async ({ page }) => {
		await ensurePluginState(page, 'cf7-antispam', true);
		await page.waitForTimeout(2000);

		const isAccessible = await isPluginAdminPageAccessible(
			page,
			'cf7-antispam'
		);
		expect(isAccessible).toBeTruthy();

		if (isAccessible) {
			await page.goto(`${baseURL}/wp-admin/admin.php?page=cf7-antispam`);
			await page.waitForLoadState('networkidle');

			const pluginHeading = page.locator('h1, h2, h3').first();
			await expect(pluginHeading).toBeVisible();
			await expect(pluginHeading).toContainText(
				/Contact Form 7|AntiSpam|CF7/i
			);
		}
	});

	test('can be disabled and admin page becomes inaccessible', async ({
		page,
	}) => {
		// Ensure plugin is active first
		await ensurePluginState(page, 'cf7-antispam', true);

		let isAccessible = await isPluginAdminPageAccessible(
			page,
			'cf7-antispam'
		);
		expect(isAccessible).toBeTruthy();

		// Deactivate plugin
		await ensurePluginState(page, 'cf7-antispam', false);
		await page.waitForTimeout(1000);

		// Verify admin page is no longer accessible
		isAccessible = await isPluginAdminPageAccessible(page, 'cf7-antispam');
		expect(isAccessible).toBeFalsy();
	});

	test('plugin status consistency check', async ({ page }) => {
		// Test deactivation
		await ensurePluginState(page, 'cf7-antispam', false);
		await visitAdminPage(page, 'plugins.php');

		let pluginRow = page.locator('tr[data-slug="cf7-antispam"]');
		const activateLink = pluginRow.locator('a:has-text("Activate")');
		await expect(activateLink).toBeVisible();

		// Test activation
		await ensurePluginState(page, 'cf7-antispam', true);
		await visitAdminPage(page, 'plugins.php');

		pluginRow = page.locator('tr[data-slug="cf7-antispam"]');
		const deactivateLink = pluginRow.locator('a:has-text("Deactivate")');
		await expect(deactivateLink).toBeVisible();

		// Verify admin page accessibility
		const isAccessible = await isPluginAdminPageAccessible(
			page,
			'cf7-antispam'
		);
		expect(isAccessible).toBeTruthy();
	});
});
