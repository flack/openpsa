<?php
$link = $data['link'];
$invoice = $data['other_obj'];
$formatter = $data['l10n']->get_formatter();

// Sum and due date
$paid = '';
if ($invoice->paid > 0) {
    $paid = ", " . midcom::get()->i18n->get_string('paid', 'org.openpsa.invoices') . ": " . $formatter->date($invoice->paid);
} elseif ($invoice->due < time()) {
    $paid = ", " . midcom::get()->i18n->get_string('not paid', 'org.openpsa.invoices');
}
?>

<li class="invoice" id="org_openpsa_relatedto_line_&(link['guid']);">
    <span class="icon">&(data['icon']:h);</span>
    <span class="title">&(data['title']:h);</span>
    <ul class="metadata">
    <?php
    // Customer
    if ($invoice->customer) {
        $customer = midcom_db_group::get_cached($invoice->customer);
        echo "<li>" . midcom::get()->i18n->get_string('customer', 'org.openpsa.invoices') . ": {$customer->official}</li>";
    }

    echo "<li>" . midcom::get()->i18n->get_string('sum', 'org.openpsa.invoices') . ": " . $formatter->number($invoice->sum) . " (" . midcom::get()->i18n->get_string('due', 'org.openpsa.invoices') . ": " . $formatter->date($invoice->due) . "{$paid})</li>";
    ?>
    </ul>

    <div id="org_openpsa_relatedto_details_&(invoice.guid);" class="details hidden" style="display: none;">
    </div>

  <?php
  //TODO: necessary JS stuff to load details (which should in turn include the invoice's own relatedtos) via AHAH
  org_openpsa_relatedto_handler_relatedto::render_line_controls($link, $data['other_obj']);
  ?>
</li>
