/* global spamChartData */
/**
 * External dependencies
 */
/**
 * External dependencies
 */
import Chart from 'chart.js/auto';
import { getIso } from 'isotolanguage';

function spamCharts(dataParam?: any) {
	const data =
		dataParam ||
		(typeof spamChartData !== 'undefined' ? spamChartData : null);

	if (data) {
		const cf7aCharts: { [key: string]: Chart | null } = {
			lineChart: null,
			pieChart: null,
			countryChart: null,
		};

		// Construct datasets based on available data
		const lineDatasets = [];
		if (data.ham && data.ham.length) {
			lineDatasets.push({
				label: 'Ham',
				backgroundColor: 'rgb(38,137,218)',
				borderColor: 'rgb(34,113,177)',
				tension: 0.25,
				data: data.ham,
			});
		}
		if (data.spam && data.spam.length) {
			lineDatasets.push({
				label: 'Spam',
				backgroundColor: 'rgb(255,4,0)',
				borderColor: 'rgb(248,49,47)',
				tension: 0.25,
				data: data.spam,
			});
		}
		if (data.blockedIps && data.blockedIps.length) {
			lineDatasets.push({
				label: 'Blocked IPs',
				backgroundColor: 'rgb(245,158,11)', // orange
				borderColor: 'rgb(217,119,6)',
				tension: 0.25,
				data: data.blockedIps,
			});
		}
		if (data.blockedComments && data.blockedComments.length) {
			lineDatasets.push({
				label: 'Blocked Comments',
				backgroundColor: 'rgb(168,85,247)', // purple
				borderColor: 'rgb(147,51,234)',
				tension: 0.25,
				data: data.blockedComments,
			});
		}

		const lineConfig: { type: string; data: any; options: any } = {
			type: 'line',
			data: {
				labels: data.dates || [],
				datasets: lineDatasets,
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: { display: true }, // enabled legend since we have 4 lines now
				},
				scales: {
					y: {
						ticks: {
							min: 0,
							precision: 0,
						},
					},
				},
			},
		};

		const pieDataValues = [];
		const pieLabels = [];
		const pieColors = [];
		let hasCountryData = false;

		const palette = [
			'#2563EB',
			'#0D9488',
			'#4F46E5',
			'#F97316',
			'#8B5CF6',
			'#EC4899',
			'#14B8A6',
			'#6366F1',
			'#F43F5E',
			'#10B981',
			'#0EA5E9',
			'#84CC16',
		];
		let countryIndex = 0;

		if (
			typeof cf7a_admin_settings !== 'undefined' &&
			cf7a_admin_settings.pieChartData
		) {
			const pieData = cf7a_admin_settings.pieChartData;
			let sortedData = Object.keys(pieData).map((key) => ({
				label: key,
				count: pieData[key],
			}));

			// Group into Top 10 + Other for countries
			if (
				sortedData.length > 10 &&
				sortedData[0].label !== 'Ham' &&
				sortedData[0].label !== 'Spam'
			) {
				sortedData.sort((a, b) => b.count - a.count);
				const top10 = sortedData.slice(0, 10);
				const otherCount = sortedData
					.slice(10)
					.reduce((sum, item) => sum + item.count, 0);
				top10.push({ label: 'Other', count: otherCount });
				sortedData = top10;
			}

			if (sortedData.length > 0) {
				for (let i = 0; i < sortedData.length; i++) {
					const label = sortedData[i].label;
					let mappedLabel = label;

					if (label === 'Ham') {
						pieColors.push('rgb(38,137,218)');
					} else if (label === 'Spam') {
						pieColors.push('rgb(255,4,0)');
					} else if (label === 'Other') {
						mappedLabel = 'Other';
						pieColors.push('#9CA3AF'); // Gray color for Other
						hasCountryData = true;
					} else {
						const info = getIso(label.toUpperCase()) as any;
						const mappedObj = Array.isArray(info) ? info[0] : info;
						if (mappedObj && mappedObj.name) {
							mappedLabel = mappedObj.name;
						}

						pieColors.push(palette[countryIndex % palette.length]);
						countryIndex++;
						hasCountryData = true;
					}

					pieLabels.push(mappedLabel);
					pieDataValues.push(sortedData[i].count);
				}
			}
		}

		const PieConfig: { type: string; data: any; options: any } = {
			type: 'pie',
			data: {
				labels: pieLabels,
				datasets: [
					{
						data: pieDataValues,
						backgroundColor: pieColors,
					},
				],
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: {
						display: true,
						position: hasCountryData ? 'right' : 'top',
					},
					title: hasCountryData
						? { display: true, text: 'Spammers by Country' }
						: undefined,
				},
			},
		};

		const chartsWrapper =
			document.getElementById('cf7a-widget-async') || // new async wrapper for widget
			document.getElementById('antispam-charts-async') || // new async wrapper for dash
			document.getElementById('cf7a-widget') ||
			(document.querySelector(
				'.antispam-charts-container'
			) as HTMLDivElement | null);

		if (chartsWrapper !== null) {
			const lineChartWrapper = chartsWrapper.querySelector(
				'#line-chart'
			) as HTMLCanvasElement | null;
			if (lineChartWrapper) {
				cf7aCharts.lineChart = new Chart(lineChartWrapper, lineConfig);
			}

			const pieChartWrapper = chartsWrapper.querySelector(
				'#pie-chart'
			) as HTMLCanvasElement | null;
			const countryPieChartWrapper = chartsWrapper.querySelector(
				'#country-pie-chart'
			) as HTMLCanvasElement | null;

			const targetWrapper = countryPieChartWrapper || pieChartWrapper;
			if (targetWrapper && pieDataValues.length > 0) {
				cf7aCharts.pieChart = new Chart(targetWrapper, PieConfig);
			}
		}

		return cf7aCharts;
	}
}

// Expose globally so fetch callback can initialize charts when data arrives asynchronously
window.cf7aInitCharts = spamCharts;

if (document.readyState === 'complete') {
	spamCharts();
} else {
	document.addEventListener('DOMContentLoaded', () => spamCharts());
}
