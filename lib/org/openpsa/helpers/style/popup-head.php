<?php
//This is a fallback element normally overwritten by the one in the theme
$i18n = midcom::get()->i18n;

$title = (array_key_exists('title', $data)) ? $data['title'] : $i18n->get_string('popup', 'org.openpsa.core');
?>
<!DOCTYPE html>
<html lang="<?php echo $i18n->get_current_language(); ?>">
    <head>
    <meta charset="UTF-8">
    <title></title>
        <?php
        midcom::get()->head->print_head_elements();
        ?>
    </head>
    <body>
