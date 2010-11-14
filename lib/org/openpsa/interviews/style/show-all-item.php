<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
$contact = new org_openpsa_contactwidget($data['person']);
?>
<tr>
    <td>
        <?php echo $contact->show(); ?>
    </td>

    <td>
        <?php $data['datamanager']->display_view(); ?>
    </td>
</tr>