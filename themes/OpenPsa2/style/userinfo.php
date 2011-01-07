<?php
if ($_MIDCOM->auth->user)
{
    echo "<ul>\n";
    echo "    <li class=\"user\">" . $_MIDCOM->auth->user->name . "</li>\n";
    echo "    <li class=\"logout\"><a href=\"" . midcom_connection::get_url('self') . "midcom-logout-\"><img src=\"" . MIDCOM_STATIC_URL . "/stock-icons/16x16/exit.png\" title=\"" . $_MIDCOM->i18n->get_string('logout', 'midcom') . "\" alt=\"" . $_MIDCOM->i18n->get_string('logout', 'midcom') . "\" /></a></li>\n";
    echo "    <li class=\"midgard\"><img src=\"" . MIDCOM_STATIC_URL . "/stock-icons/logos/midgard-16x16.png\" alt=\"X\" id=\"org_openpsa_toolbar_trigger\" /></li>\n";
    echo "</ul>\n";
}
?>