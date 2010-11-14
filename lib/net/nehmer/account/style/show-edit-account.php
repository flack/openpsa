<?php
$account =& $data['account'];
$schema =& $data['datamanager']->datamanager->schema;
?>

<h2>&(account.name);</h2>

<?php $data['datamanager']->display_form(); ?>

<p><a href="&(data['profile_url']);"><?php $data['l10n_midcom']->show('back'); ?></a></p>