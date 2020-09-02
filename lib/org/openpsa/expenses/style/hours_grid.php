<?php
$action_target_url = $data['router']->generate('hours_task_action');
$invoice_url = org_openpsa_core_siteconfig::get_instance()->get_node_full_url('org.openpsa.invoices');
$footer_data = ['hours' => 0];
$categories = [$data['l10n']->get('uninvoiceable'), $data['l10n']->get('invoiceable'), $data['l10n']->get('invoiced')];
$entries = [];
$workflow = new midcom\workflow\datamanager;
$filename = $data['mode'];

foreach ($data['hours'] as $report) {
    $entry = [
        'id' => $report->id,
        'date' => strftime('%Y-%m-%d', $report->date)
    ];

    if ($data['mode'] != 'task') {
        $task = org_openpsa_projects_task_dba::get_cached($report->task);
        $link = $data['router']->generate('list_hours_task', ['guid' => $task->guid]);
        $entry['task'] = "<a href=\"{$link}\">" . $task->get_label() . "</a>";
        $entry['index_task'] = $task->get_label();
    }

    if ($data['mode'] != 'invoice') {
        if (!$report->invoiceable) {
            $entry['category'] = 0;
        } elseif ($report->invoice) {
            $entry['category'] = 2;
        } else {
            $entry['category'] = 1;
        }
    } else {
        $entry['invoiceable'] = $report->invoiceable;
    }

    $entry['index_description'] = $report->description;
    $entry['description'] = '<a' . $workflow->render_attributes() . ' href="' . $data['router']->generate('hours_edit', ['guid' => $report->guid]) . '">' . $report->get_description() . '</a>';

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
        if ($filename == 'invoice') {
            $filename .= '_' . $invoice->number;
        }
        $entry['index_invoice'] = $invoice->number;
        $entry['invoice'] = $invoice->get_label();
        if ($invoice_url) {
            $entry['invoice'] = '<a href="' . $invoice_url . 'invoice/' . $invoice->guid . '">' . $entry['invoice'] . '</a>';
        }
    }

    $entries[] = $entry;
}

$export_fields = [
    'date' => $data['l10n']->get('date'),
    'index_reporter' => $data['l10n']->get('person')
];

$data['grid']->set_column('date', $data['l10n']->get('date'), "width: 80, align: 'right', formatter: 'date', fixed: true")
    ->set_column('reporter', $data['l10n']->get('person'), "width: 80, classes: 'ui-ellipsis'", 'string');

if ($data['mode'] != 'task') {
    $data['grid']->set_column('task', $data['l10n']->get('task'), "classes: 'ui-ellipsis'", 'string');
    $export_fields['task'] = $data['l10n']->get('task');
}
$export_fields['description'] = $data['l10n']->get('description');
$export_fields['hours'] = $data['l10n']->get('hours');
$data['grid']->set_column('description', $data['l10n']->get('description'), "width: 250, classes: 'multiline'", 'string');
$data['grid']->set_column('hours', $data['l10n']->get('hours'), "width: 50, align: 'right', formatter: 'number', summaryType: 'sum'");
if ($data['mode'] == 'invoice') {
    $export_fields['invoiceable'] = $data['l10n']->get('invoiceable');
    $data['grid']->set_column('invoiceable', $data['l10n']->get('invoiceable'), "width: 20, align: 'center', formatter: 'checkbox', fixed: true");
} else {
    $export_fields['category'] = $data['l10n']->get('category');
    $export_fields['invoice'] = $data['l10n']->get('invoice');
    $data['grid']->set_select_column('category', $data['l10n']->get('category'), "width: 50, hidden: true", $categories);
    $data['grid']->set_column('invoice', $data['l10n']->get('invoice'), "width: 50, align: 'center'", 'integer');
}
$data['grid']->set_option('loadonce', true)
    ->set_option('sortname', 'date')
    ->set_option('sortorder', 'desc')
    ->set_option('multiselect', true)
    ->set_option('grouping', true)
    ->set_option('groupingView', [
        'groupField' => [($data['mode'] == 'invoice') ? 'task' : 'category'],
        'groupColumnShow' => [false],
        'groupText' => ['<strong>{0}</strong> ({1})'],
        'groupOrder' => ['asc'],
        'groupSummary' => [true],
        'showSummaryOnHide' => true
    ]);

$data['grid']->set_footer_data($footer_data);
?>
<h1>&(data['view_title']);</h1>
<?php
if (in_array($data['mode'], ['full', 'project'])) {
    midcom_show_style('hours_filters');
}
?>

<div class="org_openpsa_expenses batch-processing full-width crop-height">

<?php
$data['grid']->render($entries);
$grid_id = $data['grid']->get_identifier();
?>

<form id="form_&(grid_id);" method="post" action="<?php echo $action_target_url; ?>">
	<input type="hidden" name="relocate_url" value="<?php echo $_SERVER['REQUEST_URI']; ?>" />
</form>

<button id="&(grid_id);_export">
   <i class="fa fa-download"></i>
   <?php echo midcom::get()->i18n->get_string('download as CSV', 'org.openpsa.core'); ?>
</button>

</div>

<script type="text/javascript">
midcom_grid_batch_processing.initialize({
    id: '&(grid_id);',
    options: <?php echo json_encode($data['action_options']); ?>,
    submit: "<?php echo $data['l10n_midcom']->get('save') ?>"
});

midcom_grid_csv.add({
    id: '&(grid_id);',
    fields: <?php echo json_encode($export_fields); ?>,
    filename: '&(filename);'
});
midcom_grid_helper.bind_grouping_switch('&(grid_id);');

$('body').on('dialogdeleted', '[data-dialog="delete"]', function(e, message) {
    $.midcom_services_uimessage_add(message);

    var row_id = $('#&(grid_id); [data-dialog="dialog"][href$="' + $(this).data('guid') + '/"]')
        .closest('tr').attr('id');

    $('#&(grid_id);').jqGrid('delRowData', row_id);
    $('#&(grid_id);').trigger('reloadGrid');
});
</script>
