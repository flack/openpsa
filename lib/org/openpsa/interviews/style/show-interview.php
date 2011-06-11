<?php
$contact = new org_openpsa_widgets_contact($data['person']);
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