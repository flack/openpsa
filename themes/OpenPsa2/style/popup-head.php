<?php
$i18n = midcom::get()->i18n;
$head = midcom::get()->head;

$title = (array_key_exists('title', $data)) ? $data['title'] : $i18n->get_string('popup', 'org.openpsa.core');
?>
<!DOCTYPE html>
<html lang="<?php echo $i18n->get_current_language(); ?>">
    <head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($title); ?></title>
    <?php
    $head->add_stylesheet(MIDCOM_STATIC_URL . '/org.openpsa.core/popup.css');
    $head->add_stylesheet(MIDCOM_STATIC_URL . '/OpenPsa2/style.css');
    $head->add_stylesheet(MIDCOM_STATIC_URL . '/OpenPsa2/ui-elements.css');
    $head->print_head_elements();
    ?>
    </head>
    <body id="org_openpsa_popup"<?php $head->print_jsonload(); ?>>
        <div id="container">
            <header>
                <h1>&(title);</h1>
                <div id="org_openpsa_toolbar" class="org_openpsa_toolbar">
                    <?php
                      midcom::get()->toolbars->show_view_toolbar();
                    ?>
                </div>
            </header>
            <div id="content">