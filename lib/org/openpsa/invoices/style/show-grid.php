<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
$entries = array();

$classes = $data['list_type'];

if ($data['list_type'] == 'overdue')
{
    $classes .= ' bad';
}
else if ($data['list_type'] == 'paid')
{
    $classes .= ' good';
}

$grid_id = $data['list_type'] . '_invoices_grid';

if (array_key_exists('deliverable', $data))
{
    $grid_id = 'd_' . $data['deliverable']->id . $grid_id;
}


foreach ($data['invoices'] as $invoice)
{
    $entry = array();
    $customer_card = org_openpsa_contactwidget::get($invoice->customerContact);
    $number = $invoice->get_label();
    $link_html = "<a href='{$prefix}invoice/{$invoice->guid}/'>" . $number . "</a>";
    $next_marker = false;

    if ($number == "")
    {
        $number = "n/a";
    }

    if ($invoice->sent == 0)
    {
        $next_marker = 'sent';
    }
    else if (!$invoice->paid)
    {
        $next_marker = 'paid';
    }

    $entry['id'] = $invoice->id;
    $entry['index_number'] = $number;
    $entry['number'] = $link_html;

    if ($data['show_customer'])
    {
        $customer = org_openpsa_contacts_group_dba::get_cached($invoice->customer);

        if ($customer)
        {
            if ($data['invoices_url'])
            {
                $entry['customer'] = "<a href=\"{$data['invoices_url']}list/customer/all/{$customer->guid}/\" title=\"{$customer->name}: {$customer->official}\">{$customer->official}</a>";
            }
            else
            {
                $entry['customer'] = $customer->official;
            }
        }
    }

    $entry['contact'] = $customer_card->show_inline();
    $entry['index_sum'] = $invoice->sum;
    $entry['sum'] = '<span title="' . $data['l10n']->get('sum including vat') . ': ' . org_openpsa_helpers::format_number((($invoice->sum / 100) * $invoice->vat) + $invoice->sum) . '">' . org_openpsa_helpers::format_number($invoice->sum) . '</span>';

    $entry['index_due'] = $invoice->due;
    $entry['due'] = strftime('%x', $invoice->due);

    if ($data['list_type'] != 'paid')
    {
        $entry['action'] = '';
        if (   $_MIDCOM->auth->can_do('midgard:update', $invoice)
            && $next_marker)
        {
            $next_marker_url = $prefix . "invoice/mark_" . $next_marker . "/" . $invoice->guid . "/";
            $next_marker_url .= "?org_openpsa_invoices_redirect=" . urlencode($_SERVER['REQUEST_URI']);
            $entry['action'] .= '<form method="post" action="' . $next_marker_url . '"';
            $entry['action'] .= '<button type="submit" name="midcom_helper_toolbar_submit" class="yes">';
            $entry['action'] .= $data['l10n']->get('mark ' . $next_marker);
            $entry['action'] .= '</button></form>';
        }
    }
    else
    {
        $entry['action'] = strftime('%x', $invoice->paid);
    }
    $entries[] = $entry;
}
echo '<script type="text/javascript">//<![CDATA[';
echo "\nvar " . $grid_id . '_entries = ' . json_encode($entries);
echo "\n//]]></script>";

$footer_data = array();

foreach ($data['totals'] as $label => $sum)
{
    if (!$sum)
    {
        continue;
    }
    $footer_data = array
    (
        'contact' => $data['l10n']->get($label),
        'sum' => org_openpsa_helpers::format_number($sum)
    );
}
?>

<div class="org_openpsa_invoices <?php echo $classes ?> full-width">

<table id="&(grid_id);"></table>
<div id="p_&(grid_id);"></div>

</div>

<script type="text/javascript">
jQuery("#&(grid_id);").jqGrid({
      datatype: "local",
      data: &(grid_id);_entries,
      colNames: ['id', 'index_number', <?php
                 echo '"' . $data['l10n']->get('invoice') . '",';
                 echo '"' . $data['l10n']->get('customer contact') . '",';
                 if ($data['show_customer'])
                 {
                     echo '"' . $data['l10n']->get('customer') . '",';
                 }
                 echo '"index_sum", "' . $data['l10n']->get('amount') . '",';
                 echo '"index_due", "' . $data['l10n']->get('due') . '",';

                 if ($data['list_type'] != 'paid')
                 {
                     echo '"' . $data['l10n']->get('next action') . '"';
                 }
                 else
                 {
                     echo '"' . $data['l10n']->get('paid date') . '"';
                 }
      ?>],
      colModel:[
          {name:'id', index:'id', hidden:true, key:true},
          {name:'index_number',index:'index_number', hidden:true},
          {name:'number', index: 'index_number', width: 80, align: 'center', fixed: true, classes: 'title'},
          {name:'contact', index: 'contact'},
          <?php if ($data['show_customer'])
          { ?>
              {name:'customer', index: 'customer'},
          <?php } ?>
          {name:'index_sum', index: 'index_sum', sorttype: "number", hidden:true},
          {name:'sum', index: 'index_sum', width: 80, fixed: true, align: 'right'},
          {name:'index_due', index: 'index_due', sorttype: "integer", hidden:true },
          {name:'due', index: 'index_due', width: 80, align: 'center'},
          {name:'action', index: 'action', width: 80, align: 'center'}
      ],
      loadonce: true,
      caption: "&(data['list_label']);",
      footerrow: true
});

jQuery("#&(grid_id);").jqGrid('footerData', 'set', <?php echo json_encode($footer_data); ?>);

</script>
