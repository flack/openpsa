<?php
// Query the needed data
global $view_person;
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());

// Display the contact
$contact = new org_openpsa_widgets_contact($view_person);
$contact->link = "{$node[MIDCOM_NAV_FULLURL]}person/{$view_person->guid}/";
$contact->show();
?>