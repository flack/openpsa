<?php

$nap = new midcom_helper_nav();
$current_node = $nap->get_node($nap->get_current_node());
$url = $current_node[MIDCOM_NAV_RELATIVEURL];
$_MIDCOM->dynamic_load($url. "directory/navigation/");

?>