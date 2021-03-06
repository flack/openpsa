<?php
// Display the persons
$contact1 = new org_openpsa_widgets_contact($data['object1']);
$contact1->link = $data['router']->generate('person_view', ['guid' => $data['object1']->guid]);
$contact1->show_groups = false;

$contact2 = new org_openpsa_widgets_contact($data['object2']);
$contact2->link = $data['router']->generate('person_view', ['guid' => $data['object2']->guid]);
$contact2->show_groups = false;
?>
<h1><?php printf($data['l10n']->get('merge %s'), $data['l10n']->get('persons')); ?></h1>
<p><?php printf($data['l10n']->get('choose the %s to keep'), $data['l10n']->get('person')); ?></p>
<form method="post" class="org_openpsa_contacts_duplicates">
    <input type="hidden" name="org_openpsa_contacts_handler_duplicates_object_options[1]" value="<?php echo $data['object1']->guid; ?>" />
    <input type="hidden" name="org_openpsa_contacts_handler_duplicates_object_options[2]" value="<?php echo $data['object2']->guid; ?>" />
    <input type="hidden" name="org_openpsa_contacts_handler_duplicates_object_loop_i" value="<?php echo $data['loop_i']; ?>" />
    <table class="org_openpsa_contacts_duplicates">
        <tr class="contacts">
            <td><?php $contact1->show(); ?></td>
            <td align="center"><?php echo $data['l10n']->get('vs'); ?></td>
            <td><?php $contact2->show(); ?></td>
        </tr>
        <tr align="center" class="choices">
            <td><input type="submit" class="keepone" name="org_openpsa_contacts_handler_duplicates_object_keep[<?php echo $data['object1']->guid; ?>]" value="<?php echo $data['l10n']->get('keep this'); ?>" /></td>
            <td><input type="submit" class="keepboth" name="org_openpsa_contacts_handler_duplicates_object_keep[both]" value="<?php echo $data['l10n']->get('keep both'); ?>" /></td>
            <td><input type="submit" class="keepone" name="org_openpsa_contacts_handler_duplicates_object_keep[<?php echo $data['object2']->guid; ?>]" value="<?php echo $data['l10n']->get('keep this'); ?>" /></td>
        </tr>
        <tr align="center" class="choices">
            <td colspan=3><input type="submit" class="decidelater" name="org_openpsa_contacts_handler_duplicates_object_decide_later" value="<?php echo $data['l10n']->get('decide later'); ?>" /></td>
        </tr>
    </table>
</form>