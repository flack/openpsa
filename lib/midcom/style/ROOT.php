<!DOCTYPE html>
<html lang="<?php echo midcom::get()->i18n->get_current_language(); ?>">
    <head>
        <meta charset="UTF-8">
        <title><(title)></title>
        <?php echo midcom::get()->head->print_head_elements(); ?>
    </head>

    <body<?php midcom::get()->head->print_jsonload(); ?>>
    <?php
    midcom::get()->toolbars->show();
    ?>
    <(content)>
      <?php
      midcom::get()->uimessages->show();
      ?>
    </body>
</html>
