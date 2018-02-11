<?php
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());
?>
<div class="content-with-sidebar">
<div class="main">
    <?php
    midcom::get()->dynamic_load($node[MIDCOM_NAV_RELATIVEURL] . "mycontacts");
    ?>
</div>
<aside>
<div class="area org_openpsa_helper_box">
    <h3><?php echo $data['l10n']->get('groups'); ?></h3>
<?php
    $data['tree']->render();
?>
</div>
</aside>
</div>
