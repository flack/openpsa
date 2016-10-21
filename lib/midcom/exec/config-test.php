<?php
midcom::get()->auth->require_admin_user();
$title = midcom::get()->i18n->get_string('test settings', 'midcom')
?>
<html>
<head><title><?php echo $title ?></title>
<link rel="stylesheet" type="text/css" href="<?php echo MIDCOM_STATIC_URL ?>/midcom.workflow/dialog.css" />
<style type="text/css">
tr.test th
{
	text-align: left;
	white-space: nowrap;
	font-weight: normal
}
</style>
</head>
<body>

<?php
$runner = new midcom_config_test();
$runner->check();
$runner->show();
?>
</body>
</html>
