<?php
$grid_id = 'hours_week';
$time = $data['week_start'];
$date_columns = array();

$grid = new org_openpsa_widgets_grid($grid_id, 'local');

$grid->set_column('task', $data['l10n']->get('task'), '', 'string')
->set_column('person', $data['l10n']->get('person'), '', 'string');

$date_tooltips = array();
$i = 6;
while ($time < $data['week_end'])
{
    $date_identifier = date('Y-m-d', $time);
    $grid->set_column($date_identifier, strftime('%a', $time), 'fixed: true, width: 40, summaryType: calculate_subtotal, align: "right"', 'float');
    // Hop to next day
    $date_columns[] = $date_identifier;
    $date_tooltips[$i] = date($data['l10n_midcom']->get('short date'), $time);
    $time = $time + 3600 * 24;
    $i += 2;
}
$grid->set_option('footerrow', true)
->set_option('grouping', true)
->set_option('groupingView', array
(
    'groupField' => array('task'),
    'groupColumnShow' => array(false),
    'groupText' => array('<strong>{0}</strong> ({1})'),
    'groupOrder' => array('asc'),
    'groupSummary' => array(true),
    'showSummaryOnHide' => true
));
?>

<div class="area">
    <?php
    $data['qf']->render();
    echo midcom::get('i18n')->get_string('group by', 'org.openpsa.core') . ': ';
    echo '<select id="chgrouping_' . $grid_id . '">';
    echo '<option value="task">' . $data['l10n']->get('task') . "</option>\n";
    echo '<option value="person">' . $data['l10n']->get('person') . "</option>\n";
    echo '<option value="clear">' . midcom::get('i18n')->get_string('no grouping', 'org.openpsa.core') . "</option>\n";
    echo '</select>';
    ?>
</div>
<script type="text/javascript">

function calculate_subtotal(val, name, record)
{
    var sum = parseFloat(val||0) + parseFloat((record[name]||0));
    return sum || '';
}
</script>
<div class="full-width">
<?php
    $grid->render($data['rows']);
?>
</div>
<script type="text/javascript">
org_openpsa_grid_helper.bind_grouping_switch('&(grid_id);');

var grid = $("#&(grid_id);"),
date_columns = <?php echo json_encode($date_columns); ?>,
date_tooltips = <?php echo json_encode($date_tooltips); ?>,
totals = {},
day_total;
$.each(date_columns, function(index, name)
{
    day_total = 0;
    $.each(grid.jqGrid('getCol', name), function(i, value)
    {
        day_total += parseFloat(value || 0);
    });
    totals[name] = day_total;
    day_total = 0;
});
grid.jqGrid('footerData', 'set', totals);

$.each(date_tooltips, function(index, value)
{
    org_openpsa_grid_helper.set_tooltip("&(grid_id);", parseInt(index), value);
});

</script>

