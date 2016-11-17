<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
$extra_class = (!empty($data['asgard_toolbar']->items)) ? ' page-title-with-toolbar' : '';
?>
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
                                echo "                                    <a href=\"{$prefix}__ais/help/midgard.admin.asgard/{$data['handler_id']}/\" target='_blank' title=\"" . midcom::get()->i18n->get_string('midcom.admin.help', 'midcom.admin.help') . "\"><img src=\"" . MIDCOM_STATIC_URL . "/stock-icons/16x16/stock_help-agent.png\" alt=\"" . midcom::get()->i18n->get_string('midcom.admin.help', 'midcom.admin.help') . "\" /></a>\n";
                                echo "                                </li>\n";
                            } ?>
                                <li>
                                    <a href="&(prefix);__mfa/asgard/preferences/?return_uri=<?php echo midcom_connection::get_url('uri'); ?>" title="<?php echo $data['l10n']->get('user preferences'); ?>"><img src="<?php echo MIDCOM_STATIC_URL; ?>/stock-icons/16x16/configuration.png" alt="<?php echo $data['l10n']->get('user preferences'); ?>" /></a>
                                </li>
                                <li>
                                    <a href="&(prefix);" title="<?php echo $data['l10n']->get('back to site'); ?>"><img src="<?php echo MIDCOM_STATIC_URL; ?>/stock-icons/16x16/gohome.png" alt="<?php echo $data['l10n']->get('back to site'); ?>" /></a>
                                </li>
                            </ul>
                        </div>
                        <?php

                        }
                        echo "<h1>";

                        if (!empty($data['object']->__mgdschema_class_name__)) {
                            $ref = midcom_helper_reflector::get($data['object']);
                            $type_icon = $ref->get_object_icon($data['object']);
                            echo "<span class=\"object_type_link\"><a href=\"{$prefix}__mfa/asgard/{$data['object']->__mgdschema_class_name__}/\">{$type_icon}</a></span> ";
                        }

                        echo "{$data['view_title']}</h1>\n";
                        ?>
                    </div>

                    <div id="toolbar">
                    <?php
                    echo $data['asgard_toolbar']->render();
                    ?>
                    </div>

                    <div id="content-text">
