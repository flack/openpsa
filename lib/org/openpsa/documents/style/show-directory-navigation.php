<div class="area org_openpsa_helper_box">
<h3><?php echo $data['l10n']->get('folders');?></h3>
<?php
$nap = new midcom_helper_nav();
$current_node = $nap->get_node($nap->get_current_node());
$url = $current_node[MIDCOM_NAV_RELATIVEURL];
midcom::get()->dynamic_load($url. "directory/navigation/");
?>
</div>