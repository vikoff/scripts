
var StdCharts = {

    /**
     * @param selector
     * @param {object} data like [ {date: 1409047200, line_1: '25', line_2: 4}, ...]
     * @param {object} [options]
     * @param {object} [rawOptions]
     */
    drawTableColumns: function(selector, data, options, rawOptions)
    {
        var opt = $.extend({
            title: '',
            subtitle: null,
            seriesTitles: {},
            dateAsString: false,
            zooming: true,
            rightLegend: false,
            sort: false,
            animation: false,
            height: null
        }, options || {});

        rawOptions = rawOptions || {};
        var seriesIndex = {};
        var row, col, len, dateTs, val, i;

        var useDateFormat = data[0] && data[0].date;

        // date-chart
        if (useDateFormat) {

            for (row = 0, len = data.length; row < len; row++) {
                for (col in data[row]) {
                    dateTs = opt.dateAsString ? (Date.parse(data[row].date + ' GMT')) : data[row].date * 1000;
                    if (col != 'date') {
                        val = parseFloat(data[row][col]) || 0;
                        if (!seriesIndex[col]) {
                            seriesIndex[col] = {items: [], count: 0, label: col};
                        }
                        seriesIndex[col].items.push([dateTs, val]);
                        seriesIndex[col].count += val;
                    }
                }
            }

            rawOptions = $.extend(true, {
                xAxis: {type: 'datetime'}
            }, rawOptions);
        }
        // non-date chart
        else {
            var categories = {};
            var firstKey = Object.keys(data[0] || [])[0];

            for (row = 0, len = data.length; row < len; row++) {
                for (col in data[row]) {
                    categories[ data[row][firstKey] ] = 1;
                    if (col != firstKey) {
                        val = parseFloat(data[row][col]) || 0;
                        if (!seriesIndex[col]) {
                            seriesIndex[col] = {items: [], count: 0, label: col};
                        }
                        seriesIndex[col].items.push(val);
                        seriesIndex[col].count += val;
                    }
                }
            }

            rawOptions = $.extend(true, {
                xAxis: {categories: Object.keys(categories)}
            }, rawOptions);
        }

        var series = [];
        for (i in seriesIndex)
            series.push({
                name: opt.seriesTitles[i] || seriesIndex[i].label,
                data: seriesIndex[i].items,
                _count: seriesIndex[i].count});

        if (opt.sort) {
            series.sort(function(a, b){ return b._count - a._count; });
        }

        var chartParams = {
            chart: {
                height: opt.height
            },
            plotOptions: {
                series: {shadow: true, marker: {enabled: false}, stickyTracking: false, animation: opt.animation}
            },
            title: { text: opt.title },
            subtitle: { text: opt.subtitle },
            xAxis: {offset: 20, gridLineWidth: 1, gridLineColor: '#DDD'},
            yAxis: { gridLineColor: '#DDD', reversedStacks: 0, min: 0 },
            tooltip: {shared: true, crosshairs: [true, false]},
            series: series
        };

        if (opt.zooming) {
            $.extend(true, chartParams, {chart: {
                zoomType: 'x',
                panning: true,
                panKey: 'shift'
            }});
        }

        if (opt.rightLegend) {
            $.extend(true, chartParams, {legend: {
                layout: 'vertical',
                backgroundColor: '#FFFFFF',
                align: 'right',
                verticalAlign: 'middle'
            }});
        }

        if (rawOptions) {
            $.extend(true, chartParams, rawOptions);
        }

        return $(selector).highcharts(chartParams);
    }
};
