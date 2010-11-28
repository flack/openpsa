<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
                <div id="content">

                    <div id="breadcrumb">
                        <?php
                        $nap = new midcom_helper_nav();
                        echo $nap->get_breadcrumb_line(" &gt; ", null, 1);
                        ?>
                    </div>


                    <?php
                    $_MIDCOM->uimessages->show_simple();
                    ?>

                    <div class="page-title">
                        <?php
                        if (midgard_admin_asgard_plugin::get_preference('enable_quicklinks') !== 'no')
                        {
                        ?>
                        <div class="quicklinks">
                            <ul>
                                <?php
                                $help_file = midcom_admin_help_help::generate_file_path($data['handler_id'], 'midgard.admin.asgard');
                                if ($help_file)
                                {
                                    echo "                                <li>\n";
                                    echo "                                    <a href=\"{$prefix}__ais/help/midgard.admin.asgard/{$data['handler_id']}/\" target=\"_blank\" title=\"" . $_MIDCOM->i18n->get_string('midcom.admin.help', 'midcom.admin.help') . "\"><img src=\"" . MIDCOM_STATIC_URL . "/stock-icons/16x16/stock_help-agent.png\" alt=\"" . $_MIDCOM->i18n->get_string('midcom.admin.help', 'midcom.admin.help') . "\" /></a>\n";
                                    echo "                                </li>\n";
                                }
                                ?>
                                <li>
                                    <a href="&(prefix);__mfa/asgard/preferences/?return_uri=<?php echo midcom_connection::get_url('uri'); ?>" title="<?php echo $_MIDCOM->i18n->get_string('user preferences', 'midgard.admin.asgard'); ?>"><img src="<?php echo MIDCOM_STATIC_URL; ?>/stock-icons/16x16/configuration.png" alt="<?php echo $_MIDCOM->i18n->get_string('user preferences', 'midgard.admin.asgard'); ?>" /></a>
                                </li>
                                <li>
                                    <a href="&(prefix);" title="<?php echo $_MIDCOM->i18n->get_string('back to site', 'midgard.admin.asgard'); ?>"><img src="<?php echo MIDCOM_STATIC_URL; ?>/stock-icons/16x16/gohome.png" alt="<?php echo $_MIDCOM->i18n->get_string('back to site', 'midgard.admin.asgard'); ?>" /></a>
                                </li>
                            </ul>
                        </div>
                        <?php
                        }
                        echo "<h1>";

                        if (   isset($data['object'])
                            && isset($data['object']->__mgdschema_class_name__))
                        {
                            $ref = midcom_helper_reflector::get($data['object']);
                            $type_icon = $ref->get_object_icon($data['object']);
                            echo "<span class=\"object_type_link\"><a href=\"{$prefix}__mfa/asgard/{$data['object']->__mgdschema_class_name__}/\">{$type_icon}</a></span> ";
                        }

                        echo "{$data['view_title']}</h1>\n";
                        ?>
                    </div>
<?php
$toolbar_style = '';
if (($position = midgard_admin_asgard_plugin::get_preference('toolbar_mode')))
{
    $toolbar_style = " style=\"position: {$position}\" class=\"{$position}\"";
}
?>
                    <div id="toolbar"&(toolbar_style:h);>
<?php
echo $data['asgard_toolbar']->render();

if ($position === 'absolute')
{
?>
<script type="text/javascript">
    // <![CDATA[
        jQuery('#toolbar')
            .draggable({
                stop: function()
                {
                    var offset = jQuery('#toolbar').offset();
                    jQuery.post(MIDCOM_PAGE_PREFIX + '__mfa/asgard/preferences/ajax/',
                    {
                        toolbar_x: offset.left,
                        toolbar_y: offset.top
                    });
                }
            })
            .css({
                position: 'fixed !important',
                top: '<?php echo midgard_admin_asgard_plugin::get_preference('toolbar_y'); ?>px',
                left: '<?php echo midgard_admin_asgard_plugin::get_preference('toolbar_x'); ?>px',
                cursor: 'move'
            })
            .resizable();

    // ]]>
</script>
<?php
}
?>

                    </div>

                    <div id="content-text">
