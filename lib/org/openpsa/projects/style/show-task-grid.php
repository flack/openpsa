<?php
$grid_id = $data['view_identifier'] . '_tasks_grid';

$task_classes = 'ui-ellipsis';
$grouping = 'project';
if ($data['show_status_controls']) {
    $grouping = 'status';
    $task_classes = 'multiline';
} elseif ($data['view_identifier'] == 'agreement') {
    $grouping = 'manager';
}

$footer_data = ['task' => $data['l10n']->get('totals')];

$grid = $data['provider']->get_grid($grid_id);
$grid->set_option('loadonce', true)
->set_option('grouping', true)
->set_option('groupingView', [
    'groupField' => [$grouping],
    'groupColumnShow' => [false],
    'groupText' => ['<strong>{0}</strong> ({1})'],
    'groupOrder' => ['asc'],
    'groupSummary' => [true],
    'showSummaryOnHide' => true
]);

if ($data['show_status_controls']) {
    $grid->set_column('status_control', '', 'width: 16, fixed: true, sortable: false');
}
$grid->set_column('task', $data['l10n']->get('task'), 'width: 110, classes: "' . $task_classes . '"', 'string');
if (!$data['is_single_project']) {
    $grid->set_column('project', $data['l10n']->get('project'), 'width: 80, classes: "ui-ellipsis"', 'string');
}
$grid->set_column('priority', $data['l10n']->get('priority'), 'width: 30, align: "center", fixed: true', 'integer');

if ($data['view_identifier'] != 'my_tasks') {
    if ($data['show_customer']) {
        $grid->set_column('customer', $data['l10n']->get('customer'), 'width: 55, classes: "ui-ellipsis"', 'string');
    }
    if ($data['view_identifier'] == 'subtasks') {
        $grid->set_column('status', $data['l10n']->get('status'), 'width: 100, classes: "ui-ellipsis"', 'float');
    }
    $grid->set_column('manager', $data['l10n']->get('manager'), 'width: 60, classes: "ui-ellipsis"', 'string')
        ->set_column('start', $data['l10n']->get('start'), 'width: 80, formatter: "date", align: "right", fixed: true')
        ->set_column('end', $data['l10n']->get('end'), 'width: 80, formatter: "date", align: "right", fixed: true');
} else {
    $grid->set_column('end', $data['l10n']->get('deadline'), 'width: 80, formatter: "date", align: "right", fixed: true')
        ->set_column('status', $data['l10n']->get('status'), 'width: 100, classes: "ui-ellipsis"', 'float')
        ->set_option('caption', $data['l10n']->get($data['view_identifier']));
}
$grid->set_column('planned_hours', $data['l10n']->get('planned hours'), 'width: 55, template: "number", summaryType:"sum", fixed: true')
    ->set_column('reported_hours', $data['l10n']->get('reported'), 'width: 55, template: "number", summaryType:"sum", fixed: true');
if ($data['is_single_project']) {
    $grid->set_column('invoiced_hours', $data['l10n']->get('invoiced'), 'width: 55, template: "number", summaryType:"sum", fixed: true')
        ->set_column('status', $data['l10n']->get('status'), 'width: 100, classes: "ui-ellipsis"', 'float');
    if ($data['view_identifier'] !== 'subtasks') {
        $grid->set_option('caption', $data['l10n']->get($data['view_identifier']));
    }
}
$grid->set_footer_data($footer_data);
?>
<div class="org_openpsa_projects <?php echo $data['view_identifier']; ?> full-width fill-height">

<?php $grid->render(); ?>

</div>

<script type="text/javascript">
org_openpsa_grid_helper.bind_grouping_switch('&(grid_id);');

org_openpsa_grid_footer.set_field('&(grid_id);', 'planned_hours', 'sum');
org_openpsa_grid_footer.set_field('&(grid_id);', 'reported_hours', 'sum');
org_openpsa_grid_footer.set_field('&(grid_id);', 'invoiced_hours', 'sum');
org_openpsa_grid_footer.set_field('&(grid_id);', 'reported', 'sum');
</script>
