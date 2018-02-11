<?php
$view = $data['object_view'];
$invoice = $data['object'];
$status_helper = new org_openpsa_invoices_status($invoice);
$formatter = $data['l10n']->get_formatter();
try {
    $customer = org_openpsa_contacts_group_dba::get_cached($invoice->customer);
} catch (midcom_error $e) {
    $customer = false;
}

$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
$siteconfig = org_openpsa_core_siteconfig::get_instance();
$projects_url = $siteconfig->get_node_full_url('org.openpsa.projects');
$expenses_url = $siteconfig->get_node_full_url('org.openpsa.expenses');
$contacts_url = $siteconfig->get_node_full_url('org.openpsa.contacts');

$cancelation_invoice_link = false;
if ($invoice->cancelationInvoice) {
    $cancelation_invoice = new org_openpsa_invoices_invoice_dba($invoice->cancelationInvoice);
    $cancelation_invoice_link = $prefix . 'invoice/' . $cancelation_invoice->guid . '/';

    $cancelation_invoice_link = "<a href=\"" . $cancelation_invoice_link . "\">" . $data['l10n']->get('invoice') . " " . $cancelation_invoice->get_label() . "</a>";
}
?>
<div class="content-with-sidebar">
<div class="main org_openpsa_invoices_invoice">
  <div class="midcom_helper_datamanager2_view">
    <?php if ($customer) {
            echo "<div class=\"field\"><div class=\"title\">" . $data['l10n']->get('customer') . ": </div>\n";
            echo '<div class="value"><a href="' . $contacts_url . 'group/' . $customer->guid . '/">' . $customer->get_label() . "</a>\n</div>\n";
            echo "</div>\n";
        }
    if ($invoice->date > 0) {
        ?>
        <div class="field"><div class="title"><?php echo $data['l10n']->get('invoice date'); ?>: </div>
        <div class="value"><?php echo $formatter->date($invoice->date); ?></div></div>
    <?php

    }

    if ($invoice->deliverydate > 0) {
        ?>
        <div class="field"><div class="title"><?php echo $data['l10n']->get('invoice delivery date'); ?>: </div>
        <div class="value"><?php echo $formatter->date($invoice->deliverydate); ?></div></div>
    <?php

    }
    ?>

    <div class="field"><div class="title"><?php echo $data['l10n_midcom']->get('description');?>: </div>
    <div class="description value">&(view['description']:h);</div></div>

    <?php
    if ($invoice->owner) {
        $owner_card = org_openpsa_widgets_contact::get($invoice->owner); ?>
        <div class="field"><div class="title"><?php echo $data['l10n_midcom']->get('owner'); ?>: </div>
        <div class="value"><?php echo $owner_card->show_inline(); ?></div></div>
    <?php

    } ?>

    <?php
    // does the invoice have a cancelation invoice?
    if ($cancelation_invoice_link) {
        echo "<div class=\"field\">";
        echo "<div class=\"title\">" . $data['l10n']->get('canceled by') .":</div>";
        echo "<div class=\"value\">" . $cancelation_invoice_link . "</a></div>";
        echo "</div>";
    }
    // is the invoice a cancelation invoice itself?
    if ($canceled_invoice = $invoice->get_canceled_invoice()) {
        $canceled_invoice_link = $prefix . 'invoice/' . $canceled_invoice->guid . '/';

        echo "<div class=\"field\">";
        echo "<div class=\"title\">" . $data['l10n']->get('cancelation invoice for') .":</div>";
        echo "<div class=\"value\"><a href=\"" . $canceled_invoice_link . "\">" . $data['l10n']->get('invoice') . " " . $canceled_invoice->get_label() . "</a></div>";
        echo "</div>";
    }
    ?>
  </div>
    <?php
    if (!empty($data['invoice_items'])) {
        ?>
        <p><strong><?php echo $data['l10n']->get('invoice items'); ?>:</strong></p>
        <table class='list invoice_items'>
        <thead>
        <tr>
        <th>
        <?php echo $data['l10n_midcom']->get('description'); ?>
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
              <td class="numeric" colspan="3"><?php echo $formatter->number($invoice->sum); ?></td>
           </tr>
           <tr class="secondary">
              <td><?php echo $data['l10n']->get('vat'); ?> (&(view['vat']:h);):</td>
              <td class="numeric" colspan="3"><?php echo $formatter->number(($invoice->sum / 100) * $invoice->vat); ?></td>
           </tr>
           <tr class="primary">
              <td><?php echo $data['l10n']->get('sum including vat'); ?>:</td>
              <td class="numeric" colspan="3"><?php echo $formatter->number((($invoice->sum / 100) * $invoice->vat) + $invoice->sum); ?></td>
           </tr>
        </tfoot>
        <tbody>
        <?php
        $invoice_sum = 0;
        foreach ($data['invoice_items'] as $item) {
            echo "<tr class='invoice_item_row'>";
            echo "<td>";
            echo $item->render_link();
            echo "</td>";
            echo "<td class='numeric'>" . $formatter->number($item->pricePerUnit) . "</td>";
            echo "<td class='numeric'>" . $formatter->number($item->units) . "</td>";
            echo "<td class='numeric'>" . $formatter->number($item->units * $item->pricePerUnit) . "</td>";
            echo "</tr>\n";
            $invoice_sum += $item->units * $item->pricePerUnit;
        } ?>
        </tbody>

        </table>
        <?php

    }

    if ($view['files'] != "") {
        ?>
        <p><strong><?php echo $data['l10n']->get('files'); ?></strong></p>
        <?php echo org_openpsa_helpers::render_fileinfo($invoice, 'files');
    }
    if ($view['pdf_file'] != "") {
        ?>
        <p><strong><?php echo $data['l10n']->get('pdf file'); ?></strong></p>
        <?php echo org_openpsa_helpers::render_fileinfo($invoice, 'pdf_file');
    }

// Display invoiced hours and tasks
if (!empty($data['reports'])) {
    $grid_id = 'invoice_' . $invoice->number . '_hours_grid';

    $guids = [];
    $rows = [];

    foreach ($data['reports'] as $report) {
        $row = [];

        $guids[] = $report->guid;

        try {
            $reporter = org_openpsa_contacts_person_dba::get_cached($report->person);
            $reporter_card = org_openpsa_widgets_contact::get($report->person);
            $row['index_reporter'] = $reporter->rname;
            $row['reporter'] = $reporter_card->show_inline();
            $task = org_openpsa_projects_task_dba::get_cached($report->task);
            $row['task'] = "<a href=\"{$projects_url}task/{$task->guid}/\">{$task->title}</a>";
        } catch (midcom_error $e) {
            $e->log();
        }

        $approved_img_src = MIDCOM_STATIC_URL . '/stock-icons/16x16/';
        if ($report->is_approved()) {
            $approved_text = $data['l10n']->get('approved');
            $approved_img_src .= 'page-approved.png';
        } else {
            $approved_text = $data['l10n']->get('not approved');
            $approved_img_src .= 'page-notapproved.png';
        }
        $approved_img =  "<img src='{$approved_img_src}' alt='{$approved_text}' title='{$approved_text}' />";

        $row['id'] = $report->id;
        $row['index_date'] = $report->date;
        $row['date'] = $formatter->date($report->date);
        $row['hours'] = $report->hours;
        $row['description'] = $report->get_description();
        $row['approved'] = $approved_img;

        $rows[] = $row;
    } ?>
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
                 echo '"index_date", "' .  midcom::get()->i18n->get_string('date', 'org.openpsa.projects') . '",';

    echo '"index_reporter", "' .  midcom::get()->i18n->get_string('reporter', 'org.openpsa.projects') . '",';
    echo '"' . midcom::get()->i18n->get_string('hours', 'org.openpsa.projects') . '",';
    echo '"' . midcom::get()->i18n->get_string('description', 'org.openpsa.projects') . '",';
    echo '"' . midcom::get()->i18n->get_string('approved', 'org.openpsa.projects') . '",';
    echo '"' . midcom::get()->i18n->get_string('task', 'org.openpsa.projects') . '"'; ?>],
      colModel:[
          {name:'id', index:'id', hidden:true, key:true},
          {name:'index_date',index:'index_date', sorttype: "integer", hidden:true},
          {name:'date', index: 'index_date', width: 70, align: 'center', fixed: true},
          {name:'index_reporter', index:'index_reporter', hidden:true},
          {name:'reporter', index: 'index_reporter', width: 60,},
          {name:'hours', index: 'hours', width: 25, align: 'right', formatter: 'number', sorttype: 'float', summaryType:'sum'},
          {name:'description', index: 'description', width: 150},
          {name:'approved', index: 'approved', width: 20, align: 'center', fixed: true},
          {name:'task', index: 'task'}
      ],
      loadonce: true,
      rowNum: <?php echo sizeof($rows); ?>,
      scroll: 1,
      caption: '<?php echo $data['l10n']->get('invoiced hour reports'); ?>',
      sortname: 'date',
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
    foreach (array_filter($guids) as $guid) {
        echo "    <input type=\"hidden\" name=\"guids[]\" value=\"" . $guid . "\" />\n";
    }
    echo "    <input type=\"hidden\" name=\"order[date]\" value=\"ASC\" />\n";
    echo "    <input class=\"button\" type=\"submit\" value=\"" . midcom::get()->i18n->get_string('download as CSV', 'org.openpsa.core') . "\" />\n";
    echo "</form>\n";
}
?>
</div>
<aside>
    <?php
    if ($invoice->customerContact) {
        echo '<div class="area">';
        echo "<h2>" . $data['l10n']->get('customer contact') . "</h2>\n";
        $contact = org_openpsa_widgets_contact::get($invoice->customerContact);
        echo $contact->show();
        echo '</div>';
    }
    if ($billing_data = $invoice->get_billing_data()) {
        echo '<div class="area">';
        $billing_data->render_address();
        echo '</div>';
    }
    echo $status_helper->render();
    ?>
</aside>
</div>
