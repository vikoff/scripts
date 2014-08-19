
<h2>Hihit chart</h2>

<ol class="breadcrumb">
	<li><a href="<?= href(''); ?>">Home</a></li>
	<li><a href="<?= href('hihit'); ?>">Hihit</a></li>
	<li class="active">Hihit chart</li>
</ol>

<div class="chart-1"></div>
<div class="chart-2"></div>

<script type="text/javascript">

	var Hihit = {
		draw: function(data)
		{
			var j, date;
			var allSeries = [];
			var ratioSeries = {name: 'ratio', data: []};
			for (var i = 0, len = data.length; i < len; i++) {
				date = (new Date(data[i][0])).getTime();
				for (j = 1; j < data[i].length; j++) {
					allSeries[j-1] = allSeries[j-1] || {name: j, data: []};
					allSeries[j-1].data.push([date, parseInt(data[i][j])]);
				}
				ratioSeries.data.push([date, parseFloat((data[i][2] / data[i][1]).toFixed(2))]);
			}
			$('.chart-1').highcharts({
				title: {text: 'hihit'},
				xAxis: {type: 'datetime', offset: 20, gridLineWidth: 1, gridLineColor: '#DDD' },
				yAxis: {type: 'linear', min: 0, gridLineColor: '#DDD' },
				plotOptions: {series: {allowPointSelect: true, marker: {enabled: false}, stickyTracking: false}},
				tooltip: { shared: true, crosshairs: [true, false] },
				series: allSeries
			});
			$('.chart-2').highcharts({
				title: {text: 'hihit ratio'},
				xAxis: {type: 'datetime', offset: 20, gridLineWidth: 1, gridLineColor: '#DDD' },
				yAxis: {type: 'linear', min: 0, gridLineColor: '#DDD' },
				plotOptions: {series: {allowPointSelect: true, marker: {enabled: false}, stickyTracking: false}},
				tooltip: { shared: true, crosshairs: [true, false] },
				series: [ratioSeries]
			});
		}
	};
	$(function(){
		Hihit.draw(<?= $this->jsonData; ?>);
	});
</script>