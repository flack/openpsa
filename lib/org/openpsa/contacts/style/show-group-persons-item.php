<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);

// Display the member
$contact = new org_openpsa_widgets_contact($data['person']);
$contact->link = "{$prefix}person/{$data['person']->guid}/";
$contact->show_groups = false;

if ($data['member']->can_do('midgard:update'))
{
    $contact->extra_html = "<li>
        <input name=\"member_title[{$data['member']->id}]\"
               class=\"ajax_editable\"
               style=\"width: 80%;\"
               value=\"{$data['member_title']}\"
               data-guid=\"{$data['member']->guid}\"
               data-ajax-url=\"{$prefix}group/{$data['group']->guid}/update_member_title/\"
               placeholder=\"" . $data['l10n']->get('<title>') . "\" />
        </li>\n";
}
else
{
    $contact->extra_html = "<li>{$data['member_title']}</li>\n";
}

$contact->show();
?>