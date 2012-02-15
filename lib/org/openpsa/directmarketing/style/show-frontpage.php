<?php
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());
?>
<div class="main">
    <?php midcom::get()->dynamic_load($node[MIDCOM_NAV_RELATIVEURL]."campaign/list/"); ?>
</div>
<div class="sidebar">
    <div class="area">
        <!-- TODO: List latest messages -->
    </div>
</div>