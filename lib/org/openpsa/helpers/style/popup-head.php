<?php
//This is a fallback element normally overwritten by the one in the template
?>
<!DOCTYPE html>
<html lang="<?php echo midcom::get()->i18n->get_current_language(); ?>">
    <head>
    <meta charset="UTF-8">
    <title></title>
        <?php
        midcom::get()->head->print_head_elements();
        ?>
    </head>
    <body>
