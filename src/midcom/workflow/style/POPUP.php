<?php
//This is a fallback element normally overwritten by the one in the theme
$i18n = midcom::get()->i18n;
$head = midcom::get()->head;
$title = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_PAGETITLE);
$head->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.workflow/dialog.css');
?>
<!DOCTYPE html>
<html lang="<?php echo $i18n->get_current_language(); ?>">
    <head>
    <meta charset="UTF-8">
    <title>&(title);</title>
        <?php $head->print_head_elements(); ?>
    </head>
    <body <?php $head->print_jsonload(); ?>>
    <div class="midcom-view-toolbar">
    <?php
        midcom::get()->toolbars->show_view_toolbar();
    ?>
    </div>
    <(content)>
    <script type="text/javascript">
    <?php
    foreach (midcom::get()->uimessages->get_messages() as $message) {
        echo "window.parent.$('#midcom_services_uimessages_wrapper').midcom_services_uimessage(" . $message . ");\n";
    }
    ?>
        </script>
    </body>
</html>