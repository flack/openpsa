<?php
require_once 'vendor/autoload.php';

$installer = openpsa\installer\mgd2setup::get(__DIR__);
$installer->run();
?>
