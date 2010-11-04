<?php
$view = $data['object_view'];
$invoice = $data['object'];
$customer = org_openpsa_contacts_group_dba::get_cached($invoice->customer);

$siteconfig = org_openpsa_core_siteconfig::get_instance();
$projects_url = $siteconfig->get_node_full_url('org.openpsa.projects');
$contacts_url = $siteconfig->get_node_full_url('org.openpsa.contacts');
?>
    <div class="sidebar">
        <?php

        if ($invoice->customerContact)
        {
            echo "<h2>" . $data['l10n']->get('customer contact') . "</h2>\n";
            $contact = org_openpsa_contactwidget::get($invoice->customerContact);
            echo $contact->show();
        }
        if ($customer)
        {
            $billing_data = $invoice->get_billing_data();
            $billing_data->render_address();
            org_openpsa_contactwidget::show_address_card($customer, array('postal'));
        } ?>
    </div>
<div class="main org_openpsa_invoices_invoice">
    <p><strong><?php echo $data['l10n']->get('invoice status'); ?>: </strong>
    <?php
    if (!$invoice->sent)
    {
        echo $data['l10n']->get('unsent');
    }
    else
    {
        if (!$invoice->paid)
        {
            if ($invoice->due > time())
            {
                echo sprintf($data['l10n']->get('due on %s'), strftime("%x", $invoice->due));
            }
            else 
            {
                echo '<span class="bad">' . sprintf($data['l10n']->get('overdue since %s'), strftime("%x", $invoice->due)) . '</span>';
            }
        }
        else
        {
            echo sprintf($data['l10n']->get('paid on %s'), strftime("%x", $invoice->paid));
        }
    }
    echo "</p>\n";
    if ($invoice->owner)
    {
        echo "<p><strong>" . $data['l10n_midcom']->get('owner') . ": </strong>\n";
        $owner_card = org_openpsa_contactwidget::get($invoice->owner);
        echo $owner_card->show_inline() . "</p>\n";
    }

    if ($customer)
    {
        echo "<p><strong>" . $data['l10n']->get('customer') . ": </strong>\n";
        echo '<a href="' . $contacts_url . 'group/' . $customer->guid . '/">' . $customer->get_label() . "</a>\n";
        echo "</p>\n";
    }
    ?>
    <h2><?php echo $data['l10n']->get('invoice data'); ?></h2>
    <p><strong><?php echo $data['l10n']->get('invoice date'); ?>: </strong>
    <?php echo strftime("%x", $invoice->date); ?></p>

    <p><strong> <?php echo $_MIDCOM->i18n->get_string('description' , 'midcom');?>: </strong></p>
    <pre class="description">
          &(view['description']);
    </pre>

    <?php
    if (!empty($data['invoice_items']))
    { ?>
        <p><strong><?php echo $data['l10n']->get('invoice items'); ?>:</strong></p>
        <table class='list invoice_items'>
        <thead>
        <th>
        <?php echo $_MIDCOM->i18n->get_string('description' , 'midcom'); ?>
        </th>
        <th class='numeric'>
        <?php echo $data['l10n']->get('price'); ?>
        </th>
        <th class='numeric'>
        <?php echo $data['l10n']->get('quantity'); ?>
        </th>
        <th class='numeric'>
        <?php echo $data['l10n']->get('sum'); ?>
        </th>
        </thead>
        <tbody>
        <?php
        $invoice_sum = 0;
        foreach ($data['invoice_items'] as $item)
        {
            echo "<tr class='invoice_item_row'>";
            echo "<td>";
            echo nl2br($item->description);
            echo "</td>";
            echo "<td class='numeric'>" . org_openpsa_helpers::format_number($item->pricePerUnit) . "</td>";
            echo "<td class='numeric'>" . $item->units . "</td>";
            echo "<td class='numeric'>" . org_openpsa_helpers::format_number($item->units * $item->pricePerUnit) . "</td>";
            echo "</tr>";
            $invoice_sum += $item->units * $item->pricePerUnit;
        }
        ?>
        </tbody>
          <tfoot>
           <tr>
              <td><?php echo $data['l10n']->get('sum excluding vat'); ?>:</td>
              <td class="numeric" colspan="3"><?php echo org_openpsa_helpers::format_number($invoice_sum); ?></td>
           </tr>
           <tr class="secondary">
              <td><?php echo $data['l10n']->get('vat'); ?> (&(view['vat']);):</td>
              <td class="numeric" colspan="3"><?php echo org_openpsa_helpers::format_number(($invoice_sum / 100) * $invoice->vat); ?></td>
           </tr>
           <tr class="primary">
              <td><?php echo $data['l10n']->get('sum including vat'); ?>:</td>
              <td class="numeric" colspan="3"><?php echo org_openpsa_helpers::format_number((($invoice_sum / 100) * $invoice->vat) + $invoice_sum); ?></td>
           </tr>
           </tfoot>
        </table>
        <?php
    }

    if ($view['files'] != "")
    { ?>
        <p><strong><?php echo $data['l10n']->get('files'); ?></strong></p>
        &(view['files']:h);
    <?php }
    if ($view['pdf_file'] != "")
    { ?>
        <p><strong><?php echo $data['l10n']->get('pdf file'); ?></strong></p>
        &(view['pdf_file']:h);
    <?php }

// Display invoiced hours and tasks
if (    isset($data['sorted_reports'])
     && count($data['sorted_reports']['reports']) > 0)
{
    $grid_id = 'invoice_' . $invoice->number . '_hours_grid';

    $guids = array();
    $rows = array();

    foreach ($data['sorted_reports']['reports'] as $report)
    {
        $row = array();

        $guids[] = $report->guid;

        $task = org_openpsa_projects_task_dba::get_cached($report->task);
        $reporter = org_openpsa_contacts_person_dba::get_cached($report->person);
        $reporter_card = org_openpsa_contactwidget::get($report->person);

        $approved_img_src = MIDCOM_STATIC_URL . '/stock-icons/16x16/';
        if ($report->is_approved())
        {
            $approved_text = $data['l10n']->get('approved');
            $approved_img_src .= 'page-approved.png';
        }
        else
        {
            $approved_text = $data['l10n']->get('not approved');
            $approved_img_src .= 'page-notapproved.png';
        }
        $approved_img =  "<img src='{$approved_img_src}' alt='{$approved_text}' title='{$approved_text}' />";

        $row['id'] = $report->id;
        $row['index_date'] = $report->date;
        $row['date'] = strftime('%x', $report->date);
        $row['index_reporter'] = $reporter->rname;
        $row['reporter'] = $reporter_card->show_inline();
        $row['hours'] = $report->hours;
        $row['description'] = $report->description;
        $row['approved'] = $approved_img;
        $row['task'] = "<a href=\"{$projects_url}task/{$task->guid}/\">{$task->title}</a>";

        $rows[] = $row;
    }
?>
<script type="text/javascript">//<![CDATA[
     var &(grid_id);_entries = <?php echo json_encode($rows); ?>
//]]></script>

<div class="hours full-width">

<table id="&(grid_id);"></table>
<div id="p_&(grid_id);"></div>

</div>

<script type="text/javascript">
jQuery("#&(grid_id);").jqGrid({
      datatype: "local",
      data: &(grid_id);_entries,
      colNames: ['id', <?php
                 echo '"index_date", "' .  $_MIDCOM->i18n->get_string('date', 'org.openpsa.projects') . '",'; 
                 
                 echo '"index_reporter", "' .  $_MIDCOM->i18n->get_string('reporter', 'org.openpsa.projects') . '",'; 
                 echo '"' . $_MIDCOM->i18n->get_string('hours', 'org.openpsa.projects') . '",'; 
                 echo '"' . $_MIDCOM->i18n->get_string('description', 'org.openpsa.projects') . '",'; 
                 echo '"' . $_MIDCOM->i18n->get_string('approved', 'org.openpsa.projects') . '",'; 
                 echo '"' . $_MIDCOM->i18n->get_string('task', 'org.openpsa.projects') . '"'; 
                ?>],
      colModel:[
          {name:'id', index:'id', hidden:true, key:true},
          {name:'index_date',index:'index_date', sorttype: "integer", hidden:true},
          {name:'date', index: 'index_date', width: 70, align: 'center', fixed: true},
          {name:'index_reporter', index:'index_reporter', hidden:true},
          {name:'reporter', index: 'index_reporter', width: 60,},
          {name:'hours', index: 'hours', width: 25, align: 'right', formatter: 'number', sorttype: 'float', summaryType:'sum'},
          {name:'description', index: 'description', width: 150},
          {name:'approved', index: 'approved', width: 20, align: 'center', fixed: true},
          {name:'task', index: 'task',}
      ],
      loadonce: true,
      rowNum: <?php echo sizeof($rows); ?>,
      scroll: 1,
      caption: '<?php echo $data['l10n']->get('invoiced hour reports'); ?>',
      grouping: true,
      groupingView: { 
          groupField: ['task'],
          groupColumnShow: [false],
          groupText : ['<strong>{0}</strong> ({1})'],
          groupOrder: ['asc'],
          groupSummary : [true], 
          showSummaryOnHide: true
       }
    });
</script>

<?php
    echo "<form method=\"post\" action=\"" . $projects_url . "csv/hours/?filename=hours_invoice_" . $invoice->number . ".csv\">\n";
    echo "    <input type=\"hidden\" id=\"csvdata\" name=\"org_openpsa_core_csvexport\" value=\"\" />";
    foreach ($guids as $guid)
    {
        if ($guid == "")
        {
            continue;
        }
        echo "    <input type=\"hidden\" name=\"guids[]\" value=\"" . $guid . "\" />\n";
    }
    echo "    <input type=\"hidden\" name=\"order[date]\" value=\"ASC\" />\n";
    echo "    <input class=\"button\" type=\"submit\" value=\"" . $_MIDCOM->i18n->get_string('download as CSV', 'org.openpsa.core') . "\" />\n";
    echo "</form>\n";

    echo "</div>\n";
}
?>