<?php
$link = $data['link'];
$salesproject = $data['other_obj'];

$owner_card = org_openpsa_widgets_contact::get($salesproject->owner);
?>

<li class="salesproject" id="org_openpsa_relatedto_line_&(link['guid']);">
  <span class="icon">&(data['icon']:h);</span>
  <span class="title">&(data['title']:h);</span>
  <ul class="metadata">
    <?php
    // Owner
    echo "<li>" . midcom::get()->i18n->get_string('owner', 'midcom') . ": ";
    echo $owner_card->show_inline() . "</li>";
    // Customer
    if ($salesproject->customer) {
        $customer = midcom_db_group::get_cached($salesproject->customer);
        echo "<li>" . midcom::get()->i18n->get_string('customer', 'org.openpsa.sales') . ": ";
        echo $customer->official;
        echo "</li>";
    }
    ?>
  </ul>

  <div id="org_openpsa_relatedto_details_&(salesproject.guid);" class="details hidden" style="display: none;">
  </div>
  <?php
  //TODO: necessary JS stuff to load details (which should in turn include the invoice's own relatedtos) via AHAH
  org_openpsa_relatedto_handler_relatedto::render_line_controls($link, $data['other_obj']);
  ?>
</li>
