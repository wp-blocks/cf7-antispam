/**
 * Vanilla JS logic to filter the blocklist table items in real-time.
 */

if (document.body.classList.contains('cf7-antispam-admin')) {
	const searchInput = document.getElementById(
		'cf7a_blocklist_search'
	) as HTMLInputElement | null;

	if (searchInput) {
		searchInput.addEventListener('input', (e) => {
			const target = e.target as HTMLInputElement;
			const query = target.value.toLowerCase().trim();

			const grids = document.querySelectorAll('.cf7a-blocklist-grid');

			let totalAutomatedVisible = 0;
			let totalWarningsVisible = 0;

			grids.forEach((grid) => {
				const cards = grid.querySelectorAll('.ban-card');
				let visibleCount = 0;

				cards.forEach((card) => {
					const el = card as HTMLElement;
					const searchData = el.getAttribute('data-search') || '';

					if (query === '' || searchData.includes(query)) {
						el.style.display = 'block';
						visibleCount++;
					} else {
						el.style.display = 'none';
					}
				});

				// Check if this grid is an automated score group
				const prevElement = grid.previousElementSibling as HTMLElement;
				if (
					prevElement &&
					prevElement.classList.contains('cf7a-score-group')
				) {
					const heading = prevElement;
					const countSpan = heading.querySelector(
						'.cf7a-score-count'
					) as HTMLElement | null;
					const totalCountStr =
						heading.getAttribute('data-total') || '0';

					const isWarning = grid.closest('.cf7a-warnings-section');
					if (isWarning) {
						totalWarningsVisible += visibleCount;
					} else {
						totalAutomatedVisible += visibleCount;
					}

					if (visibleCount === 0) {
						heading.style.display = 'none';
						(grid as HTMLElement).style.display = 'none';
					} else {
						heading.style.display = 'block';
						(grid as HTMLElement).style.display = 'grid';

						// Update group count text
						if (countSpan) {
							if (query === '') {
								countSpan.innerText = `(count ${totalCountStr})`;
							} else {
								countSpan.innerText = `(count ${visibleCount}/${totalCountStr})`;
							}
						}
					}
				} else {
					// Handle Allowlist and Manual sections
					const sectionHeader = grid.parentElement?.querySelector(
						'.cf7a-section-header'
					) as HTMLElement | null;
					if (sectionHeader) {
						const countBadge = sectionHeader.querySelector(
							'.cf7a-count-badge'
						) as HTMLElement | null;
						if (countBadge) {
							if (
								!countBadge.hasAttribute('data-original-count')
							) {
								countBadge.setAttribute(
									'data-original-count',
									countBadge.innerText
								);
							}
							const originalCount = countBadge.getAttribute(
								'data-original-count'
							);

							if (query === '') {
								countBadge.innerText = `${originalCount}`;
							} else {
								countBadge.innerText = `${visibleCount}/${originalCount}`;
							}
						}
					}

					if (visibleCount === 0) {
						(grid as HTMLElement).style.display = 'none';
					} else {
						(grid as HTMLElement).style.display = 'grid';
					}
				}
			});

			// Update the overall Automated Banned IPs badge
			const automatedSection = document.querySelector(
				'.cf7a-automated-blacklist-section'
			);
			if (automatedSection) {
				const headerBadge = automatedSection.querySelector(
					'.cf7a-section-header .cf7a-count-badge'
				) as HTMLElement | null;
				if (headerBadge) {
					if (!headerBadge.hasAttribute('data-original-count')) {
						headerBadge.setAttribute(
							'data-original-count',
							headerBadge.innerText
						);
					}
					const originalCount = headerBadge.getAttribute(
						'data-original-count'
					);

					if (query === '') {
						headerBadge.innerText = `${originalCount}`;
					} else {
						headerBadge.innerText = `${totalAutomatedVisible}/${originalCount}`;
					}
				}
			}

			// Update the overall Warnings badge
			const warningsSection = document.querySelector(
				'.cf7a-warnings-section'
			);
			if (warningsSection) {
				const headerBadge = warningsSection.querySelector(
					'.cf7a-section-header .cf7a-count-badge'
				) as HTMLElement | null;
				if (headerBadge) {
					if (!headerBadge.hasAttribute('data-original-count')) {
						headerBadge.setAttribute(
							'data-original-count',
							headerBadge.innerText
						);
					}
					const originalCount = headerBadge.getAttribute(
						'data-original-count'
					);

					if (query === '') {
						headerBadge.innerText = `${originalCount}`;
					} else {
						headerBadge.innerText = `${totalWarningsVisible}/${originalCount}`;
					}
				}
			}
		});
	}
}
