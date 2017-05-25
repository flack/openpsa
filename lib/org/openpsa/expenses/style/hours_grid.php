<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
$invoice_url = org_openpsa_core_siteconfig::get_instance()->get_node_full_url('org.openpsa.invoices');
$footer_data = array('hours' => 0);
$categories = array($data['l10n']->get('uninvoiceable'), $data['l10n']->get('invoiceable'), $data['l10n']->get('invoiced'));
$entries = array();
$workflow = new midcom\workflow\datamanager2;
foreach ($data['hours'] as $report) {
    $entry = array();

    $entry['id'] = $report->id;
    $entry['date'] = strftime('%Y-%m-%d', $report->date);

    if ($data['mode'] != 'simple') {
        $task = org_openpsa_projects_task_dba::get_cached($report->task);
        $entry['task'] = "<a href=\"{$prefix}hours/task/{$task->guid}/\">" . $task->get_label() . "</a>";
        $entry['index_task'] = $task->get_label();
    }

    if ($report->invoice) {
        $entry['category'] = 2;
    } elseif ($report->invoiceable) {
        $entry['category'] = 1;
    } else {
        $entry['category'] = 0;
    }

    $entry['index_description'] = $report->description;
    $entry['description'] = '<a' . $workflow->render_attributes() . ' href="' . $prefix . 'hours/edit/' . $report->guid . '/">' . $report->get_description() . '</a>';

    try {
        $reporter = midcom_db_person::get_cached($report->person);
        $reporter_card = new org_openpsa_widgets_contact($reporter);
        $entry['index_reporter'] = $reporter->rname;
        $entry['reporter'] = $reporter_card->show_inline();
    } catch (midcom_error $e) {
        $e->log();
        $entry['index_reporter'] = '';
        $entry['reporter'] = '';
    }

    $entry['hours'] = $report->hours;
    $footer_data['hours'] += $report->hours;

    $entry['index_invoice'] = '';
    $entry['invoice'] = '';
    if ($report->invoice) {
        $invoice = org_openpsa_invoices_invoice_dba::get_cached($report->invoice);
        $entry['index_invoice'] = $invoice->number;
        $entry['invoice'] = $invoice->get_label();
        if ($invoice_url) {
            $entry['invoice'] = '<a href="' . $invoice_url . 'invoice/' . $invoice->guid . '">' . $entry['invoice'] . '</a>';
        }
    }

    $entries[] = $entry;
}

$data['grid']->set_column('date', $data['l10n']->get('date'), "width: 80, align: 'right', formatter: 'date', fixed: true")
    ->set_column('reporter', $data['l10n']->get('person'), "width: 80, classes: 'ui-ellipsis'", 'string');

if ($data['mode'] != 'simple') {
    $data['grid']->set_column('task', $data['l10n']->get('task'), "classes: 'ui-ellipsis'", 'string');
}
$data['grid']->set_column('description', $data['l10n']->get('description'), "width: 250, classes: 'multiline'", 'string');
$data['grid']->set_column('hours', $data['l10n']->get('hours'), "width: 50, align: 'right', formatter: 'number', summaryType: 'sum'");
$data['grid']->set_select_column('category', $data['l10n']->get('category'), "width: 50, hidden: true", $categories);
$data['grid']->set_column('invoice', $data['l10n']->get('invoice'), "width: 50, align: 'center'", 'integer');

$data['grid']->set_option('loadonce', true)
    ->set_option('sortname', 'date')
    ->set_option('sortorder', 'desc')
    ->set_option('multiselect', true)
    ->set_option('grouping', true)
    ->set_option('groupingView', array(
        'groupField' => array('category'),
        'groupColumnShow' => array(false),
        'groupText' => array('<strong>{0}</strong> ({1})'),
        'groupOrder' => array('asc'),
        'groupSummary' => array(true),
        'showSummaryOnHide' => true
    ));

$data['grid']->set_footer_data($footer_data);
?>
<div class="org_openpsa_expenses batch-processing full-width crop-height">

<?php
$data['grid']->render($entries);
$grid_id = $data['grid']->get_identifier();
?>

<form id="form_&(grid_id);" method="post" action="<?php echo $data['action_target_url']; ?>">
<input type="hidden" name="relocate_url" value="<?php echo $_SERVER['REQUEST_URI']; ?>" />
</form>
</div>

<script type="text/javascript">
org_openpsa_grid_helper.bind_grouping_switch('&(grid_id);');

org_openpsa_batch_processing.initialize(
{
    id: '&(grid_id);',
    options: <?php echo json_encode($data['action_options']); ?>
});
</script>
