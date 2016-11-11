<?php
$toolbars = midcom::get()->toolbars;
$i18n = midcom::get()->i18n;
$toolbar_class = "midcom_services_toolbars_simple";
if (midcom::get()->auth->can_user_do('midcom:ajax', null, 'midcom_services_toolbars')) {
    $toolbar_class = "midcom_services_toolbars_fancy";
}
echo "<div class=\"{$toolbar_class}\" style=\"display:none\">\n";
echo " <div class=\"minimizer\">\n";
echo " </div>\n";
echo " <div class=\"items\">\n";
echo " <div id=\"midcom_services_toolbars_topic-folder\" class=\"item\">\n";
echo " <span class=\"midcom_services_toolbars_topic_title folder\">". $i18n->get_string('folder', 'midcom') . "</span>\n";
echo $toolbars->render_node_toolbar();
echo " </div>\n";
echo " <div id=\"midcom_services_toolbars_topic-host\" class=\"item\">\n";
echo " <span class=\"midcom_services_toolbars_topic_title host\">". $i18n->get_string('host', 'midcom') . "</span>\n";
echo $toolbars->render_host_toolbar();
echo " </div>\n";
echo " <div id=\"midcom_services_toolbars_topic-help\" class=\"item\">\n";
echo " <span class=\"midcom_services_toolbars_topic_title help\">". $i18n->get_string('help', 'midcom.admin.help') . "</span>\n";
echo $toolbars->render_help_toolbar();
echo " </div>\n";
echo " </div>\n";
echo " <div class=\"dragbar\"></div>\n";
echo "</div>\n";
