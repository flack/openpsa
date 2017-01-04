<?php
midcom::get()->auth->require_admin_user();
include MIDCOM_ROOT . '/../tools/migration_helpers/gallery_converter.php';

echo "<pre>\n";
$runner = new gallery_converter;
$runner->execute();
echo "</pre>\n";
