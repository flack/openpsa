<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
$l10n = midcom::get()->i18n->get_l10n('org.openpsa.invoices');

$entries = array();

$grid_id = 'invoices_report_grid';
if ($data['query']->orgOpenpsaObtype !== org_openpsa_reports_query_dba::OBTYPE_REPORT_TEMPORARY) {
    $grid_id .= $data['query']->id;
}

$footer_data = array(
    'number' => $data['l10n']->get('totals'),
    'sum' => 0,
    'vat_sum' => 0
);
$sortname = 'date';
$sortorder = 'asc';
$cancelations = array();

foreach ($data['invoices'] as $invoice) {
    $entry = array();

    $vat_sum = ($invoice->sum / 100) * $invoice->vat;

    $footer_data['sum'] += $invoice->sum;
    $footer_data['vat_sum'] += $vat_sum;

    $number = $invoice->get_label();
    $link_html = "<a href='{$prefix}invoice/{$invoice->guid}/'>" . $number . "</a>";
    $next_marker = false;

    if ($number == "") {
        $number = "n/a";
    }

    $entry['id'] = $invoice->id;

    $entry['index_number'] = $invoice->number;

    if ($data['invoices_url'] && $invoice->id) {
        $entry['number'] = "<a target='_blank' href=\"{$data['invoices_url']}invoice/{$invoice->guid}/\">" . $invoice->get_label() . "</a>";
    } elseif ($invoice->id) {
        $entry['number'] = $invoice->get_label();
    } else {
        $entry['number'] = $invoice->description;
    }

    if ($invoice->{$data['date_field']} > 0) {
        $entry['date'] = strftime('%Y-%m-%d', $invoice->{$data['date_field']});
        $entry['year'] = strftime('%Y', $invoice->{$data['date_field']});
        $entry['month'] = strftime('%B %Y', $invoice->{$data['date_field']});
        $entry['index_month'] = strftime('%Y%m', $invoice->{$data['date_field']});
    } else {
        $entry['date'] = '';
        $entry['year'] = '';
        $entry['month'] = '';
        $entry['index_month'] = '';
    }
    try {
        $customer = org_openpsa_contacts_group_dba::get_cached($invoice->customer);
        $entry['index_customer'] = $customer->official;
        if ($data['invoices_url']) {
            $entry['customer'] = "<a href=\"{$data['invoices_url']}list/customer/all/{$customer->guid}/\" title=\"{$customer->name}: {$customer->official}\">{$customer->official}</a>";
        } else {
            $entry['customer'] = $customer->official;
        }
    } catch (midcom_error $e) {
        $entry['customer'] = '';
        $entry['index_customer'] = '';
    }

    $entry['index_contact'] = '';
    $entry['contact'] = '';

    $entry['index_status'] = $invoice->get_status();
    $entry['status'] = $l10n->get($entry['index_status']);

    if ($entry['index_status'] === 'canceled') {
        $cancelations[] = $invoice->cancelationInvoice;
    }

    try {
        $contact = org_openpsa_contacts_person_dba::get_cached($invoice->customerContact);
        $entry['index_contact'] = $contact->rname;
        $contact_card = org_openpsa_widgets_contact::get($invoice->customerContact);
        $entry['contact'] = $contact_card->show_inline();
    } catch (midcom_error $e) {
    }

    $entry['sum'] = $invoice->sum;

    $entry['index_vat'] = $invoice->vat;
    $entry['vat'] = $invoice->vat . ' %';
    $entry['vat_sum'] = $vat_sum;

    $entries[] = $entry;
}

if (count($cancelations) > 0) {
    foreach ($entries as &$entry) {
        if (in_array($entry['id'], $cancelations)) {
            $entry['index_status'] = 'canceled';
            $entry['status'] = $l10n->get('canceled');
            $entry['number'] .= ' (' . $l10n->get('cancelation invoice') . ')';
        }
    }
}

if ($data['date_field'] == 'date') {
    $data['date_field'] = 'invoice date';
}

$grid = new org_openpsa_widgets_grid($grid_id, 'local');

$grid->set_column('number', $l10n->get('invoice number'), 'width: 120', 'string')
    ->set_column('status', $data['l10n']->get('invoice status'), '', 'string')
    ->set_column('date', $l10n->get($data['date_field']), 'width: 80, fixed: true, formatter: "date", align: "right"')
    ->set_column('month', '', 'hidden: true', 'number')
    ->set_column('year', '', 'hidden: true')
    ->set_column('customer', $l10n->get('customer'), 'width: 100', 'string')
    ->set_column('contact', $l10n->get('customer contact'), 'width: 100', 'string')
    ->set_column('sum', $l10n->get('sum excluding vat'), 'width: 90, fixed: true, template: "number", summaryType:"sum"')
    ->set_column('vat', $l10n->get('vat'), 'width: 40, fixed: true, align: "right"', 'number')
    ->set_column('vat_sum', $l10n->get('vat sum'), 'width: 70, fixed: true, template: "number", summaryType:"sum"');

$grid->set_option('loadonce', true)
    ->set_option('grouping', true)
    ->set_option('groupingView', array(
             'groupField' => array('status'),
             'groupColumnShow' => array(false),
             'groupText' => array('<strong>{0}</strong> ({1})'),
             'groupOrder' => array('asc'),
             'groupSummary' => array(true),
             'showSummaryOnHide' => true
         ))
    ->set_option('sortname', $sortname)
    ->set_option('sortorder', $sortorder);

$grid->set_footer_data($footer_data);

$host_prefix = midcom::get()->get_host_prefix();

$filename = preg_replace('/[^a-z0-9-]/i', '_', $data['title'] . '_' . date('Y_m_d'));
?>
<div class="grid-controls">
<?php
echo ' ' . midcom::get()->i18n->get_string('group by', 'org.openpsa.core') . ': ';
echo '<select id="chgrouping_' . $grid_id . '">';
echo '<option value="status">' . $data['l10n']->get('invoice status') . "</option>\n";
echo '<option value="customer">' . $l10n->get('customer') . "</option>\n";
echo '<option value="contact">' . $l10n->get('customer contact') . "</option>\n";
echo '<option value="year" data-hidden="true">' . $data['l10n']->get('year') . "</option>\n";
echo '<option value="month" data-hidden="true">' . $data['l10n']->get('month') . "</option>\n";
echo '<option value="clear">' . midcom::get()->i18n->get_string('no grouping', 'org.openpsa.core') . "</option>\n";
echo '</select>';
?>
<form id="&(grid_id);_export" class="tab_escape" method="post" action="&(host_prefix);midcom-exec-org.openpsa.core/csv_export.php">
<input id="&(grid_id);_csvdata" type="hidden" value="" name="org_openpsa_export_csv_data" />
<input type="hidden" value="&(filename);.csv" name="org_openpsa_export_csv_filename" />
<input class="button" type="submit" value="<?php echo midcom::get()->i18n->get_string('download as CSV', 'org.openpsa.core'); ?>" />
</form>
</div>

<div class="report org_openpsa_invoices full-width fill-height">
<?php
    echo $grid->render($entries);
?>
</div>

<script type="text/javascript">

org_openpsa_export_csv.add({
      id: '&(grid_id);',
      fields: {
          index_number: '<?php echo $l10n->get('invoice number'); ?>',
          status: '<?php echo $l10n->get('status'); ?>',
          date: '<?php echo $data['l10n_midcom']->get('date'); ?>',
          index_customer: '<?php echo $l10n->get('customer'); ?>',
          index_contact: '<?php echo $l10n->get('customer contact'); ?>',
          sum: '<?php echo $l10n->get('sum excluding vat'); ?>',
          vat: '<?php echo $l10n->get('vat'); ?>',
          vat_sum: '<?php echo $l10n->get('vat sum'); ?>'
        }
});
org_openpsa_grid_helper.bind_grouping_switch('&(grid_id);');

</script>
