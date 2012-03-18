<?php
$grid_id = $data['view_identifier'] . '_tasks_grid';

$task_classes = 'ui-ellipsis';
$grouping = 'project';
if (   $data['view_identifier'] == 'my_tasks'
    || $data['view_identifier'] == 'project_tasks')
{
    $grouping = 'status';
    $task_classes = 'multiline';
}

$footer_data = array('task' => $data['l10n']->get('totals'));

$grid = $data['provider']->get_grid($grid_id);
$grid->set_option('loadonce', true)
->set_option('grouping', true)
->set_option('groupingView', array
(
    'groupField' => array($grouping),
    'groupColumnShow' => array(false),
    'groupText' => array('<strong>{0}</strong> ({1})'),
    'groupOrder' => array('asc'),
    'groupSummary' => array(true),
    'showSummaryOnHide' => true
));

if (   $data['view_identifier'] == 'my_tasks'
    || $data['view_identifier'] == 'project_tasks')
{
    $grid->set_column('status_control', '', 'width: 16, fixed: true, sortable: false');
}
$grid->set_column('task', $data['l10n']->get('task'), 'width: 110, classes: "' . $task_classes . '"', 'string');
if ($data['view_identifier'] != 'project_tasks')
{
    $grid->set_column('project', $data['l10n']->get('project'), 'width: 80, classes: "ui-ellipsis"', 'string');
}
$grid->set_column('priority', $data['l10n']->get('priority'), 'width: 18, align: "center", fixed: true', 'integer');

if ($data['view_identifier'] != 'my_tasks')
{
    if ($data['view_identifier'] != 'project_tasks')
    {
        $grid->set_column('customer', $data['l10n']->get('customer'), 'width: 55, classes: "ui-ellipsis"', 'string');
    }
    $grid->set_column('manager', $data['l10n']->get('manager'), 'width: 70, classes: "ui-ellipsis"', 'string')
        ->set_column('start', $data['l10n']->get('start'), 'width: 75, formatter: "date", align: "center", fixed: true')
        ->set_column('end', $data['l10n']->get('end'), 'width: 75, formatter: "date", align: "center", fixed: true');
}
else
{
    $grid->set_column('end', $data['l10n']->get('deadline'), 'width: 75, formatter: "date", align: "center", fixed: true')
        ->set_column('status', $data['l10n']->get('status'), 'width: 100, classes: "ui-ellipsis"', 'float')
        ->set_option('caption', $data['l10n']->get($data['view_identifier']));
}
if ($data['view_identifier'] != 'project_tasks')
{
    $grid->set_column('reported', $data['l10n']->get('hours'), 'width: 50, align: "right", summaryType:"sum"', 'float');
}
else
{
    $grid->set_column('planned_hours', $data['l10n']->get('planned hours'), 'width: 40, align: "right", sorttype: "number", formatter: "number", summaryType:"sum", fixed: true')
        ->set_column('invoiceable_hours', $data['l10n']->get('invoiceable'), 'width: 40, align: "right", sorttype: "number", formatter: "number", summaryType:"sum", fixed: true')
        ->set_column('reported_hours', $data['l10n']->get('reported'), 'width: 40, align: "right", sorttype: "number", formatter: "number", summaryType:"sum", fixed: true')
        ->set_column('status', $data['l10n']->get('status'), 'width: 100, classes: "ui-ellipsis"', 'float')
        ->set_option('caption', $data['l10n']->get($data['view_identifier']));
}

$grid->set_footer_data($footer_data);
?>
<div class="org_openpsa_projects <?php echo $data['view_identifier']; ?> full-width fill-height">

<?php $grid->render(); ?>

</div>

<script type="text/javascript">
var &(grid_id);_grouping = "&(grouping);";

jQuery("#chgrouping_&(grid_id);").change(function()
{
    var selection = $(this).val();
    if (selection)
    {
        if (selection == "clear")
        {
            jQuery("#&(grid_id);").jqGrid('groupingRemove', true);
            &(grid_id);_grouping = '';
        }
        else
        {
            &(grid_id);_grouping = selection;
            jQuery("#&(grid_id);").jqGrid('groupingGroupBy', selection);
        }
	jQuery(window).trigger('resize');
    }
});
org_openpsa_grid_footer.set_field('&(grid_id);', 'planned_hours', 'sum');
org_openpsa_grid_footer.set_field('&(grid_id);', 'invoiceable_hours', 'sum');
org_openpsa_grid_footer.set_field('&(grid_id);', 'reported_hours', 'sum');
org_openpsa_grid_footer.set_field('&(grid_id);', 'reported', 'sum');
</script>
