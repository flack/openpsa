function init_chart(grid_id) {

    const ctx = document.getElementById('chart-' + grid_id);
    var chart,
        chart_shown = false,
        chart_toggle = $('#' + grid_id + '-chart');

    function render_chart() {
        let group_data = $('#' + grid_id).jqGrid('getGridParam', 'groupingView'),
            chart_labels = [],
            chart_data = [],
            datasets = [];

        group_data.groups.forEach(function(group) {
            chart_labels.push(group.value.replace(/<\/?[^>]+(>|$)/g, ""));
            chart_data.push(group.summary[0].v);
        });

        var label = '';
        $('#' + grid_id).jqGrid('getGridParam', 'colModel').forEach(function(col, index) {
            if (col.name == 'sum') {
                label = $('#' + grid_id).jqGrid('getGridParam', 'colNames')[index];
            }
        });

        datasets.push({
            label: label,
            type: 'bar',
            data: chart_data,
            borderWidth: 1
        });

        if (group_data.groupField[0] == 'month' || group_data.groupField[0] == 'year') {
            let averages = [],
                periods = Math.min(Math.floor(chart_data.length / 6), 11);
            chart_data.forEach(function(value, index) {
                if (index >= periods) {
                    let sum = value;
                    for (let i = 1; i <= periods; i++) {
                        sum += chart_data[index - i];
                    }
                    averages.push(sum / (periods + 1));
                } else {
                    averages.push(null);
                }
            });
            datasets.push({
                label: 'Avg (' + (periods + 1) + ')',
                type: 'line',
                tension: .4,
                pointStyle: false,
                data: averages
            });
        }

        chart = new Chart(ctx, {
            data: {
                labels: chart_labels,
                datasets: datasets
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    $('#chgrouping_' + grid_id).on('change', function() {
        if (chart) {
            chart.destroy();
        }
        if ($('#' + grid_id).jqGrid('getGridParam', 'grouping')) {
            $(ctx).show();
            render_chart();
            chart_toggle.prop('disabled', false);
        } else {
            $(ctx).hide();
            chart_toggle.prop('disabled', true);
            $(window).trigger('resize');
        }
    }).trigger('change');

    chart_toggle.on('click', function() {
        chart_shown = !chart_shown && !chart_toggle.prop('disabled');

        if (chart_shown) {
            $('body').addClass('chart-shown');
        } else {
            $('body').removeClass('chart-shown');
        }
        $(window).trigger('resize');
    }).trigger('click');
}