<?php
$i18n = midcom::get()->i18n;
$head = midcom::get()->head;
$title = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_PAGETITLE);
?>
<!DOCTYPE html>
<html lang="<?php echo $i18n->get_current_language(); ?>">
    <head>
    <meta charset="UTF-8">
    <title>&(title);</title>
    <?php
    $head->add_stylesheet(MIDCOM_STATIC_URL . '/OpenPsa2/popup.css');
    $head->add_stylesheet(MIDCOM_STATIC_URL . '/OpenPsa2/style.css');
    $head->add_stylesheet(MIDCOM_STATIC_URL . '/OpenPsa2/ui-elements.css');
    $head->print_head_elements();
    ?>
    </head>
    <body id="org_openpsa_popup"<?php $head->print_jsonload(); ?>>
        <div id="container">
            <div id="content" class="no-header">
                <div class="midcom-view-toolbar">
                    <?php
                      midcom::get()->toolbars->show_view_toolbar();
                    ?>
                </div>
                <(content)>
            </div>
        </div>
        <script type="text/javascript">
        <?php
        foreach (midcom::get()->uimessages->get_messages() as $message) {
            echo "window.parent.$('#midcom_services_uimessages_wrapper').midcom_services_uimessage(" . $message . ");\n";
        }
        ?>
        </script>
  </body>
</html>