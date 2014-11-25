<!DOCTYPE html>
<html lang="<?php echo midcom::get()->i18n->get_current_language(); ?>">
    <head>
        <meta charset="UTF-8">
        <title><?php echo midcom_core_context::get()->get_key(MIDCOM_CONTEXT_PAGETITLE); ?></title>
        <?php
        echo midcom::get()->head->print_head_elements();
        ?>
    </head>
    <body <?php midcom::get()->head->print_jsonload(); ?>>