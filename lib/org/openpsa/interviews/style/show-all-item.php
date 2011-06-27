<?php
$contact = new org_openpsa_widgets_contact($data['person']);
?>
<tr>
    <td>
        <?php echo $contact->show(); ?>
    </td>

    <td>
        <?php $data['datamanager']->display_view(); ?>
    </td>
</tr>