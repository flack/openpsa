<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
$entries = array();

$grid_id = $data['table_class'] . '_invoices_grid';

$footer_data = array
(
    'number' => $data['l10n']->get('totals'),
    'sum' => 0,
    'vat_sum' => 0
);
$sortname = 'index_number';
$sortorder = 'asc';

if ($data['table_class'] == 'paid')
{
    $sortorder = 'desc';
}
if ($data['table_class'] != 'unsent')
{
    $sortname = 'date';
}
else
{
    $sortname = 'index_number';
}

foreach ($data['invoices'] as $invoice)
{
    $entry = array();

    $vat_sum = ($invoice->sum / 100) * $invoice->vat;

    $footer_data['sum'] += $invoice->sum;
    $footer_data['vat_sum'] += $vat_sum;

    $number = $invoice->get_label();
    $link_html = "<a href='{$prefix}invoice/{$invoice->guid}/'>" . $number . "</a>";
    $next_marker = false;

    if ($number == "")
    {
        $number = "n/a";
    }

    $entry['id'] = $invoice->id;

    $entry['index_number'] = $invoice->number;

    if ($data['invoices_url'] && $invoice->id)
    {
        $entry['number'] = "<a target='_blank' href=\"{$data['invoices_url']}invoice/{$invoice->guid}/\">" . $invoice->get_label() . "</a>";
    }
    else if ($invoice->id)
    {
        $entry['number'] = $invoice->get_label();
    }
    else
    {
        $entry['number'] = $invoice->description;
    }

    $entry['owner'] = '';
    if (!empty($invoice->owner))
    {
        try
        {
            $owner = org_openpsa_contacts_person_dba::get_cached($invoice->owner);
            $entry['owner'] = $owner->name;
        }
        catch (midcom_error $e)
        {
            $e->log();
        }
    }

    if ($invoice->{$data['date_field']} > 0)
    {
        $entry['date'] = strftime('%Y-%m-%d', $invoice->{$data['date_field']});
    }
    else
    {
        $entry['date'] = '';
    }
    try
    {
        $customer = org_openpsa_contacts_group_dba::get_cached($invoice->customer);
        $entry['index_customer'] = $customer->official;
        if ($data['invoices_url'])
        {
            $entry['customer'] = "<a href=\"{$data['invoices_url']}list/customer/all/{$customer->guid}/\" title=\"{$customer->name}: {$customer->official}\">{$customer->official}</a>";
        }
        else
        {
            $entry['customer'] = $customer->official;
        }
    }
    catch (midcom_error $e)
    {
        $entry['customer'] = '';
        $entry['index_customer'] = '';
    }

    $entry['index_contact'] = '';
    $entry['contact'] = '';

    try
    {
        $contact = org_openpsa_contacts_person_dba::get_cached($invoice->customerContact);
        $entry['index_contact'] = $contact->rname;
        $contact_card = org_openpsa_widgets_contact::get($invoice->customerContact);
        $entry['contact'] = $contact_card->show_inline();
    }
    catch (midcom_error $e){}

    $entry['sum'] = $invoice->sum;

    $entry['index_vat'] = $invoice->vat;
    $entry['vat'] = $invoice->vat . ' %';
    $entry['vat_sum'] = $vat_sum;

    $entries[] = $entry;
}

if ($data['date_field'] == 'date')
{
    $data['date_field'] = 'invoice date';
}

$l10n = midcom::get('i18n')->get_l10n('org.openpsa.invoices');
$grid = new org_openpsa_widgets_grid($grid_id, 'local');

$grid->set_column('number', $l10n->get('invoice number'), 'width: 80', 'string')
    ->set_column('owner', '')
    ->set_column('date', $l10n->get($data['date_field']), 'width: 80, fixed: true, formatter: "date", align: "center"')
    ->set_column('customer', $l10n->get('customer'), 'width: 100', 'string')
    ->set_column('contact', $l10n->get('customer contact'), 'width: 100', 'string')
    ->set_column('sum', $l10n->get('customer contact'), 'width: 90, fixed: true, sorttype: "number", formatter: "number", align: "right", summaryType:"sum"')
    ->set_column('vat', $l10n->get('vat'), 'width: 40, fixed: true, align: "right"', 'number')
    ->set_column('vat_sum', $l10n->get('vat sum'), 'width: 70, fixed: true, sorttype: "number", formatter: "number", align: "right", summaryType:"sum"');

$grid->set_option('loadonce', true)
    ->set_option('caption', $data['table_title'])
    ->set_option('grouping', true)
    ->set_option('groupingView', array
         (
             'groupField' => array('owner'),
             'groupColumnShow' => array(false),
             'groupText' => array('<strong>{0}</strong> ({1})'),
             'groupOrder' => array('asc'),
             'groupSummary' => array(true),
             'showSummaryOnHide' => true
         ))
    ->set_option('sortname', $sortname)
    ->set_option('sortorder', $sortorder);

$grid->set_footer_data($footer_data);
?>

<div class="report &(data['table_class']); org_openpsa_invoices full-width">
<?php
    echo $grid->render($entries);
?>
</div>

<?php
$host_prefix = midcom::get()->get_host_prefix();

$filename = $data['l10n']->get($data['table_title']);
$filename .= '_' . date('Y_m_d');
$filename = preg_replace('/[^a-z0-9-]/i', '_', $filename);
?>

<form id="&(grid_id);_export" class="tab_escape" method="post" action="&(host_prefix);midcom-exec-org.openpsa.core/csv_export.php">
<input id="&(grid_id);_csvdata" type="hidden" value="" name="org_openpsa_export_csv_data" />
<input type="hidden" value="&(filename);.csv" name="org_openpsa_export_csv_filename" />
<input class="button" type="submit" value="<?php echo midcom::get('i18n')->get_string('download as CSV', 'org.openpsa.core'); ?>" />
</form>

<script type="text/javascript">

org_openpsa_export_csv.add({
      id: '&(grid_id);',
      fields: {
          index_number: '<?php echo midcom::get('i18n')->get_string('invoice number', 'org.openpsa.invoices'); ?>',
          date: '<?php echo $data['l10n_midcom']->get('date'); ?>',
          owner: '<?php echo $data['l10n_midcom']->get('owner'); ?>',
          index_customer: '<?php echo midcom::get('i18n')->get_string('customer', 'org.openpsa.invoices'); ?>',
          index_contact: '<?php echo midcom::get('i18n')->get_string('customer contact', 'org.openpsa.invoices'); ?>',
          sum: '<?php echo midcom::get('i18n')->get_string('sum excluding vat', 'org.openpsa.invoices'); ?>',
          vat: '<?php echo midcom::get('i18n')->get_string('vat', 'org.openpsa.invoices'); ?>',
          vat_sum: '<?php echo midcom::get('i18n')->get_string('vat sum', 'org.openpsa.invoices'); ?>'
        }
});

</script>
