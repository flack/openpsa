<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
$user = $_MIDCOM->auth->user->get_storage();
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_root_node());
$contact = new org_openpsa_contactwidget($user);

echo "<ul>\n";
echo "    <li class=\"user\">" . $contact->show_inline() . "</li>\n";
echo "    <li class=\"filter\">\n";
echo "        <input id=\"org_openpsa_mypage_workgroups_ajaxUrl\" type=\"hidden\" value=\"{$node[MIDCOM_NAV_FULLURL]}savefilter/\" />\n";
echo "        <select title=\"" . $data['l10n']->get('select filter') . "\" id=\"org_openpsa_mypage_workgroups\" name=\"org_openpsa_workgroup_filter\" class=\"ajax_editable\" onchange=\"ooAjaxSelect(this);\">\n";
foreach ($data['virtual_groups'] as $key => $vgroup)
{
    if (is_object($vgroup))
    {
        $label = $vgroup->name;
    }
    else
    {
        $label = $vgroup;
    }

    $selected = '';
    if ($GLOBALS['org_openpsa_core_workgroup_filter'] == $key)
    {
        $selected = ' selected="selected"';
    }

    if ($label != '')
    {
        echo "            <option value=\"{$key}\"{$selected}>{$label}</option>\n";
    }
}
echo "        </select>\n";
echo "    </li>\n";

echo "    <li class=\"logout\"><a href=\"{$node[MIDCOM_NAV_FULLURL]}midcom-logout-\"><img src=\"" . MIDCOM_STATIC_URL . "/stock-icons/16x16/exit.png\" title=\"" . $data['l10n_midcom']->get('logout') . "\" alt=\"" . $data['l10n_midcom']->get('logout') . "\" /></a></li>\n";

echo "</ul>\n";
?>