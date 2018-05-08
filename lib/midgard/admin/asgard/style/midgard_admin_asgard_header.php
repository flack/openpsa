<?php
$head = midcom::get()->head;
// Check the user preference and configuration
if (   midgard_admin_asgard_plugin::get_preference('escape_frameset')
    || (   midgard_admin_asgard_plugin::get_preference('escape_frameset') !== '0'
        && $data['config']->get('escape_frameset'))) {
    $head->add_jsonload('if(top.frames.length != 0 && top.location.href != this.location.href){top.location.href = this.location.href}');
}

$pref_found = false;

if ($width = midgard_admin_asgard_plugin::get_preference('offset')) {
    $navigation_width = $width - 31;
    $content_offset = $width + 1;
    $pref_found = true;
}

$head->add_stylesheet(MIDCOM_STATIC_URL . "/stock-icons/font-awesome-4.7.0/css/font-awesome.min.css");
$head->add_stylesheet(MIDCOM_STATIC_URL . "/midgard.admin.asgard/screen.css");

$head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/core.min.js');
$head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/widgets/mouse.min.js');
$head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/widgets/draggable.min.js');
$head->add_jsfile(MIDCOM_STATIC_URL . '/midgard.admin.asgard/ui.js');
$head->add_jscript("var MIDGARD_ROOT = '" . midcom_connection::get_url('self') . "';");
?>
<!DOCTYPE html>
<html lang="<?php echo midcom::get()->i18n->get_current_language(); ?>">
    <head>
    <meta charset="UTF-8">
    <title><?php echo midcom_core_context::get()->get_key(MIDCOM_CONTEXT_PAGETITLE); ?> (<?php echo $data['l10n']->get('asgard for'); ?> <(title)>)</title>
        <link rel="shortcut icon" href="<?php echo MIDCOM_STATIC_URL; ?>/stock-icons/logos/favicon.ico" />
        <?php
        $head->print_head_elements();
        if ($pref_found) {
            ?>
              <style type="text/css">
                #container #navigation
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
    <body class="asgard"<?php midcom::get()->head->print_jsonload(); ?>>
        <div id="container-wrapper">
            <div id="container">
