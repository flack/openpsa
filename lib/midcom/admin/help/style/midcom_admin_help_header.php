<!DOCTYPE html>
<html lang="<?php echo midcom::get()->i18n->get_current_language(); ?>">
    <head>
        <meta charset="UTF-8">
        <title><?php echo midcom_core_context::get()->get_key(MIDCOM_CONTEXT_PAGETITLE); ?></title>
        <link rel="stylesheet" type="text/css" href="<?php echo MIDCOM_STATIC_URL; ?>/midcom.admin.help/help.css" media="screen,projector" />
        <link rel="shortcut icon" href="<?php echo MIDCOM_STATIC_URL; ?>/stock-icons/logos/favicon.ico" />
        <?php
        midcom::get()->head->print_head_elements();
        ?>
    </head>
    <body>
        <div id="mainmenu">
        <?php
        if ($data['handler_id'] === 'help') {
            echo '<ul>';
            foreach ($data['help_files'] as $id => $info) {
                $url = $data['router']->generate('help', ['component' => $data['component'], 'help_id' => $id]);
                echo '<li><a href="' . $url . '">' . $info['subject'] . '</a></li>';
            }
            echo '</ul>';
        }
        ?>
        </div>

        <div id="breadcrumb">
            <?php
            $nap = new midcom_helper_nav();
            echo $nap->get_breadcrumb_line(" &gt; ", null, 1);
            ?>
        </div>