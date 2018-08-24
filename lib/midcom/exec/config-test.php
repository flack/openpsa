<?php
midcom::get()->auth->require_admin_user();
$title = midcom::get()->i18n->get_string('test settings', 'midcom')
?>
<html>
<head><title><?php echo $title ?></title>
<link rel="stylesheet" type="text/css" href="<?php echo MIDCOM_STATIC_URL ?>/midcom.workflow/dialog.css" />
<link rel="stylesheet" type="text/css" href="<?php echo MIDCOM_STATIC_URL ?>/stock-icons/font-awesome-4.7.0/css/font-awesome.min.css" />
<style type="text/css">
tr.test th
{
    white-space: nowrap;
    font-weight: normal
}
th, td {
    text-align: left;
    border-bottom: 1px solid #ddd;
    padding: .2rem;
}
.fa {
    font-size: 1.2rem;
    margin: 0 .2rem;
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
