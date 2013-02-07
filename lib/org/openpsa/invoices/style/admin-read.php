<?php
$view = $data['object_view'];
$invoice = $data['object'];

try
{
    $customer = org_openpsa_contacts_group_dba::get_cached($invoice->customer);
}
catch (midcom_error $e)
{
    $customer = false;
}

$siteconfig = org_openpsa_core_siteconfig::get_instance();
$projects_url = $siteconfig->get_node_full_url('org.openpsa.projects');
$expenses_url = $siteconfig->get_node_full_url('org.openpsa.expenses');
$contacts_url = $siteconfig->get_node_full_url('org.openpsa.contacts');
?>
    <div class="sidebar">
        <?php
        if ($invoice->customerContact)
        {
            echo "<h2>" . $data['l10n']->get('customer contact') . "</h2>\n";
            $contact = org_openpsa_widgets_contact::get($invoice->customerContact);
            echo $contact->show();
        }
        if ($customer)
        {
            $billing_data = $invoice->get_billing_data();
            $billing_data->render_address();
        } ?>

        <div class="org_openpsa_helper_box history status">
        <?php
            echo "<h3>" . $data['l10n']->get('invoice status') . "</h3>\n";
            echo "<div class=\"current-status {$invoice->get_status()}\">";
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
                        echo sprintf($data['l10n']->get('due on %s'), date($data['l10n_midcom']->get('short date'), $invoice->due));
                    }
                    else
                    {
                        echo '<span class="bad">' . sprintf($data['l10n']->get('overdue since %s'), date($data['l10n_midcom']->get('short date'), $invoice->due)) . '</span>';
                    }
                }
                else
                {
                    echo sprintf($data['l10n']->get('paid on %s'), date($data['l10n_midcom']->get('short date'), $invoice->paid));
                }
            }
            echo "</div>\n";

            if ($invoice->owner)
            {
                echo "<p><strong>" . $data['l10n_midcom']->get('owner') . ": </strong>\n";
                $owner_card = org_openpsa_widgets_contact::get($invoice->owner);
                echo $owner_card->show_inline() . "</p>\n";
            }

            echo "<ul>\n";
            if ($invoice->paid)
            {
                echo '<li><span class="date">' . date($data['l10n_midcom']->get('short date') . ' H:i', $invoice->paid) . '</span>: <br />';
                echo sprintf($data['l10n']->get('marked invoice %s paid'), '') . '</li>';
                if ($invoice->due < $invoice->paid)
                {
                    echo '<li><span class="date">' . date($data['l10n_midcom']->get('short date') . ' H:i', $invoice->due) . '</span>: <br />';
                    echo $data['l10n']->get('overdue') . '</li>';
                }
            }
            else if (   $invoice->due
                     && $invoice->due < time())
            {
                echo '<li><span class="date">' . date($data['l10n_midcom']->get('short date') . ' H:i', $invoice->due) . '</span>: <br />';
                echo $data['l10n']->get('overdue') . '</li>';
            }

            if ($invoice->sent)
            {
                echo '<li><span class="date">' . date($data['l10n_midcom']->get('short date') . ' H:i', $invoice->sent) . '</span>: <br />';
                if ($mail_time = $invoice->get_parameter('org.openpsa.invoices', 'sent_by_mail'))
                {
                    echo sprintf($data['l10n']->get('marked invoice %s sent per mail'), '');
                }
                else
                {
                    echo sprintf($data['l10n']->get('marked invoice %s sent'), '');
                }
                echo '</li>';
            }

            echo '<li><span class="date">' . date($data['l10n_midcom']->get('short date') . ' H:i', $invoice->metadata->created) . '</span>: <br />';
            echo sprintf($data['l10n']->get('invoice %s created'), '') . '</li>';

            echo "</ul>\n";
        ?>
    </div>
    </div>
<div class="main org_openpsa_invoices_invoice">
  <div class="midcom_helper_datamanager2_view">
    <?php if ($customer)
    {
        echo "<div class=\"field\"><div class=\"title\">" . $data['l10n']->get('customer') . ": </div>\n";
        echo '<div class="value"><a href="' . $contacts_url . 'group/' . $customer->guid . '/">' . $customer->get_label() . "</a>\n</div>\n";
        echo "</div>\n";
    }
    if ($invoice->date > 0)
    {
    ?>
        <div class="field"><div class="title"><?php echo $data['l10n']->get('invoice date'); ?>: </div>
        <div class="value"><?php echo date($data['l10n_midcom']->get('short date'), $invoice->date); ?></div></div>
    <?php
    }

    if ($invoice->deliverydate > 0)
    {
    ?>
        <div class="field"><div class="title"><?php echo $data['l10n']->get('invoice delivery date'); ?>: </div>
        <div class="value"><?php echo date($data['l10n_midcom']->get('short date'), $invoice->deliverydate); ?></div></div>
    <?php
    }
    ?>

    <?php
    if (   $invoice->sent
        && !$invoice->paid)
    {
        echo "<div class=\"field\"><div class=\"title\">" . $data['l10n']->get('sent date') . ": </div>\n";
        echo '<div class="value">' . date($data['l10n_midcom']->get('short date'), $invoice->sent) . "</div>\n</div>\n";
    } ?>

    <div class="field"><div class="title"><?php echo midcom::get('i18n')->get_string('description', 'midcom');?>: </div>
    <div class="description value">&(view['description']:h);</div></div>
  
    <?php 
    // does the invoice has a cancelation invoice?
    if ($invoice->cancelationInvoice)
    {
        $cancelation_invoice = new org_openpsa_invoices_invoice_dba($invoice->cancelationInvoice);
        $cancelation_invoice_link = midcom::get()->get_host_name() . '/invoice/invoice/' . $cancelation_invoice->guid . '/';
        
        echo "<div class=\"field\">";
        echo "<div class=\"title\">" . midcom::get('i18n')->get_string('cancelation invoice') .":</div>";
        echo "<div class=\"value\"><a href=\"" . $cancelation_invoice_link . "\">" . midcom::get('i18n')->get_string('invoice') . " " . $cancelation_invoice->get_label() . "</a></div>";
        echo "</div>";
    }
    // is the invoice a cancelation invoice itself?
    $canceled_invoice = $invoice->get_canceled_invoice();
    if ($canceled_invoice)
    {
        $canceled_invoice_link = midcom::get()->get_host_name() . '/invoice/invoice/' . $canceled_invoice->guid . '/';
        
        echo "<div class=\"field\">";
        echo "<div class=\"title\">" . midcom::get('i18n')->get_string('canceled invoice') .":</div>";
        echo "<div class=\"value\"><a href=\"" . $canceled_invoice_link . "\">" . midcom::get('i18n')->get_string('invoice') . " " . $canceled_invoice->get_label() . "</a></div>";
        echo "</div>";
    }
    ?>
  </div>
    <?php
    if (!empty($data['invoice_items']))
    { ?>
        <p><strong><?php echo $data['l10n']->get('invoice items'); ?>:</strong></p>
        <table class='list invoice_items'>
        <thead>
        <tr>
        <th>
        <?php echo midcom::get('i18n')->get_string('description', 'midcom'); ?>
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
        </tr>
        </thead>
        <tfoot>
           <tr>
              <td><?php echo $data['l10n']->get('sum excluding vat'); ?>:</td>
              <td class="numeric" colspan="3"><?php echo org_openpsa_helpers::format_number($invoice->sum); ?></td>
           </tr>
           <tr class="secondary">
              <td><?php echo $data['l10n']->get('vat'); ?> (&(view['vat']:h);):</td>
              <td class="numeric" colspan="3"><?php echo org_openpsa_helpers::format_number(($invoice->sum / 100) * $invoice->vat); ?></td>
           </tr>
           <tr class="primary">
              <td><?php echo $data['l10n']->get('sum including vat'); ?>:</td>
              <td class="numeric" colspan="3"><?php echo org_openpsa_helpers::format_number((($invoice->sum / 100) * $invoice->vat) + $invoice->sum); ?></td>
           </tr>
        </tfoot>
        <tbody>
        <?php
        $invoice_sum = 0;
        foreach ($data['invoice_items'] as $item)
        {
            echo "<tr class='invoice_item_row'>";
            echo "<td>";
            echo $item->render_link();
            echo "</td>";
            echo "<td class='numeric'>" . org_openpsa_helpers::format_number($item->pricePerUnit) . "</td>";
            echo "<td class='numeric'>" . $item->units . "</td>";
            echo "<td class='numeric'>" . org_openpsa_helpers::format_number($item->units * $item->pricePerUnit) . "</td>";
            echo "</tr>\n";
            $invoice_sum += $item->units * $item->pricePerUnit;
        }
        ?>
        </tbody>

        </table>
        <?php
    }

    if ($view['files'] != "")
    { ?>
        <p><strong><?php echo $data['l10n']->get('files'); ?></strong></p>
        <?php echo org_openpsa_helpers::render_fileinfo($invoice, 'files'); ?>
    <?php }
    if ($view['pdf_file'] != "")
    { ?>
        <p><strong><?php echo $data['l10n']->get('pdf file'); ?></strong></p>
        <?php echo org_openpsa_helpers::render_fileinfo($invoice, 'pdf_file'); ?>
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

        try
        {
            $task = org_openpsa_projects_task_dba::get_cached($report->task);
            $reporter = org_openpsa_contacts_person_dba::get_cached($report->person);
        }
        catch (midcom_error $e)
        {
            continue;
        }

        $reporter_card = org_openpsa_widgets_contact::get($report->person);

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
        $row['date'] = date($data['l10n_midcom']->get('short date'), $report->date);
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
                 echo '"index_date", "' .  midcom::get('i18n')->get_string('date', 'org.openpsa.projects') . '",';

                 echo '"index_reporter", "' .  midcom::get('i18n')->get_string('reporter', 'org.openpsa.projects') . '",';
                 echo '"' . midcom::get('i18n')->get_string('hours', 'org.openpsa.projects') . '",';
                 echo '"' . midcom::get('i18n')->get_string('description', 'org.openpsa.projects') . '",';
                 echo '"' . midcom::get('i18n')->get_string('approved', 'org.openpsa.projects') . '",';
                 echo '"' . midcom::get('i18n')->get_string('task', 'org.openpsa.projects') . '"';
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
    echo "<form method=\"post\" action=\"" . $expenses_url . "csv/hour_report/?filename=hours_invoice_" . $invoice->number . ".csv\">\n";
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
    echo "    <input class=\"button\" type=\"submit\" value=\"" . midcom::get('i18n')->get_string('download as CSV', 'org.openpsa.core') . "\" />\n";
    echo "</form>\n";
}
?>
</div>