<?php
$contact = $data['person'];
$delete_string = $data['l10n']->get('remove from my contacts');

// Display the contact
$contactwidget = new org_openpsa_widgets_contact($contact);
$contactwidget->link = $data['router']->generate('person_view', ['guid' => $contact->guid]);

$contactwidget->prefix_html .= '<i style="float: right;" class="fa fa-trash delete" id="org_openpsa_contacts_mycontactsremove-' . $contact->guid . '" title="' . $delete_string . '"></i>';

$contactwidget->show();
