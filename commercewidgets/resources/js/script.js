/**
 * Commerce Widgets
 *
 * @author    Jason Mayo
 * @twitter   @madebymayo
 * @package   Commerce Widgets
 *
 */
 
$(function() {
	
	/* Chart - Doghnut */

	var chartData = $('#cart-abandonment-chart').data('chart');
	var chartDataArray = chartData.split(',');

	var chart = document.getElementById('cart-abandonment-chart');
	
	var options = {
		cutoutPercentage: 50,
		tooltips: {
			enabled: false
		}
	}

	var data = {
	    labels: false,
	    datasets: [
	        {
	            data: chartDataArray,
	            backgroundColor: [
	                "#FF6384",
	                "#36A2EB"
	            ],
	            hoverBackgroundColor: [
	                "#FF6384",
	                "#36A2EB"
	            ]
	        }
		]
	};
	
	var myDoughnutChart = new Chart(chart, {
	    type: 'doughnut',
	    data: data,
	    options: options
	});

});