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
	
	if ($('.cartabandonment').length) {

		var chartData = $('#cart-abandonment-chart').data('chart'),
			chartDataArray = chartData.split(','),
			chart = document.getElementById('cart-abandonment-chart');
		
		var options = {
			cutoutPercentage: (chartData == '0,0') ? 0 : 50,
			tooltips: {
				enabled: false
			}
		}
		
		console.log(chartDataArray);

		var data = {
		    labels: false,
		    datasets: [
		        {
		            data: (chartData == '0,0') ? [0,1] : chartDataArray,
		            backgroundColor: [
		                "#6CBC15",
		                "#EBEBEB"
		            ],
		            hoverBackgroundColor: [
		                "#6CBC15",
		                "#EBEBEB"
		            ]
		        }
			]
		};	
		
		var myDoughnutChart = new Chart(
			chart, {
		    	type: 'doughnut',
				data: data,
				options: options
			}
		);
	
	}

});