<?php
$_MIDCOM->auth->require_valid_user('basic');
$sync_helper = new org_openpsa_products_groupsync();
$sync_helper->verbose = true;
while(@ob_end_flush());
echo "<h1>Starting</h1>\n"; 
flush();
$sync_helper->full_sync();
echo "<h1>Done</h1>\n"; 
flush();
ob_start();
?>