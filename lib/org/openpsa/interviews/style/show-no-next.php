<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<div class="main">
    <p class="info"><?php echo sprintf($data['l10n']->get('no members to interview now in "%s"'), $data['campaign']->title); ?></p>
</div>