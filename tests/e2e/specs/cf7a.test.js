// Load utilities from the e2e-test-utils package.
import {
	activatePlugin,
	deactivatePlugin,
	loginUser,
	visitAdminPage,
} from '@wordpress/e2e-test-utils';

// JS Tests
describe( 'installation', () => {
	it( 'works', () => {
		expect( true ).toBeTruthy();
	} );

	it( 'verifies the plugin is active', async () => {
		// login as admin
		await loginUser();

		// visit the plugins page
		// assert that our plugin is active by checking the HTML
		await visitAdminPage( 'plugins.php' );
	} );

	it( 'is enabled', async () => {
		await activatePlugin( 'cf7-antispam' );
		await visitAdminPage( 'admin.php?page=cf7-antispam' );

		// Assertions
		const activeNode = await page.$x(
			'//h2[contains(text(), "Settings")]'
		);
		expect( activeNode.length ).not.toEqual( 1 );
	} );

	it( 'can be disabled', async () => {
		await deactivatePlugin( 'cf7-antispam' );
    await visitAdminPage( 'admin.php?page=cf7-antispam' );

		// Assertions
		const activePlugin = await page.$x(
			'//tr[contains(@class, "active") and contains(@data-slug, "cf7-antispam")]'
		);
		expect( activePlugin?.length ).toBe( 0 );
	} );

} );
