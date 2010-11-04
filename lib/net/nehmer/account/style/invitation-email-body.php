<?php
$hash = $data['hash'];
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
$register_link = "{$prefix}register_invitation/{$hash}/";
$message = $data['user_message'];
?>
&(message);

<?php
$data['l10n']->show('click the following link to accept the invitation');
?>

&(register_link);

