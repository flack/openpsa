<?php
$head = midcom::get()->head;
$l10n = midcom::get()->i18n->get_l10n('midgard.admin.asgard');
if (isset($data['view_title'])) {
    $head->set_pagetitle($data['view_title']);
}

// Check the user preference and configuration
if (midgard_admin_asgard_plugin::get_preference('escape_frameset')) {
    $head->add_jsonload('if(top.frames.length != 0 && top.location.href != this.location.href){top.location.href = this.location.href}');
}

$pref_found = false;

if ($width = midgard_admin_asgard_plugin::get_preference('offset', false)) {
    $navigation_width = $width - 31;
    $content_offset = $width + 1;
    $pref_found = true;
}

$head->add_stylesheet(MIDCOM_STATIC_URL . "/stock-icons/font-awesome-4.7.0/css/font-awesome.min.css");
$head->add_stylesheet(MIDCOM_STATIC_URL . "/midgard.admin.asgard/screen.css");

$head->enable_jquery_ui(['mouse', 'draggable']);
$head->add_jsfile(MIDCOM_STATIC_URL . '/midgard.admin.asgard/ui.js');
$context = midcom_core_context::get();
$prefix = $context->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
$extra_class = (!empty($data['asgard_toolbar']->items)) ? ' page-title-with-toolbar' : '';
?>
<!DOCTYPE html>
<html lang="<?php echo midcom::get()->i18n->get_current_language(); ?>">
    <head>
    <meta charset="UTF-8">
    <title><?php echo $context->get_key(MIDCOM_CONTEXT_PAGETITLE); ?> (<?php echo $l10n->get('asgard for'); ?> <(title)>)</title>
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
                <div id="content">

                    <div id="breadcrumb">
                        <?php
                        $nap = new midcom_helper_nav();
                        echo $nap->get_breadcrumb_line(" &gt; ", null, 1);
                        ?>
                    </div>

                    <?php
                    midcom::get()->uimessages->show_simple();
                    ?>

                    <div class="page-title&(extra_class);">
                        <?php
                        if (midgard_admin_asgard_plugin::get_preference('enable_quicklinks') !== 'no') {
                            ?>
                        <div class="quicklinks">
                            <ul>
                                <?php
                                $help_file = midcom_admin_help_help::generate_file_path($data['handler_id'], 'midgard.admin.asgard');
                            if ($help_file) {
                                echo "                                <li>\n";
                                echo "                                    <a href=\"{$prefix}__ais/help/midgard.admin.asgard/{$data['handler_id']}/\" target='_blank' title=\"" . midcom::get()->i18n->get_string('midcom.admin.help', 'midcom.admin.help') . "\"><i class=\"fa fa-question\"></i></a>\n";
                                echo "                                </li>\n";
                            } ?>
                                <li>
                                    <a href="&(prefix);__mfa/asgard/preferences/?return_uri=<?php echo midcom_connection::get_url('uri'); ?>" title="<?php echo $l10n->get('user preferences'); ?>"><i class="fa fa-sliders"></i></a>
                                </li>
                                <li>
                                    <a href="&(prefix);" title="<?php echo $l10n->get('back to site'); ?>"><i class="fa fa-home"></i></a>
                                </li>
                            </ul>
                        </div>
                        <?php } ?>
                        <h1>
                        <?php
                        if (!empty($data['object']->__mgdschema_class_name__)) {
                            $type_icon = midcom_helper_reflector::get_object_icon($data['object']);
                            echo "<span class=\"object_type_link\"><a href=\"{$prefix}__mfa/asgard/{$data['object']->__mgdschema_class_name__}/\">{$type_icon}</a></span> ";
                        }
                        ?>
                        &(data['view_title']);</h1>
                    </div>

                    <div id="toolbar">
                    <?php
                    echo $data['asgard_toolbar']->render();
                    ?>
                    </div>

                    <div id="content-text">
                    <?php
                    $context->get_key(MIDCOM_CONTEXT_SHOWCALLBACK)();
                    ?>
                    </div>
                    <div id="object_metadata">
                        <?php
                        if (!empty($data['object']->guid)) {
                            echo "GUID: {$data['object']->guid}, ID: {$data['object']->id}.\n";
                        }
                        if ($view_metadata = midcom::get()->metadata->get_view_metadata()) {
                            try {
                                $creator = new midcom_db_person($view_metadata->get('creator'));
                                $creator_string = "<a href=\"" . midcom_connection::get_url('self') . "__mfa/asgard/object/view/{$creator->guid}/\">$creator->name</a>";
                            } catch (midcom_error $e) {
                                $creator_string = $l10n->get('unknown person');
                            }
                            $created = (int) $view_metadata->get('created');
                            printf($l10n->get('created by %s on %s'), $creator_string, strftime('%c', $created) . ".\n");

                            $edited = (int) $view_metadata->get('revised');
                            $revision = $view_metadata->get('revision');
                            if (   $revision > 0
                                && $edited != $created) {
                                try {
                                    $editor = new midcom_db_person($view_metadata->get('revisor'));
                                    $editor_string = "<a href=\"" . midcom_connection::get_url('self') . "__mfa/asgard/object/view/{$editor->guid}/\">$editor->name</a>";
                                } catch (midcom_error $e) {
                                    $editor_string = $l10n->get('unknown person');
                                }

                                printf($l10n->get('last edited by %s on %s (revision %s)'), $editor_string, strftime('%c', $edited), $revision) . "\n";
                            }
                        }
                        ?>
                    </div>
                </div>
                <div id="navigation">
                    <?php
                    echo "<a href=\"{$prefix}__mfa/asgard/\">";
                    echo "<img src=\"" . MIDCOM_STATIC_URL . "/midgard.admin.asgard/asgard2.png\" id=\"asgard_logo\" title=\"Asgard\" alt=\"Asgard\" />";
                    echo "</a>\n";

                    $navigation = new midgard_admin_asgard_navigation($data['object'] ?? null, $data);
                    $navigation->draw();
                    ?>
                </div>
            </div>
        </div>
        <div id="siteinfo">
            <span class="copyrights">
                <img src="<?php echo MIDCOM_STATIC_URL; ?>/midcom.services.toolbars/images/midgard-logo.png" alt="(M)" />
                <strong><?php
                    echo $l10n->get('asgard for');
                    echo " Midgard " . mgd_version();
                ?></strong>.
                Copyright &copy; 1998 - <?php echo date('Y'); ?> <a href="http://www.midgard-project.org/" rel="powered">The Midgard Project</a>.
                Midgard is a <a href="https://en.wikipedia.org/wiki/Free_software">free software</a> available under
                <a href="https://www.gnu.org/licenses/lgpl.html" rel="license" about="http://www.midgard-project.org/">GNU Lesser General Public License</a>.<br />&nbsp;
            </span>
        </div>
        <script type="text/javascript">
        	if (typeof midcom_grid_resize !== 'undefined') {
                var nongrid_height = $('#siteinfo').height() + $('#content-text').offset().top;
                $('#content-text').css('height', 'calc(100vh - ' + nongrid_height  + 'px - 4.7em)');
	            $(window).trigger('resize');
            }
        </script>
    </body>
</html>
