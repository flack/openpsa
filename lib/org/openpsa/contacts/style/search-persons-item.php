<?php
// Query the needed data
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);

// Display the contact
$contact = new org_openpsa_widgets_contact($data['person']);
$contact->link = $prefix . "person/{$data['person']->guid}/";
$contact->show();
