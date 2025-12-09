/**
 * WordPress dependencies
 */
const { test, expect } = require('@wordpress/e2e-test-utils-playwright');
const fs = require('fs');
test.describe('Contact Form 7 AntiSpam E2E', () => {
	const debugLog = 'debug.log';

	const cf7Plugin = 'contact-form-7'; // For activation/deactivation
	const pluginActivationSlug = 'antispam-for-contact-form-7'; // For activation/deactivation

	// Clean the log file before tests start to ensure a clean state
	test.beforeAll(async ({ requestUtils }) => {
		// Activate plugins
		await requestUtils.activatePlugin(cf7Plugin);
		await requestUtils.activatePlugin(pluginActivationSlug);

		// Clear debug.log inside the container
		fs.writeFileSync(debugLog, '');
		console.log('debug.log cleared via CLI');
	});

	test('Should detect spam on contact form submission', async ({
		admin,
		page,
		requestUtils,
	}) => {
		// --- 1. Create a Contact Form via UI (Because CF7 has no REST API) ---
		console.log('Creating form via UI...');

		// Navigate to the CF7 "Add New" page directly
		await admin.visitAdminPage('admin.php?page=wpcf7-new');

		// Fill the Title
		await page.locator('#title').fill('AntiSpam E2E Test Form');

		// Fill the Form Content (CF7 uses a simple textarea with id 'wpcf7-form')
		const formContent = `
<label> Your Name
    [text* your-name] </label>

<label> Your Email
    [email* your-email] </label>

<label> Subject
    [text* your-subject] </label>

<label> Your Message (optional)
    [textarea your-message] </label>

[submit "Send"]
`;
		await page.locator('textarea#wpcf7-form').fill(formContent);

		// Save the form
		await page.locator('#publishing-action .button-primary').click();

		// Wait for the save to complete. The URL will change to include "?page=wpcf7&post=ID..."
		await page.waitForURL(/page=wpcf7&post=\d+/);

		// Extract the Form ID from the URL
		const url = page.url();
		const urlParams = new URLSearchParams(url.split('?')[1]);
		const formId = urlParams.get('post');

		expect(formId).toBeDefined();
		console.log(`Form created with ID: ${formId}`);

		// --- 2. Create a Page with the Contact Form 7 shortcode ---
		console.log('Creating page...');
		const shortcode = `[contact-form-7 id="${formId}" title="AntiSpam E2E Test Form"]`;

		const pagePost = await requestUtils.createPost({
			postType: 'page',
			title: 'AntiSpam Test Page',
			content: shortcode,
			status: 'publish',
		});

		expect(pagePost).toBeDefined();
		console.log(`Page created: ${pagePost.id} at ${pagePost.link}`);

		// --- 3. Visit the created page on the frontend ---
		console.log('Visiting page...');
		await page.goto(pagePost.link);

		// Check for the hidden field presence
		console.log('Checking hidden field presence...');
		await expect(page.locator('.hidden-fields-container')).toBeHidden();

		console.log('Counting the hidden fields that start with _cf7');
		const hiddenFieldsCount = await page
			.locator('.hidden-fields-container')
			.locator('input[name^="_cf7"]')
			.count();
		expect(hiddenFieldsCount).not.toBe(0);
		console.log(`cf7a Hidden fields count: ${hiddenFieldsCount}`);

		// --- 4. Fill out the form with "Spammy" content ---
		console.log('Filling form...');

		// Using generic selectors based on the 'name' attribute is safe here
		await page.locator('input[name="your-name"]').fill('Test Spammer');
		await page
			.locator('input[name="your-email"]')
			.fill('spammer@example.com');
		await page
			.locator('input[name="your-subject"]')
			.fill('asdjkhas jkdhaskjdha skdhas');
		await page
			.locator('textarea[name="your-message"]')
			.fill(
				'Ciaone come va? viagra\n' +
				'Earn extra cash\n' +
				'MEET SINGLES\n' +
				'1234567890'
			);

		// --- 5. Submit the form ---
		console.log('Submitting form...');
		// CF7 submit buttons usually have the class .wpcf7-submit
		await page.locator('.wpcf7-submit').click();

		// --- 6. Wait for the response ---
		console.log('Waiting for response...');
		// Wait for the loader to disappear and response to appear
		const responseLocator = page.locator('.wpcf7-response-output');
		await responseLocator.waitFor({ state: 'visible' });

		// Optional: Assert the frontend message says it was spam (orange border usually)
		console.log('Checking response message...');
		await expect(responseLocator).toHaveText(
			'There was an error trying to send your message. Please try again later.'
		);

		// --- 7. CHECK LOG FILE ---
		console.log('Verifying debug.log content...');
		await page.waitForTimeout(2000);

		// Read log via CLI
		const logContent = fs.readFileSync(debugLog, 'utf8');
		console.log('--- LOG CONTENT START ---');
		console.log(logContent);
		console.log('--- LOG CONTENT END ---');

		// Assert specific details in the log
		// Expect 'viagra' as it is the bad word found
		expect(logContent.toLowerCase()).toContain('new submission from 172.');
		expect(logContent.toLowerCase()).toContain('viagra');
		expect(logContent.toLowerCase()).toContain('cf7a: ban');
	});
});
