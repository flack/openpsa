<?php
$_MIDCOM->componentloader->load('fi.protie.navigation');
$navi = new fi_protie_navigation(); 
$navi->list_leaves = true;
$navi->list_levels = 3;
$navi->follow_all = true;
$navi->draw();
?>