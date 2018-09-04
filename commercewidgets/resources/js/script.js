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
		$('.cartabandonment').each(function(){

			var chartData = $(this).find($('[data-chart]')).data('chart'),
				chartDataArray = chartData.split(','),
				//chart = document.getElementById('cart-abandonment-chart');
				chart = $(this).find('.chart');
				
			var options = {
				cutoutPercentage: (chartData == '0,0') ? 0 : 50,
				tooltips: {
					enabled: false
				}
			}

			var data = {
				labels: false,
				datasets: [
					{
						data: (chartData == '0,0') ? [0,1] : chartDataArray,
						backgroundColor: [
							"#6CBC15",
							"#EA9305"
						],
						hoverBackgroundColor: [
							"#6CBC15",
							"#EA9305"
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
		});
	
	}

});
