<?php
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());

if ($data['event']->can_do('org.openpsa.calendar:read')) {
    echo "<div class=\"description\">{$data['event']->description}</div>";

    midcom::get()->dynamic_load("{$node[MIDCOM_NAV_RELATIVEURL]}__mfa/org.openpsa.relatedto/render/{$data['event']->guid}/in/normal/");
}