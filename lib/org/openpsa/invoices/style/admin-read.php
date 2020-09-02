<?php
$view = $data['object_view'];
$invoice = $data['object'];
$status_helper = new org_openpsa_invoices_status($invoice);
$formatter = $data['l10n']->get_formatter();
try {
    $customer = org_openpsa_contacts_group_dba::get_cached($invoice->customer);
    if ($customer->orgOpenpsaObtype <= org_openpsa_contacts_group_dba::MYCONTACTS) {
        $customer = false;
    }
} catch (midcom_error $e) {
    $customer = false;
}

$siteconfig = org_openpsa_core_siteconfig::get_instance();
$expenses_url = $siteconfig->get_node_relative_url('org.openpsa.expenses');
$contacts_url = $siteconfig->get_node_full_url('org.openpsa.contacts');
?>
<div class="content-with-sidebar">
<div class="main org_openpsa_invoices_invoice">
  <div class="midcom_helper_datamanager2_view">
    <?php
    if ($customer) { ?>
        <div class="field"><div class="title"><?php echo $data['l10n']->get('customer'); ?>: </div>
        <div class="value"><a href="&(contacts_url);group/&(customer.guid);/"><?php echo $customer->get_label(); ?></a></div></div>
    <?php }
    if ($invoice->date > 0) { ?>
        <div class="field"><div class="title"><?php echo $data['l10n']->get('invoice date'); ?>: </div>
        <div class="value"><?php echo $formatter->date($invoice->date); ?></div></div>
    <?php }

    if ($invoice->deliverydate > 0) { ?>
        <div class="field"><div class="title"><?php echo $data['l10n']->get('invoice delivery date'); ?>: </div>
        <div class="value"><?php echo $formatter->date($invoice->deliverydate); ?></div></div>
    <?php } ?>

    <div class="field"><div class="title"><?php echo $data['l10n_midcom']->get('description');?>: </div>
    <div class="description value">&(view['description']:h);</div></div>

    <?php
    if ($invoice->owner) {
        $owner_card = org_openpsa_widgets_contact::get($invoice->owner); ?>
        <div class="field"><div class="title"><?php echo $data['l10n_midcom']->get('owner'); ?>: </div>
        <div class="value"><?php echo $owner_card->show_inline(); ?></div></div>
    <?php }

    // does the invoice have a cancelation invoice?
    if ($invoice->cancelationInvoice) {
        $cancelation_invoice = new org_openpsa_invoices_invoice_dba($invoice->cancelationInvoice);
        $cancelation_invoice_link = $data['router']->generate('invoice', ['guid' => $cancelation_invoice->guid]); ?>

        <div class="field"><div class="title"><?php echo $data['l10n']->get('canceled by'); ?>: </div>
        <div class="value"><a href="&(cancelation_invoice_link);"><?php echo $data['l10n']->get('invoice') . " " . $cancelation_invoice->get_label(); ?></a></div>
        </div>
    <?php }
    // is the invoice a cancelation invoice itself?
    if ($canceled_invoice = $invoice->get_canceled_invoice()) {
        $canceled_invoice_link = $data['router']->generate('invoice', ['guid' => $canceled_invoice->guid]); ?>

        <div class="field"><div class="title"><?php echo $data['l10n']->get('cancelation invoice for'); ?>: </div>
        <div class="value"><a href="&(canceled_invoice_link);"><?php echo $data['l10n']->get('invoice') . " " . $canceled_invoice->get_label(); ?></a></div>
        </div>
	<?php } ?>
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
              <td><?php echo $data['l10n']->get('vat sum'); ?> (&(view['vat']:h);):</td>
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

    if ($view['files'] != "") { ?>
        <p><strong><?php echo $data['l10n']->get('files'); ?></strong></p>
        <?php echo org_openpsa_helpers::render_fileinfo($invoice, 'files');
    }
    if ($view['pdf_file'] != "") { ?>
        <p><strong><?php echo $data['l10n']->get('pdf file'); ?></strong></p>
        <?php echo org_openpsa_helpers::render_fileinfo($invoice, 'pdf_file');
    }

    $tabs = [];
    if ($expenses_url) {
        $qb = org_openpsa_expenses_hour_report_dba::new_query_builder();
        $qb->add_constraint('invoice', '=', $invoice->id);
        $qb->set_limit(1);
        if ($qb->count() > 0) {
            $tabs[] = [
                'url' => $expenses_url . "hours/invoice/{$invoice->guid}/",
                'title' => midcom::get()->i18n->get_string('hour reports', 'org.openpsa.projects'),
            ];
        }
    }
    org_openpsa_widgets_ui::render_tabs($invoice->guid, $tabs);
?>
</div>
<aside>
    <?php
    if ($invoice->customerContact) {
        echo '<div class="area">';
        echo "<h2>" . $data['l10n']->get('customer contact') . "</h2>\n";
        $contact = org_openpsa_widgets_contact::get($invoice->customerContact);
        $contact->show();
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
