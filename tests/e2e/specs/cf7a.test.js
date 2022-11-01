// Load utilities from the e2e-test-utils package.
import {
	activatePlugin,
	deactivatePlugin,
	loginUser,
	visitAdminPage,
} from '@wordpress/e2e-test-utils';

// JS Tests
describe( 'installation', () => {
	// Flow being tested.
	// Ideally each flow is independent and can be run separately.
	it( 'Should load properly', async () => {
		// login as admin
		await loginUser();

		// Navigate the admin and performs tasks
		// Use Puppeteer APIs to interacte with mouse, keyboard...
		await visitAdminPage( '/' );

		// Assertions
		const nodes = await page.$x(
			'//h2[contains(text(), "Welcome to WordPress!")]'
		);

		expect( nodes.length ).not.toEqual( 0 );
	} );

	visitAdminPage( '/plugins.php' ).then( () => {
		it( 'is enabled', async () => {
			// await activatePlugin( 'cf7-antispam' );

			// Assertions
			const activeNode = await page.$x(
				'//a[contains(text(), "Antispam Settings")]'
			);
			expect( activeNode.length ).not.toEqual( 0 );
		} );
		it( 'can be disabled', async () => {
			await deactivatePlugin( 'cf7-antispam' );

			// Assertions
			const activePlugin = await page.$x(
				'//tr[contains(@class, "active") and contains(@data-slug, "cf7-antispam")]'
			);
			expect( activePlugin?.length ).toBe( 0 );
		} );
		it( 'can be disabled', async () => {
			await activatePlugin( 'cf7-antispam' );

			// Assertions
			const activePlugin = await page.$x(
				'//tr[contains(@class, "active") and contains(@data-slug, "cf7-antispam")]'
			);
			expect( activePlugin?.length ).toBe( 1 );
		} );
	} );
} );
