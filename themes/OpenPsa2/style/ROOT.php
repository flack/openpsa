<?php
$i18n = midcom::get()->i18n;
$head = midcom::get()->head;
$context = midcom_core_context::get();

if (!defined('MIDCOM_STATIC_URL')) {
    define('MIDCOM_STATIC_URL', '/midcom-static');
}

$pref_found = false;
$width = midgard_admin_asgard_plugin::get_preference('openpsa2_offset');
if ($width !== false) {
    $navigation_width = $width - 2;
    $content_offset = $width;
    $pref_found = true;
}

$topic = $context->get_key(MIDCOM_CONTEXT_CONTENTTOPIC);
$title_prefix = $topic->extra . ': ' . $context->get_key(MIDCOM_CONTEXT_PAGETITLE);
?>
<!DOCTYPE html>
<html lang="<?php echo $i18n->get_current_language(); ?>">
    <head>
        <meta charset="UTF-8">
        <title>&(title_prefix); - <(title)> OpenPSA</title>
        <link type="image/x-icon" href="<?php echo MIDCOM_STATIC_URL; ?>/org.openpsa.core/openpsa-16x16.png" rel="shortcut icon"/>
        <?php
          $head->add_stylesheet(MIDCOM_STATIC_URL . "/stock-icons/font-awesome-4.7.0/css/font-awesome.min.css");
          $head->add_stylesheet(MIDCOM_STATIC_URL . '/OpenPsa2/style.css');
          $head->add_stylesheet(MIDCOM_STATIC_URL . '/OpenPsa2/print.css', 'print');

        $head->enable_jquery_ui(['mouse', 'draggable']);

        org_openpsa_widgets_ui::add_head_elements();
        org_openpsa_widgets_tree::add_head_elements();

        $head->add_jscript("const TOOLBAR_MORE_LABEL = '" . midcom::get()->i18n->get_l10n('org.openpsa.widgets')->get('more') . "';");
        $head->add_jsfile(MIDCOM_STATIC_URL . '/OpenPsa2/ui.js');
        $head->print_head_elements();

        if ($pref_found) {
            ?>
            <style type="text/css">
            #container #leftframe
            {
                width: &(navigation_width);px;
            }

            #container #content
            {
                margin-left: &(content_offset);px;
            }
            </style>
        <?php
        } ?>

    </head>
    <body<?php $head->print_jsonload(); ?>>
        <div id="container">
          <div id="leftframe">
            <div id="branding">
                <(logo)>
            </div>
            <div id="nav">
                <(navigation)>
            </div>
          </div>
          <div id="content">
              <div id="content-menu">
                  <(breadcrumb)>
                  <div class="context">
                      <(userinfo)>
                      <(search)>
                  </div>
              </div>
              <div id="org_openpsa_toolbar" class="org_openpsa_toolbar">
                  <(toolbar-bottom)>
              </div>
              <div id="content-text">
                  <?php
                  //Display any UI messages added to stack on PHP level
                  midcom::get()->uimessages->show();
                  ?>
                  <(content)>
              </div>
          </div>
       </div>
    <(toolbar)>

    <script type="text/javascript">
    org_openpsa_layout.add_splitter();
    org_openpsa_jsqueue.execute();
    </script>
    </body>
    <?php
    // Add after content (component) has already run, so that we can override its styles
    $head->add_stylesheet(MIDCOM_STATIC_URL . '/OpenPsa2/ui-elements.css');
    ?>
</html>