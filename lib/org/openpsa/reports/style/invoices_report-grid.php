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
        $entry['number'] = "<a class='target_blank' href=\"{$data['invoices_url']}invoice/{$invoice->guid}/\">" . $invoice->get_label() . "</a>";
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
echo '<script type="text/javascript">//<![CDATA[';
echo "\nvar " . $grid_id . '_entries = ' . json_encode($entries);
echo "\n//]]></script>";
?>

<div class="report &(data['table_class']); org_openpsa_invoices full-width">

<table id="&(grid_id);"></table>
<div id="p_&(grid_id);"></div>

</div>

<script type="text/javascript">
jQuery("#&(grid_id);").jqGrid({
      datatype: "local",
      data: &(grid_id);_entries,
      colNames: ['id', 'index_number', <?php
                 echo '"' . midcom::get('i18n')->get_string('invoice number', 'org.openpsa.invoices') . '", "owner",';
                 echo '"' . midcom::get('i18n')->get_string($data['date_field'], 'org.openpsa.invoices') . '",';
                 echo '"index_customer", "' . midcom::get('i18n')->get_string('customer', 'org.openpsa.invoices') . '",';
                 echo '"index_contact", "' . midcom::get('i18n')->get_string('customer contact', 'org.openpsa.invoices') . '",';
                 echo '"' . midcom::get('i18n')->get_string('sum excluding vat', 'org.openpsa.invoices') . '",';
                 echo '"index_vat", "' . midcom::get('i18n')->get_string('vat', 'org.openpsa.invoices') . '",';
                 echo '"' . midcom::get('i18n')->get_string('vat sum', 'org.openpsa.invoices') . '"';
      ?>],
      colModel:[
          {name:'id', index:'id', hidden:true, key:true},
          {name:'index_number',index:'index_number', hidden:true},
          {name:'number', index: 'index_number'},
          {name:'owner', index: 'owner'},
          {name:'date', index: 'date', width: 80, fixed: true, formatter: 'date', align: 'center'},
          {name:'index_customer', index: 'index_customer', hidden:true },
          {name:'customer', index: 'index_customer', width: 100},
          {name:'index_contact', index: 'index_contact', hidden:true },
          {name:'contact', index: 'index_contact', width: 100},
          {name:'sum', index: 'sum', width: 90, fixed: true, sorttype: "number", formatter: "number", align: 'right', summaryType:'sum'},
          {name:'index_vat', index: 'index_vat', sorttype: "number", hidden:true },
          {name:'vat', index: 'index_vat', width: 40, fixed: true, align: 'right'},
          {name:'vat_sum', index: 'vat_sum', width: 70, fixed: true, sorttype: "number", formatter: "number", align: 'right', summaryType:'sum'}
      ],
      rowNum: <?php echo sizeof($entries); ?>,
      loadonce: true,
      caption: "&(data['table_title']);",
      footerrow: true,
      grouping: true,
      groupingView: {
          groupField: ['owner'],
          groupColumnShow: [false],
          groupText : ['<strong>{0}</strong> ({1})'],
          groupOrder: ['asc'],
          groupSummary : [true],
          showSummaryOnHide: true
       },
	  sortname: '&(sortname);',
	  sortorder: '&(sortorder);'
});

jQuery("#&(grid_id);").jqGrid('footerData', 'set', <?php echo json_encode($footer_data); ?>);
</script>

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
