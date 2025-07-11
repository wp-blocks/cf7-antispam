/**
 * WordPress dependencies
 */
const { test, expect } = require('@wordpress/e2e-test-utils-playwright');

test.describe('CF7 AntiSpam Plugin', () => {
	const pluginActivationSlug = 'antispam-for-contact-form-7'; // For activation/deactivation
	const pluginSlug = 'cf7-antispam'; // For HTML elements and admin pages
	const pluginName = 'AntiSpam for Contact Form 7';

	test.beforeAll(async ({ requestUtils }) => {
		// Ensure clean state - deactivate plugin if active
		try {
			await requestUtils.deactivatePlugin(pluginActivationSlug);
		} catch (error) {
			// Plugin might not be active, ignore error
			console.log('Plugin not active or not found, continuing...');
		}
	});

	test.afterAll(async ({ requestUtils }) => {
		// Clean up after tests
		try {
			await requestUtils.deactivatePlugin(pluginActivationSlug);
		} catch (error) {
			// Ignore cleanup errors
		}
	});

	test('plugin can be activated', async ({ admin, page, requestUtils }) => {
		// Activate the plugin
		await requestUtils.activatePlugin(pluginActivationSlug);

		// Visit plugins page to verify activation
		await admin.visitAdminPage('plugins.php');

		// Check if plugin is listed as active
		const pluginRow = page.locator(`tr[data-slug="${pluginSlug}"]`);
		await expect(pluginRow).toBeVisible();
		await expect(pluginRow).toHaveClass(/active/);

		// Check for deactivate link (indicates plugin is active)
		const deactivateLink = pluginRow.locator('a:has-text("Deactivate")');
		await expect(deactivateLink).toBeVisible();
	});

	test('plugin admin page is accessible when active', async ({
		admin,
		page,
		requestUtils,
	}) => {
		// Ensure plugin is active
		await requestUtils.activatePlugin(pluginActivationSlug);

		// Visit the plugin admin page
		await admin.visitAdminPage(`admin.php?page=${pluginSlug}`);

		// Check that we're on the right page
		await expect(page).toHaveURL(new RegExp(`page=${pluginSlug}`));

		// Check for plugin content (adjust selector based on your plugin)
		const pluginContent = page.locator('#wpbody-content .wrap');
		await expect(pluginContent).toBeVisible();

		// Check for plugin title or specific content
		const pageTitle = page.locator('h1, h2').first();
		await expect(pageTitle).toBeVisible();
		await expect(pageTitle).toContainText(
			new RegExp(pluginName.split(' ')[0], 'i')
		);
	});

	test('plugin can be deactivated', async ({ admin, page, requestUtils }) => {
		// First activate the plugin
		await requestUtils.activatePlugin(pluginActivationSlug);

		// Then deactivate it
		await requestUtils.deactivatePlugin(pluginActivationSlug);

		// Visit plugins page to verify deactivation
		await admin.visitAdminPage('plugins.php');

		// Check if plugin is listed as inactive
		const pluginRow = page.locator(`tr[data-slug="${pluginSlug}"]`);
		await expect(pluginRow).toBeVisible();
		await expect(pluginRow).toHaveClass(/inactive/);

		// Check for activate link (indicates plugin is inactive)
		const activateLink = pluginRow.locator('a:has-text("Activate")');
		await expect(activateLink).toBeVisible();
	});

	test('plugin admin page is not accessible when inactive', async ({
		admin,
		page,
		requestUtils,
	}) => {
		// Ensure plugin is deactivated
		await requestUtils.deactivatePlugin(pluginActivationSlug);

		// Try to visit the plugin admin page
		await admin.visitAdminPage(`admin.php?page=${pluginSlug}`);

		// Should be redirected or show error
		// Check if we're NOT on the plugin page or if there's an error
		const currentUrl = page.url();
		const isOnPluginPage = currentUrl.includes(`page=${pluginSlug}`);

		if (isOnPluginPage) {
			// If we're on the page, check for error messages
			const errorMessage = page.locator(
				'.error, .notice-error, .wp-die-message'
			);
			await expect(errorMessage).toBeVisible();
		} else {
			// We should be redirected away from the plugin page
			expect(isOnPluginPage).toBeFalsy();
		}
	});

	test('plugin activation and deactivation cycle', async ({
		admin,
		page,
		requestUtils,
	}) => {
		// Start with deactivated plugin
		await requestUtils.deactivatePlugin(pluginActivationSlug);

		// Activate
		await requestUtils.activatePlugin(pluginActivationSlug);

		// Verify activation
		await admin.visitAdminPage('plugins.php');
		let pluginRow = page.locator(`tr[data-slug="${pluginSlug}"]`);
		await expect(pluginRow).toHaveClass(/active/);

		// Verify admin page is accessible
		await admin.visitAdminPage(`admin.php?page=${pluginSlug}`);
		await expect(page).toHaveURL(new RegExp(`page=${pluginSlug}`));

		// Deactivate
		await requestUtils.deactivatePlugin(pluginActivationSlug);

		// Verify deactivation
		await admin.visitAdminPage('plugins.php');
		pluginRow = page.locator(`tr[data-slug="${pluginSlug}"]`);
		await expect(pluginRow).toHaveClass(/inactive/);
	});

	test('plugin shows correct information on plugins page', async ({
		admin,
		page,
		requestUtils,
	}) => {
		await admin.visitAdminPage('plugins.php');

		const pluginRow = page.locator(`tr[data-slug="${pluginSlug}"]`);
		await expect(pluginRow).toBeVisible();

		// Check plugin name
		const pluginTitle = pluginRow.locator('.plugin-title strong');
		await expect(pluginTitle).toBeVisible();
		await expect(pluginTitle).toContainText(pluginName.split(' ')[0]);

		// Check plugin description (if visible)
		const pluginDescription = pluginRow.locator('.plugin-description');
		if ((await pluginDescription.count()) > 0) {
			await expect(pluginDescription).toBeVisible();
		}

		// Check version information
		const versionInfo = pluginRow.locator('.plugin-version-author-uri');
		if ((await versionInfo.count()) > 0) {
			await expect(versionInfo).toBeVisible();
		}
	});

	/*test('plugin functionality works when active', async ({
                                                          admin,
                                                          page,
                                                          requestUtils,
                                                        }) => {
    // Activate plugin
    await requestUtils.activatePlugin(pluginActivationSlug);

    // Visit plugin admin page
    await admin.visitAdminPage(`admin.php?page=${pluginSlug}`);

    // TODO: Add tests for specific plugin functionality

    // Check for settings sections
    const settingsForm = page.locator('form');
    if ((await settingsForm.count()) > 0) {
      await expect(settingsForm).toBeVisible();
    }

    // Check for specific plugin elements
    const pluginElements = page.locator(
      '.cf7-antispam, [class*="cf7"], [id*="cf7"]'
    );
    if ((await pluginElements.count()) > 0) {
      await expect(pluginElements.first()).toBeVisible();
    }

    // TODO: Add more tests for specific plugin functionality here
  });
*/
	// Test plugin with Contact Form 7 integration (if applicable)
	test('plugin integrates with Contact Form 7', async ({
		admin,
		page,

		requestUtils,
	}) => {
		// Activate both plugins
		await requestUtils.activatePlugin('contact-form-7');
		await requestUtils.activatePlugin(pluginActivationSlug);

		// Visit Contact Form 7 forms page
		await admin.visitAdminPage('admin.php?page=wpcf7');

		// Check if CF7 is working
		const cf7Content = page.locator('.wrap');
		await expect(cf7Content).toBeVisible();

		// TODO: Test integration with CF7
	});
});

// Additional helper tests for edge cases
test.describe('CF7 AntiSpam Plugin - Edge Cases', () => {
	const pluginActivationSlug = 'antispam-for-contact-form-7'; // For activation/deactivation

	test('handles multiple activation attempts gracefully', async ({
		requestUtils,
	}) => {
		// Activate multiple times - should not cause errors
		await requestUtils.activatePlugin(pluginActivationSlug);
		await requestUtils.activatePlugin(pluginActivationSlug);
		await requestUtils.activatePlugin(pluginActivationSlug);

		// Should still work normally
		await requestUtils.deactivatePlugin(pluginActivationSlug);
	});

	test('handles multiple deactivation attempts gracefully', async ({
		requestUtils,
	}) => {
		// Activate first
		await requestUtils.activatePlugin(pluginActivationSlug);

		// Deactivate multiple times - should not cause errors
		await requestUtils.deactivatePlugin(pluginActivationSlug);
		await requestUtils.deactivatePlugin(pluginActivationSlug);
		await requestUtils.deactivatePlugin(pluginActivationSlug);

		// Should still work normally
		await requestUtils.activatePlugin(pluginActivationSlug);
		await requestUtils.deactivatePlugin(pluginActivationSlug);
	});
});
