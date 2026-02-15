/**
 * Wordlist Management Tab Functionality
 *
 * Handles API calls and UI interactions for the B8 dictionary wordlist management tab.
 */

/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';

interface Word {
	token: string;
	count_spam: number | null;
	count_ham: number | null;
}

interface WordlistResponse {
	success: boolean;
	words: Word[];
	total: number;
	page: number;
	per_page: number;
	total_pages: number;
}

interface ApiResponse {
	success: boolean;
	message: string;
}

// State
let currentPage = 1;
let totalPages = 1;
let perPage = 50;
let typeFilter = 'all';
let searchQuery = '';
let currentOrderBy = 'measure';
let currentOrder = 'desc';

// DOM Elements
const getElements = () => ({
	container: document.querySelector('.cf7a-wordlist-manager') as HTMLElement,
	tableBody: document.getElementById(
		'cf7a-wordlist-body'
	) as HTMLTableSectionElement,
	searchInput: document.getElementById(
		'cf7a-wordlist-search'
	) as HTMLInputElement,
	searchBtn: document.getElementById(
		'cf7a-wordlist-search-btn'
	) as HTMLButtonElement,
	typeFilterSelect: document.getElementById(
		'cf7a-wordlist-type-filter'
	) as HTMLSelectElement,
	perPageSelect: document.getElementById(
		'cf7a-wordlist-per-page'
	) as HTMLSelectElement,
	prevBtn: document.getElementById('cf7a-wordlist-prev') as HTMLButtonElement,
	nextBtn: document.getElementById('cf7a-wordlist-next') as HTMLButtonElement,
	pageInput: document.getElementById(
		'cf7a-wordlist-page'
	) as HTMLInputElement,
	totalPagesSpan: document.getElementById(
		'cf7a-wordlist-total-pages'
	) as HTMLSpanElement,
	totalWordsSpan: document.getElementById(
		'cf7a-wordlist-total-words'
	) as HTMLSpanElement,
	editModal: document.getElementById(
		'cf7a-wordlist-edit-modal'
	) as HTMLDivElement,
	editTokenDisplay: document.getElementById(
		'cf7a-edit-token'
	) as HTMLSpanElement,
	editTokenValue: document.getElementById(
		'cf7a-edit-token-value'
	) as HTMLInputElement,
	editSpamCount: document.getElementById(
		'cf7a-edit-spam-count'
	) as HTMLInputElement,
	editHamCount: document.getElementById(
		'cf7a-edit-ham-count'
	) as HTMLInputElement,
	saveWordBtn: document.getElementById('cf7a-save-word') as HTMLButtonElement,
});

/**
 * Get nonce from the container element
 */
const getNonce = (): string => {
	const container = document.querySelector(
		'.cf7a-wordlist-manager'
	) as HTMLElement;
	return container?.dataset.nonce || '';
};

/**
 * Calculate spam probability score (0-1, higher = more spam-like)
 * @param {number} spamCount The count of spam occurrences
 * @param {number} hamCount  The count of ham occurrences
 */
const calculateScore = (spamCount: number, hamCount: number): number => {
	if (spamCount === 0 && hamCount === 0) {
		return 0.5;
	}
	const total = spamCount + hamCount;
	return total > 0 ? spamCount / total : 0.5;
};

/**
 * Get score class for styling
 * @param {number} score The spam probability
 */
const getScoreClass = (score: number): string => {
	if (score > 0.8) {
		return 'cf7a-score-spam';
	}
	if (score > 0.5) {
		return 'cf7a-score-leaning-spam';
	}
	if (score < 0.2) {
		return 'cf7a-score-ham';
	}
	if (score < 0.5) {
		return 'cf7a-score-leaning-ham';
	}
	return 'cf7a-score-neutral';
};

/**
 * Fetch wordlist from the API
 */
const fetchWordlist = async (): Promise<void> => {
	const elements = getElements();
	if (!elements.tableBody) {
		return;
	}

	// Show loading state
	elements.tableBody.innerHTML = `
		<tr class="cf7a-loading-row">
			<td colspan="5">
				<span class="spinner is-active"></span>
				Loading words...
			</td>
		</tr>
	`;

	try {
		const response = await apiFetch<WordlistResponse>({
			path: `/cf7-antispam/v1/get-wordlist?page=${currentPage}&per_page=${perPage}&type=${typeFilter}&search=${encodeURIComponent(searchQuery)}&orderby=${currentOrderBy}&order=${currentOrder}`,
			method: 'GET',
		});

		if (response.success) {
			renderWordlist(response.words);
			totalPages = response.total_pages;
			updatePagination(response);
		} else {
			showError('Failed to fetch wordlist');
		}
	} catch (error) {
		// eslint-disable-next-line no-console
		console.error('Error fetching wordlist:', error);
		showError('Failed to fetch wordlist. Please try again.');
	}
};

/**
 * Render the wordlist table
 * @param {Word[]} words The words to render
 */
const renderWordlist = (words: Word[]): void => {
	const elements = getElements();
	if (!elements.tableBody) {
		return;
	}

	if (words.length === 0) {
		elements.tableBody.innerHTML = `
			<tr class="cf7a-empty-row">
				<td colspan="5">
					<em>No words found matching your criteria.</em>
				</td>
			</tr>
		`;
		return;
	}

	elements.tableBody.innerHTML = words
		.map((word) => {
			const spamCount = word.count_spam || 0;
			const hamCount = word.count_ham || 0;
			const score = calculateScore(spamCount, hamCount);
			const scoreClass = getScoreClass(score);

			return `
			<tr data-token="${escapeHtml(word.token)}">
				<td class="column-token">
					<code>${escapeHtml(word.token)}</code>
				</td>
				<td class="column-spam">
					<span class="cf7a-count-spam">${spamCount}</span>
				</td>
				<td class="column-ham">
					<span class="cf7a-count-ham">${hamCount}</span>
				</td>
				<td class="column-score">
					<span class="cf7a-score-badge ${scoreClass}">${(score * 100).toFixed(0)}%</span>
				</td>
				<td class="column-actions">
					<button type="button" class="button button-small cf7a-edit-word" data-token="${escapeHtml(word.token)}" data-spam="${spamCount}" data-ham="${hamCount}">
						<span class="dashicons dashicons-edit"></span>
					</button>
					<button type="button" class="button button-small cf7a-delete-word" data-token="${escapeHtml(word.token)}">
						<span class="dashicons dashicons-trash"></span>
					</button>
				</td>
			</tr>
		`;
		})
		.join('');

	// Attach event listeners
	attachRowEventListeners();
};

/**
 * Escape HTML to prevent XSS
 * @param {string} text The text to escape
 */
const escapeHtml = (text: string): string => {
	const div = document.createElement('div');
	div.textContent = text;
	return div.innerHTML;
};

/**
 * Show error message
 * @param {string} message The error message to display
 */
const showError = (message: string): void => {
	const elements = getElements();
	if (elements.tableBody) {
		elements.tableBody.innerHTML = `
			<tr class="cf7a-error-row">
				<td colspan="5">
					<span class="dashicons dashicons-warning"></span>
					${escapeHtml(message)}
				</td>
			</tr>
		`;
	}
};

/**
 * Update pagination UI
 * @param {WordlistResponse} response The response from the API
 */
const updatePagination = (response: WordlistResponse): void => {
	const elements = getElements();

	if (elements.pageInput) {
		elements.pageInput.value = String(response.page);
	}
	if (elements.totalPagesSpan) {
		elements.totalPagesSpan.textContent = String(response.total_pages);
	}
	if (elements.totalWordsSpan) {
		elements.totalWordsSpan.textContent = String(response.total);
	}

	if (elements.prevBtn) {
		elements.prevBtn.disabled = response.page <= 1;
	}
	if (elements.nextBtn) {
		elements.nextBtn.disabled = response.page >= response.total_pages;
	}
};

/**
 * Attach event listeners to table rows
 */
const attachRowEventListeners = (): void => {
	// Edit buttons
	document.querySelectorAll('.cf7a-edit-word').forEach((btn) => {
		btn.addEventListener('click', (e) => {
			const target = e.currentTarget as HTMLButtonElement;
			openEditModal(
				target.dataset.token || '',
				parseInt(target.dataset.spam || '0', 10),
				parseInt(target.dataset.ham || '0', 10)
			);
		});
	});

	// Delete buttons
	document.querySelectorAll('.cf7a-delete-word').forEach((btn) => {
		btn.addEventListener('click', (e) => {
			const target = e.currentTarget as HTMLButtonElement;
			const token = target.dataset.token || '';
			if (
				// eslint-disable-next-line no-alert
				confirm(
					`Are you sure you want to delete the word "${token}" from the dictionary?`
				)
			) {
				deleteWord(token);
			}
		});
	});
};

/**
 * Open the edit modal
 * @param {string} token     The token to edit
 * @param {number} spamCount The spam count
 * @param {number} hamCount  The ham count
 */
const openEditModal = (
	token: string,
	spamCount: number,
	hamCount: number
): void => {
	const elements = getElements();
	if (!elements.editModal) {
		return;
	}

	elements.editTokenDisplay.textContent = token;
	elements.editTokenValue.value = token;
	elements.editSpamCount.value = String(spamCount);
	elements.editHamCount.value = String(hamCount);
	elements.editModal.style.display = 'flex';
};

/**
 * Close the edit modal
 */
const closeEditModal = (): void => {
	const elements = getElements();
	if (elements.editModal) {
		elements.editModal.style.display = 'none';
	}
};

/**
 * Save word changes
 */
const saveWord = async (): Promise<void> => {
	const elements = getElements();
	const token = elements.editTokenValue.value;
	const spamCount = parseInt(elements.editSpamCount.value, 10);
	const hamCount = parseInt(elements.editHamCount.value, 10);

	try {
		const response = await apiFetch<ApiResponse>({
			path: '/cf7-antispam/v1/update-word',
			method: 'POST',
			data: {
				token,
				count_spam: spamCount,
				count_ham: hamCount,
				nonce: getNonce(),
			},
		});

		if (response.success) {
			closeEditModal();
			fetchWordlist(); // Refresh the list
		} else {
			// eslint-disable-next-line no-alert
			alert(response.message || 'Failed to update word');
		}
	} catch (error) {
		// eslint-disable-next-line no-console
		console.error('Error updating word:', error);
		// eslint-disable-next-line no-alert
		alert('Failed to update word. Please try again.');
	}
};

/**
 * Delete a word from the dictionary
 * @param {string} token The token to delete
 */
const deleteWord = async (token: string): Promise<void> => {
	try {
		const response = await apiFetch<ApiResponse>({
			path: '/cf7-antispam/v1/delete-word',
			method: 'POST',
			data: {
				token,
				nonce: getNonce(),
			},
		});

		if (response.success) {
			fetchWordlist(); // Refresh the list
		} else {
			// eslint-disable-next-line no-alert
			alert(response.message || 'Failed to delete word');
		}
	} catch (error) {
		// eslint-disable-next-line no-console
		console.error('Error deleting word:', error);
		// eslint-disable-next-line no-alert
		alert('Failed to delete word. Please try again.');
	}
};

/**
 * Handle sort click
 * @param {string} orderBy The column to sort by
 */
const handleSort = (orderBy: string): void => {
	if (currentOrderBy === orderBy) {
		// Toggle order if clicking same column
		currentOrder = currentOrder === 'asc' ? 'desc' : 'asc';
	} else {
		// Default to desc for checks/counts, asc for text
		currentOrderBy = orderBy;
		currentOrder = 'desc';
	}

	// Reset to page 1 when sorting changes
	currentPage = 1;

	updateSortIcons();
	fetchWordlist();
};

/**
 * Update sort icons in table headers
 */
const updateSortIcons = (): void => {
	const headers = document.querySelectorAll('.cf7a-sortable');
	headers.forEach((header) => {
		header.classList.remove('sorted-asc', 'sorted-desc');
		if (header.getAttribute('data-sort') === currentOrderBy) {
			header.classList.add(`sorted-${currentOrder}`);
		}
	});
};

/**
 * Initialize sort listeners
 */
const initSortListeners = (): void => {
	const headers = document.querySelectorAll('.cf7a-sortable');
	headers.forEach((header) => {
		header.addEventListener('click', () => {
			const sortKey = header.getAttribute('data-sort');
			if (sortKey) {
				handleSort(sortKey);
			}
		});
	});
};

/**
 * Initialize wordlist management
 */
export const initWordlist = (): void => {
	const elements = getElements();
	if (!elements.container) {
		return;
	}

	// Search
	elements.searchBtn?.addEventListener('click', () => {
		searchQuery = elements.searchInput?.value || '';
		currentPage = 1;
		fetchWordlist();
	});

	elements.searchInput?.addEventListener('keypress', (e) => {
		if (e.key === 'Enter') {
			searchQuery = elements.searchInput?.value || '';
			currentPage = 1;
			fetchWordlist();
		}
	});

	// Type filter
	elements.typeFilterSelect?.addEventListener('change', () => {
		typeFilter = elements.typeFilterSelect?.value || 'all';
		currentPage = 1;
		fetchWordlist();
	});

	// Per page
	elements.perPageSelect?.addEventListener('change', () => {
		perPage = parseInt(elements.perPageSelect?.value || '50', 10);
		currentPage = 1;
		fetchWordlist();
	});

	// Pagination
	elements.prevBtn?.addEventListener('click', () => {
		if (currentPage > 1) {
			currentPage--;
			fetchWordlist();
		}
	});

	elements.nextBtn?.addEventListener('click', () => {
		if (currentPage < totalPages) {
			currentPage++;
			fetchWordlist();
		}
	});

	elements.pageInput?.addEventListener('change', () => {
		const page = parseInt(elements.pageInput?.value || '1', 10);
		if (page >= 1 && page <= totalPages) {
			currentPage = page;
			fetchWordlist();
		}
	});

	// Modal
	elements.saveWordBtn?.addEventListener('click', saveWord);

	document
		.querySelectorAll('.cf7a-modal-close, .cf7a-modal-cancel')
		.forEach((el) => {
			el.addEventListener('click', closeEditModal);
		});

	// Close modal on outside click
	elements.editModal?.addEventListener('click', (e) => {
		if (e.target === elements.editModal) {
			closeEditModal();
		}
	});

	// Initial load
	initSortListeners();
	updateSortIcons();
	fetchWordlist();
};

// Auto-initialize if the wordlist manager element exists
document.addEventListener('DOMContentLoaded', () => {
	if (document.querySelector('.cf7a-wordlist-manager')) {
		initWordlist();
	}
});
