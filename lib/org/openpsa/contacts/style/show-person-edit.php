<?php
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());
?>
<div class="sidebar">
    <?php
    $_MIDCOM->dynamic_load($node[MIDCOM_NAV_RELATIVEURL] . "person/memberships/{$data['person']->guid}/");
    midcom_show_style("show-person-account");
    ?>
</div>

<div class="main">
    <?php
    	$data['controller']->display_form();
    ?>
</div>
