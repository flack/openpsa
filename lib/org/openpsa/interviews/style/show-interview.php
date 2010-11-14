<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
$contact = new org_openpsa_contactwidget($data['person']);
?>
<div class="main">
    <h1><?php echo sprintf($data['l10n']->get('interview %s for "%s"'), $data['person']->name, $data['campaign']->title); ?></h1>

    <div class="contact">
        <?php echo $contact->show(); ?>
    </div>

    <div class="interview" style="clear: left;">
        <?php $data['controller']->display_form(); ?>
    </div>
</div>