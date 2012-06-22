<?php
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());

//TODO: Configure whether to show in/both and reverse vs normal sorting ?
midcom::get()->dynamic_load("{$node[MIDCOM_NAV_RELATIVEURL]}__mfa/org.openpsa.relatedto/render/{$data['salesproject']->guid}/both/normal/");
?>
</div>