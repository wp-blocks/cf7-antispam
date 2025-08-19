/* global spamChartData */
import Chart from 'chart.js/auto';

function spamCharts() {
	if (typeof spamChartData !== 'undefined') {
		const cf7aCharts: { [key: string]: Chart | null } = {
			lineChart: null,
			pieChart: null,
		};

		const lineConfig: { type: string; data: any; options: any } = {
			type: 'line',
			data: spamChartData.lineData,
			options: {
				responsive: true,
				plugins: {
					legend: { display: false },
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

		const PieConfig: { type: string; data: any; options: any } = {
			type: 'pie',
			data: spamChartData.pieData,
			options: {
				responsive: true,
				plugins: {
					legend: { display: false },
				},
			},
		};

		const antispamDashWidget = document.getElementById(
			'cf7a-widget'
		) as HTMLDivElement | null;

		if (antispamDashWidget !== null) {
			const lineChartWrapper = antispamDashWidget.querySelector(
				'#line-chart'
			) as HTMLCanvasElement | null;
			if (lineChartWrapper) {
				cf7aCharts.lineChart = new Chart(lineChartWrapper, lineConfig);
			}

			const pieChartWrapper = antispamDashWidget.querySelector(
				'#pie-chart'
			) as HTMLCanvasElement | null;
			if (pieChartWrapper) {
				cf7aCharts.pieChart = new Chart(pieChartWrapper, PieConfig);
			}
		}

		return cf7aCharts;
	}
}

if (document.readyState === 'complete') {
	spamCharts();
} else {
	document.addEventListener('DOMContentLoaded', spamCharts);
}
