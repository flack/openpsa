<?php
$projects_l10n = midcom::get()->i18n->get_l10n('org.openpsa.projects');
$action_target_url = $data['router']->generate('hours_task_action');
$export_url = $data['router']->generate('csv', ['type' => 'hour_report']);
$invoice_url = org_openpsa_core_siteconfig::get_instance()->get_node_full_url('org.openpsa.invoices');
$footer_data = ['hours' => 0];
$categories = [$data['l10n']->get('uninvoiceable'), $data['l10n']->get('invoiceable'), $data['l10n']->get('invoiced')];
$entries = [];
$workflow = new midcom\workflow\datamanager;
$filename = $data['mode'];

foreach ($data['hours'] as $report) {
    $entry = [];

    $entry['id'] = $report->id;
    $entry['date'] = strftime('%Y-%m-%d', $report->date);

    if ($data['mode'] != 'task') {
        $task = org_openpsa_projects_task_dba::get_cached($report->task);
        $link = $data['router']->generate('list_hours_task', ['guid' => $task->guid]);
        $entry['task'] = "<a href=\"{$link}\">" . $task->get_label() . "</a>";
        $entry['index_task'] = $task->get_label();
    }

    if ($data['mode'] != 'invoice') {
        if ($report->invoice) {
            $entry['category'] = 2;
        } elseif ($report->invoiceable) {
            $entry['category'] = 1;
        } else {
            $entry['category'] = 0;
        }
    } else {
        if ($report->is_approved()) {
            $approved_text = $projects_l10n->get('approved');
            $icon = 'check';
        } else {
            $approved_text = $projects_l10n->get('not approved');
            $icon = 'times';
        }
        $entry['approved'] =  "<i class='fa fa-{$icon}' title='{$approved_text}'></i>";
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

$data['grid']->set_column('date', $data['l10n']->get('date'), "width: 80, align: 'right', formatter: 'date', fixed: true")
    ->set_column('reporter', $data['l10n']->get('person'), "width: 80, classes: 'ui-ellipsis'", 'string');

if ($data['mode'] != 'task') {
    $data['grid']->set_column('task', $data['l10n']->get('task'), "classes: 'ui-ellipsis'", 'string');
}
$data['grid']->set_column('description', $data['l10n']->get('description'), "width: 250, classes: 'multiline'", 'string');
$data['grid']->set_column('hours', $data['l10n']->get('hours'), "width: 50, align: 'right', formatter: 'number', summaryType: 'sum'");
if ($data['mode'] == 'invoice') {
    $data['grid']->set_column('approved', $projects_l10n->get('approved'), "width: 20, align: 'center', fixed: true");
} else {
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
if ($data['mode'] == 'full') {
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

<form method="post" class="tab_escape" id="csv_&(grid_id);" action="&(export_url);?filename=hours_&(filename);.csv">
    <input type="hidden" name="order[date]" value="ASC" />
    <input class="button" type="submit" value="<?= midcom::get()->i18n->get_string('download as CSV', 'org.openpsa.core'); ?>" />
</form>
</div>

<script type="text/javascript">
org_openpsa_grid_helper.bind_grouping_switch('&(grid_id);');

org_openpsa_batch_processing.initialize({
    id: '&(grid_id);',
    options: <?php echo json_encode($data['action_options']); ?>
});

$('#csv_&(grid_id);').on('submit', function() {
    $('#&(grid_id);').jqGrid('getRowData').forEach(function(row) {
        $('#csv_&(grid_id);').append($('<input type="hidden" name="ids[]" value="' + row.id + '" />'));
    });
});
</script>
