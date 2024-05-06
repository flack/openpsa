<?php
$time = $data['week_start'];
$date_columns = [];

$data['grid']
    ->set_column('task', $data['l10n']->get('task'), separate_index: 'string')
    ->set_column('person', $data['l10n']->get('person'), separate_index: 'string');

$formatter = $data['l10n']->get_formatter();
while ($time < $data['week_end']) {
    $date_identifier = date('Y-m-d', $time);
    $data['grid']->set_column($date_identifier, $formatter->customdate($time, 'E'), 'fixed: true, headerTitle: "' . $formatter->date($time) . '" , width: 40, summaryType: calculate_subtotal, align: "right"', 'float');
    // Hop to next day
    $date_columns[] = $date_identifier;
    $time += 3600 * 24;
}
$data['grid']->set_option('footerrow', true)
    ->set_option('grouping', true)
    ->set_option('groupingView', [
        'groupField' => ['task'],
        'groupColumnShow' => [false],
        'groupText' => ['<strong>{0}</strong> ({1})'],
        'groupOrder' => ['asc'],
        'groupSummary' => [true],
        'showSummaryOnHide' => true
]);
?>
<h1>&(data['view_title']);</h1>
<?php
    midcom_show_style('hours_filters');
?>

<script type="text/javascript">

function calculate_subtotal(val, name, record)
{
    var sum = parseFloat(val||0) + parseFloat((record['index_' + name]||0));
    return sum || '';
}
</script>
<div class="full-width">
<?php
    $data['grid']->render($data['rows']);
    $grid_id = $data['grid']->get_identifier();
?>
</div>
<script type="text/javascript">
midcom_grid_helper.bind_grouping_switch('&(grid_id);');

var grid = $("#&(grid_id);"),
    date_columns = <?php echo json_encode($date_columns); ?>,
    totals = {},
    day_total;

date_columns.forEach(function(name) {
    day_total = 0;
    grid.jqGrid('getCol', 'index_' + name).forEach(function(value) {
        day_total += parseFloat(value || 0);
    });
    totals[name] = Math.round(day_total * 100) / 100;
});
grid.jqGrid('footerData', 'set', totals);

$('body')
   .on('dialogsaved, dialogdeleted', '#midcom-dialog', function() {
       location.href = location.href;
   });
</script>
