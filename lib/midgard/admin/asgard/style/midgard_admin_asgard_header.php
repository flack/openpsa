<?php
// Check the user preference and configuration
$config = midcom_baseclasses_components_configuration::get('midgard.admin.asgard', 'config');
if (   midgard_admin_asgard_plugin::get_preference('escape_frameset')
    || (   midgard_admin_asgard_plugin::get_preference('escape_frameset') !== '0'
        && $config->get('escape_frameset')))
{
    midcom::get('head')->add_jsonload('if(top.frames.length != 0 && top.location.href != this.location.href){top.location.href = this.location.href}');
}

//don't send an XML prolog for IE, it knocks IE6 into quirks mode
if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6.') !== false)
{
    echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
}

$pref_found = false;

if (($width = midgard_admin_asgard_plugin::get_preference('offset')))
{
    $navigation_width = $width - 31;
    $content_offset = $width + 1;
    $pref_found = true;
}

// JavasScript libraries required by Asgard
midcom::get('head')->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.core.min.js');
midcom::get('head')->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.widget.min.js');
midcom::get('head')->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.mouse.min.js');
midcom::get('head')->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.draggable.min.js');
midcom::get('head')->add_jsfile(MIDCOM_STATIC_URL . '/midgard.admin.asgard/resize.js');
midcom::get('head')->add_jscript("var MIDGARD_ROOT = '" . midcom_connection::get_url('self') . "';");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo midcom::get('i18n')->get_current_language(); ?>" lang="<?php echo midcom::get('i18n')->get_current_language(); ?>">
    <head>
    <title><?php echo midcom_core_context::get()->get_key(MIDCOM_CONTEXT_PAGETITLE); ?> (<?php echo $data['l10n']->get('asgard for'); ?> <(title)>)</title>
        <link rel="stylesheet" type="text/css" href="<?php echo MIDCOM_STATIC_URL; ?>/midgard.admin.asgard/screen.css" media="screen,projector" />
        <link rel="shortcut icon" href="<?php echo MIDCOM_STATIC_URL; ?>/stock-icons/logos/favicon.ico" />
        <?php
        midcom::get('head')->print_head_elements();
        if ($pref_found)
        {?>
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
        <?php } ?>
        <!--[if IE 6]>
            <script type="text/javascript">
                var ie6 = true;
            </script>
        <![endif]-->
    </head>
    <body class="asgard"<?php midcom::get('head')->print_jsonload(); ?>>
        <div id="container-wrapper">
            <div id="container">
