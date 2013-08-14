<?php
$queryTypesData = array();
foreach ($this->queryTypes as $type => $cnt)
	$queryTypesData[] = array($type, (int)$cnt);

//echo '<pre>'; var_dump($this->queryTypes); die; // DEBUG
?>

<script src="http://code.highcharts.com/highcharts.js"></script>
<script src="http://code.highcharts.com/modules/exporting.js"></script>

<div id="query-types" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
<div id="selects-stat" style="min-width: 310px; height: 400px; margin: 0 auto"></div>

<script type="text/javascript">

	$(function () {

		$('#query-types').highcharts({
			title: {text: 'Типы SQL запросов'},
			plotOptions: {
				pie: {
					allowPointSelect: true,
					cursor: 'pointer'
				}
			},
			series: [{
				type: 'pie',
				name: 'Запросов',
				data: <?= json_encode($queryTypesData); ?>
			}]
		});

		$('#selects-stat').highcharts({
			plotOptions: {series: { animation: false }},
			title: { text: 'Количество SELECT запросов в минуту' },
			xAxis: { categories: <?= json_encode($this->selectsStat['dates']); ?>, offset: 20 },
			yAxis: { title: { text: 'Количество SELECT запросов' }, min: 0 },
			tooltip: {formatter: function() {
				return this.x + '<br />запросов: ' + this.y;
			}},
			series: [{name: 'Количество SELECT запросов', data: <?= json_encode($this->selectsStat['values']); ?>}]
		});

	});



</script>