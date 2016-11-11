<?php
$auth = midcom::get()->auth;
$i18n = midcom::get()->i18n;

if ($auth->user) {
    $siteconf = org_openpsa_core_siteconfig::get_instance();
    if ($user_url = $siteconf->get_node_full_url('org.openpsa.user')) {
        $person_string = '<a href="' . $user_url . 'view/' . $auth->user->guid . '/">' . $auth->user->name . "</a>";
    } else {
        $person_string = $auth->user->name;
    }
    echo "<ul>\n";
    echo "    <li class=\"user\">" . $person_string . "</li>\n";
    echo "    <li class=\"logout\"><a href=\"" . midcom_connection::get_url('self') . "midcom-logout-\"><img src=\"" . MIDCOM_STATIC_URL . "/stock-icons/16x16/exit.png\" title=\"" . $i18n->get_string('logout', 'midcom') . "\" alt=\"" . $i18n->get_string('logout', 'midcom') . "\" /></a></li>\n";
    echo "    <li class=\"midgard\"><div id=\"midcom_services_toolbars_minimizer\"></div></li>\n";
    echo "</ul>\n";
}
