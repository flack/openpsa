<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
$reporters = $data['reporters'];
$reports = $data['reports'];
$invoice_url = org_openpsa_core_siteconfig::get_instance()->get_node_full_url('org.openpsa.invoices');

$class = $data['status'];
if ($data['status'] === 'invoiced') {
    $class .= ' good';
}
if ($data['status'] === 'invoiceable') {
    $class .= ' normal';
}

$entries = array();
$grid_id = $data['status'] . '_hours_grid';
$workflow = new midcom\workflow\datamanager2;
foreach ($reports['reports'] as $report) {
    $entry = array();

    $entry['id'] = $report->id;
    $entry['date'] = strftime('%Y-%m-%d', $report->date);

    if ($data['mode'] != 'simple') {
        $task = org_openpsa_projects_task_dba::get_cached($report->task);
        $entry['task'] = "<a href=\"{$prefix}hours/task/{$task->guid}/\">" . $task->get_label() . "</a>";
        $entry['index_task'] = $task->get_label();
    }

    $entry['index_description'] = $report->description;
    $entry['description'] = '<a' . $workflow->render_attributes() . ' href="' . $prefix . 'hours/edit/' . $report->guid . '/">' . $report->get_description() . '</a>';

    $entry['index_reporter'] = $reporters[$report->person]['rname'];
    $entry['reporter'] = $reporters[$report->person]['card'];

    $entry['hours'] = $report->hours;

    if ($data['status'] === 'invoiced') {
        $invoice = org_openpsa_invoices_invoice_dba::get_cached($report->invoice);
        $entry['index_invoice'] = $invoice->number;
        $entry['invoice'] = $invoice->get_label();
        if ($invoice_url) {
            $entry['invoice'] = '<a href="' . $invoice_url . 'invoice/' . $invoice->guid . '">' . $entry['invoice'] . '</a>';
        }
    }

    $entries[] = $entry;
}
$grid = new org_openpsa_widgets_grid($grid_id, 'local');

$grid->set_column('date', $data['l10n']->get('date'), "width: 80, align: 'right', formatter: 'date', fixed: true")
    ->set_column('reporter', $data['l10n']->get('person'), "width: 80, classes: 'ui-ellipsis'", 'string');

if ($data['mode'] != 'simple') {
    $grid->set_column('task', $data['l10n']->get('task'), "classes: 'ui-ellipsis'", 'string');
}
$grid->set_column('description', $data['l10n']->get('description'), "width: 250, classes: 'ui-ellipsis'", 'string');
if ($data['status'] === 'invoiced') {
    $grid->set_column('invoice', $data['l10n']->get('invoice'), "width: 60, align: 'center'", 'integer');
}
$grid->set_column('hours', $data['l10n']->get('hours'), "width: 50, align: 'right', formatter: 'number'");

$grid->set_option('loadonce', true)
    ->set_option('caption', $data['subheading'])
    ->set_option('sortname', 'date')
    ->set_option('sortorder', 'desc')
    ->set_option('multiselect', true);

$grid->add_pager();

$footer_data = array(
    'hours' => $reports['hours']
);

$grid->set_footer_data($footer_data);
?>
<div class="org_openpsa_expenses <?php echo $class ?> batch-processing full-width crop-height" style="margin-top: 1em">

<?php $grid->render($entries); ?>

<form id="form_&(grid_id);" method="post" action="<?php echo $data['action_target_url']; ?>">
<input type="hidden" name="relocate_url" value="<?php echo $_SERVER['REQUEST_URI']; ?>" />
</form>
</div>

<script type="text/javascript">

org_openpsa_batch_processing.initialize(
{
    id: '&(grid_id);',
    options: <?php echo json_encode($data['action_options']); ?>
});
</script>
