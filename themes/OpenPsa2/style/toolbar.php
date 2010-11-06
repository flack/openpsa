<?php
$toolbar_class = "midcom_services_toolbars_simple";

if ($_MIDCOM->auth->can_user_do('midcom:ajax', null, 'midcom_services_toolbars'))
{
    $toolbar_class = "midcom_services_toolbars_fancy";
}

echo "<div class=\"{$toolbar_class} type_palette\" style=\"display:none\">\n";
echo "    <div class=\"logos\">\n";
echo "        <a href=\"" . $_MIDCOM->get_page_prefix() . "midcom-exec-midcom/about.php\">\n";
echo "            <img src=\"" . MIDCOM_STATIC_URL . "/stock-icons/logos/midgard-16x16.png\" width=\"16\" height=\"16\" alt=\"Midgard\" />\n";
echo "        </a>\n";
echo "    </div>\n";
echo "    <div class=\"items\">\n";
echo "        <div id=\"midcom_services_toolbars_topic-folder\" class=\"item\">\n";
echo "            <span class=\"midcom_services_toolbars_topic_title folder\">". $_MIDCOM->i18n->get_string('folder', 'midcom') . "</span>\n";
echo $_MIDCOM->toolbars->render_node_toolbar();
echo "        </div>\n";
echo "        <div id=\"midcom_services_toolbars_topic-host\" class=\"item\">\n";
echo "            <span class=\"midcom_services_toolbars_topic_title host\">". $_MIDCOM->i18n->get_string('host', 'midcom') . "</span>\n";
echo $_MIDCOM->toolbars->render_host_toolbar();
echo "        </div>\n";
echo "        <div id=\"midcom_services_toolbars_topic-help\" class=\"item\">\n";
echo "            <span class=\"midcom_services_toolbars_topic_title help\">". $_MIDCOM->i18n->get_string('help', 'midcom.admin.help') . "</span>\n";
echo $_MIDCOM->toolbars->render_help_toolbar();
echo "        </div>\n";
echo "    </div>\n";
echo "     <div class=\"dragbar\"></div>\n";
echo "</div>\n";
?>