/**
 * It creates a hidden input field with a name and value
 *
 * @param {string}                    key      - the name of the field
 * @param {string | number | boolean} value    - The value of the field.
 * @param {string}                    [prefix] - The prefix for the field name.
 *
 * @return {HTMLElement} A new input element with the type of hidden, name of the key, and value of the value.
 */
export function createCF7Afield(
	key: string,
	value: string | number | boolean,

	prefix: string = cf7a_settings.prefix
): HTMLElement {
	const e = document.createElement('input');
	e.setAttribute('type', 'hidden');
	e.setAttribute('name', prefix + key);

	let stringValue: string;
	if (typeof value === 'string') {
		stringValue = value;
	} else if (typeof value === 'number' || typeof value === 'boolean') {
		stringValue = String(value);
	} else {
		stringValue = JSON.stringify(value);
	}

	e.setAttribute('value', stringValue);
	return e;
}

/**
 * Update an existing hidden input field if present.
 *
 * @param {HTMLElement} hiddenInputsContainer The Element that contains the hidden fields
 * @param {string}      name                  The full field name
 * @param {string}      value                 The value to set
 */
export function setExistingHiddenFieldValue(
	hiddenInputsContainer: HTMLElement,
	name: string,
	value: string
): void {
	const input = hiddenInputsContainer.querySelector(
		`input[name="${name}"]`
	) as HTMLInputElement | null;

	if (!input) {
		return;
	}

	input.setAttribute('value', value);
	input.value = value;
}

/**
 * Generate a random string
 *
 * @param {number} length - The length of the string to generate.
 * @return {string} The generated string.
 */
export function randomString(length: number = 12): string {
	const chars =
		'0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	let result = '';
	for (let i = length; i > 0; --i) {
		result += chars[Math.floor(Math.random() * chars.length)];
	}
	return result;
}

/**
 * Fetch and set the timestamp if it's missing
 *
 * @param {HTMLInputElement} tsInput the input that contains the timestamp value
 * @param {string}           restUrl The rest url of the current website
 */
export async function setTimestamp(tsInput: HTMLInputElement, restUrl: string) {
	try {
		const response = await fetch(`${restUrl}/get-timestamp`, {
			method: 'POST',
			headers: {
				Accept: 'application/json',
				'Content-Type': 'application/json',
			},
		});
		if (response.ok) {
			const data = await response.json();
			if (data.timestamp) {
				tsInput.setAttribute('value', data.timestamp);
				tsInput.value = data.timestamp;
			}
		}
	} catch (e) {
		console.error('CF7 Antispam: Failed to fetch timestamp', e);
	}
}

/**
 * Fetch and set the distributed-bot token if the hidden field exists.
 *
 * @param {HTMLInputElement} tokenInput The input that contains the token value
 * @param {string}           restUrl    The rest url of the current website
 */
export async function setDistributedBotToken(
	tokenInput: HTMLInputElement,
	restUrl: string
) {
	try {
		const response = await fetch(`${restUrl}/get-ip-token`, {
			method: 'POST',
			headers: {
				Accept: 'application/json',
				'Content-Type': 'application/json',
			},
		});
		if (response.ok) {
			const data = await response.json();
			if (data.token) {
				tokenInput.setAttribute('value', data.token);
				tokenInput.value = data.token;
			}
		}
	} catch (e) {
		console.error('CF7 Antispam: Failed to fetch distributed bot token', e);
	}
}
