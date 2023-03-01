function init_chart(grid_id) {

    const ctx = document.getElementById('chart-' + grid_id);
    var chart;

    function render_grid() {
        let group_data = $('#' + grid_id).jqGrid('getGridParam', 'groupingView'),
        chart_labels = [],
        chart_data = [];

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

        chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chart_labels,
            datasets: [{
                label: label,
                data: chart_data,
                borderWidth: 1
            }]
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
            render_grid();
        }
    }).trigger('change');
}