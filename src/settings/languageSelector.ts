import { getAll } from 'isotolanguage';

/**
 * Initialize the language selector for allowed and disallowed languages.
 */
export const initLanguageSelector = () => {
	const allowedTextarea = document.getElementById(
		'languages_allowed'
	) as HTMLTextAreaElement;
	const disallowedTextarea = document.getElementById(
		'languages_disallowed'
	) as HTMLTextAreaElement;

	if (!allowedTextarea || !disallowedTextarea) {
		return;
	}

	// Hide the original textareas
	allowedTextarea.style.display = 'none';
	disallowedTextarea.style.display = 'none';

	createSelectorUI(allowedTextarea, 'Allowed Languages');
	createSelectorUI(disallowedTextarea, 'Disallowed Languages');
};

const createSelectorUI = (textarea: HTMLTextAreaElement, label: string) => {
	const container = document.createElement('div');
	container.className = 'cf7a-language-selector-container';

	const wrapper = document.createElement('div');
	wrapper.className = 'cf7a-language-selector-wrapper';

	// Toggle Checkbox
	const toggleLabel = document.createElement('label');
	toggleLabel.style.display = 'block';
	toggleLabel.style.marginBottom = '10px';

	const toggleCheckbox = document.createElement('input');
	toggleCheckbox.type = 'checkbox';
	toggleCheckbox.style.marginRight = '5px';

	toggleLabel.appendChild(toggleCheckbox);
	toggleLabel.appendChild(
		document.createTextNode('Show raw input (Manual entry)')
	);

	// Get all countries and their languages
	const allData = getAll('all' as any);

	// Parse current values
	const parseValues = () =>
		textarea.value
			.split('\n')
			.map((s) => s.trim())
			.filter((s) => s);

	// State
	let usedCodes = new Set(parseValues());

	// Create UI elements
	const availableSelect = document.createElement('select');
	availableSelect.multiple = true;
	availableSelect.className = 'form-control add-select';
	availableSelect.style.height = '300px';
	availableSelect.style.width = '45%';
	availableSelect.style.display = 'inline-block';

	const selectedSelect = document.createElement('select');
	selectedSelect.multiple = true;
	selectedSelect.className = 'form-control remove-select';
	selectedSelect.style.height = '300px';
	selectedSelect.style.width = '45%';
	selectedSelect.style.display = 'inline-block';
	selectedSelect.style.marginLeft = '10px';

	const controlsDiv = document.createElement('div');
	controlsDiv.style.display = 'inline-block';
	controlsDiv.style.verticalAlign = 'top';
	controlsDiv.style.margin = '0 10px';

	const addButton = document.createElement('button');
	addButton.textContent = 'Add >';
	addButton.className = 'button button-secondary';
	addButton.type = 'button';

	const removeButton = document.createElement('button');
	removeButton.textContent = '< Remove';
	removeButton.className = 'button button-secondary';
	removeButton.type = 'button';
	removeButton.style.marginTop = '5px';

	controlsDiv.appendChild(addButton);
	controlsDiv.appendChild(document.createElement('br'));
	controlsDiv.appendChild(removeButton);

	// Add elements to wrapper
	wrapper.appendChild(availableSelect);
	wrapper.appendChild(controlsDiv);
	wrapper.appendChild(selectedSelect);

	container.appendChild(toggleLabel);
	container.appendChild(wrapper);

	textarea.parentNode?.insertBefore(container, textarea);
	// Move textarea inside container to keep things grouped, or just leave it?
	// Leaving it is fine, but we need to control its display.
	// Actually, let's put it after the label but before wrapper?
	// Or just control it via ID since we have the reference.

	// Helper to get sorted countries
	const sortedCountries = Object.values(allData).sort((a: any, b: any) => {
		return a.name.localeCompare(b.name);
	});

	// Render Function
	const render = () => {
		// Clear selects
		availableSelect.innerHTML = '';
		selectedSelect.innerHTML = '';

		sortedCountries.forEach((country: any) => {
			if (!country || !country.iso2) {
				return;
			}

			const countryCode = country.iso2;
			const countryName = country.name;
			const isCountryBlocked = usedCodes.has(countryCode);

			// Collect all language codes for this country
			const countryLanguageCodes: { code: string; name: string }[] = [];
			if (country.languages && Array.isArray(country.languages)) {
				country.languages.forEach((lang: any, index: number) => {
					if (!lang) {
						return;
					}
					let code = lang.iso2;
					if (
						country['language-code'] &&
						Array.isArray(country['language-code']) &&
						country['language-code'][index]
					) {
						code = country['language-code'][index];
					}
					if (code) {
						countryLanguageCodes.push({ code, name: lang.name });
					}
				});
			}

			// Split languages into Selected and Available
			const selectedLangs = countryLanguageCodes.filter((l) =>
				usedCodes.has(l.code)
			);
			const availableLangs = countryLanguageCodes.filter(
				(l) => !usedCodes.has(l.code)
			);

			// --- RENDER SELECTED PANE ---
			if (isCountryBlocked) {
				// Case 1: Country is fully blocked.
				const countryOption = document.createElement('option');
				countryOption.value = countryCode;
				countryOption.textContent = countryName;
				countryOption.style.fontWeight = 'bold';
				countryOption.setAttribute('data-type', 'country');
				selectedSelect.appendChild(countryOption);

				countryLanguageCodes.forEach((l) => {
					const langOption = document.createElement('option');
					langOption.value = l.code;
					langOption.textContent = `\u00A0\u00A0\u00A0\u00A0${l.name} (${l.code})`;
					langOption.setAttribute('data-type', 'language');
					selectedSelect.appendChild(langOption);
				});
			} else if (selectedLangs.length > 0) {
				// Case 2: Country NOT blocked, but some languages are.
				const headerOption = document.createElement('option');
				headerOption.value = `HEADER_${countryCode}`;
				headerOption.textContent = countryName;
				headerOption.style.fontWeight = 'bold';
				headerOption.setAttribute('data-type', 'header');
				selectedSelect.appendChild(headerOption);

				selectedLangs.forEach((l) => {
					const langOption = document.createElement('option');
					langOption.value = l.code;
					langOption.textContent = `\u00A0\u00A0\u00A0\u00A0${l.name} (${l.code})`;
					langOption.setAttribute('data-type', 'language');
					selectedSelect.appendChild(langOption);
				});
			}

			// --- RENDER AVAILABLE PANE ---
			if (!isCountryBlocked) {
				const countryOption = document.createElement('option');
				countryOption.value = countryCode;
				countryOption.textContent = countryName;
				countryOption.style.fontWeight = 'bold';
				countryOption.setAttribute('data-type', 'country');
				availableSelect.appendChild(countryOption);

				availableLangs.forEach((l) => {
					const langOption = document.createElement('option');
					langOption.value = l.code;
					langOption.textContent = `\u00A0\u00A0\u00A0\u00A0${l.name} (${l.code})`;
					langOption.setAttribute('data-type', 'language');
					availableSelect.appendChild(langOption);
				});
			}
		});
	};

	// Initial Render
	render();

	// Update Textarea
	const updateTextarea = () => {
		// Filter out headers
		const codes = Array.from(usedCodes).filter(
			(c) => !c.startsWith('HEADER_')
		);
		textarea.value = codes.join('\n');
	};

	// Add Button Logic
	addButton.addEventListener('click', () => {
		const selectedOptions = Array.from(availableSelect.selectedOptions);
		let changed = false;

		selectedOptions.forEach((option) => {
			const type = option.getAttribute('data-type');
			const value = option.value;

			if (type === 'country') {
				if (!usedCodes.has(value)) {
					usedCodes.add(value);
					changed = true;
				}
			} else if (type === 'language') {
				if (!usedCodes.has(value)) {
					usedCodes.add(value);
					changed = true;
				}
			}
		});

		if (changed) {
			render();
			updateTextarea();
		}
	});

	// Remove Button Logic
	removeButton.addEventListener('click', () => {
		const selectedOptions = Array.from(selectedSelect.selectedOptions);
		let changed = false;

		selectedOptions.forEach((option) => {
			const type = option.getAttribute('data-type');
			const value = option.value;

			if (type === 'header') {
				const countryCode = value.replace('HEADER_', '');
				const country = sortedCountries.find(
					(c: any) => c.iso2 === countryCode
				);
				if (country) {
					const codesToRemove: string[] = [];
					if (country.languages && Array.isArray(country.languages)) {
						country.languages.forEach(
							(lang: any, index: number) => {
								let code = lang.iso2;
								if (country['language-code']?.[index]) {
									code = country['language-code'][index];
								}
								if (code) {
									codesToRemove.push(code);
								}
							}
						);
					}
					codesToRemove.forEach((c) => {
						if (usedCodes.has(c)) {
							usedCodes.delete(c);
							changed = true;
						}
					});
				}
			} else if (type === 'country') {
				if (usedCodes.has(value)) {
					usedCodes.delete(value);
					changed = true;
				}
			} else if (type === 'language') {
				if (usedCodes.has(value)) {
					usedCodes.delete(value);
					changed = true;
				}
			}
		});

		if (changed) {
			render();
			updateTextarea();
		}
	});

	// Toggle Logic
	toggleCheckbox.addEventListener('change', () => {
		if (toggleCheckbox.checked) {
			// Show Raw Input
			textarea.style.display = 'block';
			wrapper.style.display = 'none';
		} else {
			// Show Utility
			textarea.style.display = 'none';
			wrapper.style.display = 'block';

			// Sync from textarea
			usedCodes = new Set(parseValues());
			render();
		}
	});
};
