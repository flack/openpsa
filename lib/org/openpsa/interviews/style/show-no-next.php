<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<div class="main">
    <p class="info"><?php echo sprintf($data['l10n']->get('no members to interview now in "%s"'), $data['campaign']->title); ?></p>
</div>