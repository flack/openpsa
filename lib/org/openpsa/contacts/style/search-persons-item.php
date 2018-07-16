<?php
// Display the contact
$contact = new org_openpsa_widgets_contact($data['person']);
$contact->link = $data['router']->generate('person_view', ['guid' => $data['person']->guid]);
$contact->show();
