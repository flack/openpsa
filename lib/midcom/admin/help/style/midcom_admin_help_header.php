<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title><?php echo $_MIDCOM->get_context_data(MIDCOM_CONTEXT_PAGETITLE); ?></title>
        <link rel="stylesheet" type="text/css" href="<?php echo MIDCOM_STATIC_URL; ?>/midcom.admin.help/help.css" media="screen,projector" />
        <link rel="shortcut icon" href="<?php echo MIDCOM_STATIC_URL; ?>/stock-icons/logos/favicon.ico" />
        <?php
        midcom::get('head')->print_head_elements();
        ?>
    </head>
    <body>
        <div id="mainmenu">
        </div>

        <div id="breadcrumb">
            <?php
            $nap = new midcom_helper_nav();
            echo $nap->get_breadcrumb_line(" &gt; ", null, 1);
            ?>
        </div>