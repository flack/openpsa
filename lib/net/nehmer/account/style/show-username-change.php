<h1><?php
    if ($data['config']->get('username_is_email'))
    {
        echo $data['l10n']->get('change email');
    }
    else
    {
        echo $data['l10n']->get('change username');
    }
    ?></h1>

<?php if ($data['processing_msg']) { ?>
<p>&(data['processing_msg']);</p>
<?php } ?>

<?php $data['formmanager']->display_form(); ?>

<p><a href="&(data['profile_url']);"><?php $data['l10n_midcom']->show('back'); ?></a></p>