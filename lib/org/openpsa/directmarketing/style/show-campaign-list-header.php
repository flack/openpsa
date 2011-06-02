<?php
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());
?>
<div class="area org_openpsa_helper_box">
    <h3><?php echo $data['l10n']->get('campaigns'); ?></h3>
    <dl class="campaigns">