/* global spamChartData */
import Chart from 'chart.js/auto';

function spamCharts() {
	if ( typeof spamChartData !== 'undefined' ) {
		const cf7aCharts = {};

		const lineConfig = {
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

		const PieConfig = {
			type: 'pie',
			data: spamChartData.pieData,
			options: {
				responsive: true,
				plugins: {
					legend: { display: false },
				},
			},
		};

		cf7aCharts.lineChart = new Chart(
			document.getElementById( 'line-chart' ),
			lineConfig
		);

		cf7aCharts.pieChart = new Chart(
			document.getElementById( 'pie-chart' ),
			PieConfig
		);

		return cf7aCharts;
	}
}

window.onload = spamCharts();
