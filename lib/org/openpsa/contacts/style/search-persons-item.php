<?php
// Query the needed data
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);

// Display the contact
$contact = new org_openpsa_widgets_contact($data['person']);
$contact->link = $prefix . "person/{$data['person']->guid}/";
$contact->show();
?>