<?php
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());
?>
<div class="area">
    <h2><?php echo $data['l10n']->get('campaigns'); ?></h2>
    <dl class="campaigns">