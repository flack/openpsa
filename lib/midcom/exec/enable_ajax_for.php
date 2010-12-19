<?php
$_MIDCOM->auth->require_admin_user();

$is_group = false;

if (! empty($_GET['group']))
{
    $is_group = true;
    $guid = $_GET['group'];
}
elseif (!empty($_GET['person']))
{
    $guid = $_GET['person'];
}
else
{
    throw new midcom_error_notfound("Specify either group or person GUID in GET params");
}

$priv = new midcom_core_privilege_db();
$priv->objectguid = $guid;
$priv->privilegename = 'midcom:ajax';
$priv->assignee = "SELF";
$priv->classname = 'midcom_services_toolbars';
$priv->value = 1;
$priv->create();

$priv = new midcom_core_privilege_db();
$priv->objectguid = $guid;
$priv->privilegename = 'midcom:ajax';
$priv->assignee = "SELF";
$priv->classname = 'midcom_services_uimessages';
$priv->value = 1;
$priv->create();

$priv = new midcom_core_privilege_db();
$priv->objectguid = $guid;
$priv->privilegename = 'midcom:centralized_toolbar';
$priv->assignee = "SELF";
$priv->classname = 'midcom_services_toolbars';
$priv->value = 1;
$priv->create();
?>