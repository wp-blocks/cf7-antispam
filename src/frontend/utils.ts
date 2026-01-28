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
	// eslint-disable-next-line camelcase
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
