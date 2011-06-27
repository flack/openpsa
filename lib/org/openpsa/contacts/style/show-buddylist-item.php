<?php
$contact = $data['person'];
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
$delete_string = $data['l10n']->get('remove buddy');

// Display the contact
$contactwidget = new org_openpsa_widgets_contact($contact);
$contactwidget->link = "{$prefix}person/{$contact->guid}/";

$contactwidget->prefix_html .= '<input type="image" style="float: right;" src="' . MIDCOM_STATIC_URL . '/stock-icons/16x16/trash.png" class="delete" id="org_openpsa_contacts_buddyremove-' . $contact->guid . '" value="' . $delete_string . '" title="' . $delete_string . '" alt="' . $delete_string . '" />';

$contactwidget->show();
?>