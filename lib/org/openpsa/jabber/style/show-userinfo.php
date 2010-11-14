<?php
$user = $_MIDCOM->auth->user->storage;
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_root_node());
?>
<dl>
<?php
/** Gravatar support, commented out
if ($user->email)
    {
        $size = 25;
        $grav_url = "http://www.gravatar.com/avatar.php?gravatar_id=".md5($user->email)."&size=".$size;
        ?>
          <img src="&(grav_url);" class="avatar" alt="&(user.name);" title="&(user.name);" style="float: left; margin-right: 4px;" />
        <?php
    }
    */
echo "<dt>{$user->firstname} {$user->lastname}</dt>\n";
echo "<dd>\n";
echo "    <ul>\n";
echo "<input id=\"org_openpsa_mypage_workgroups_ajaxUrl\" type=\"hidden\" value=\"{$node[MIDCOM_NAV_FULLURL]}savefilter/\" />\n";
echo "<select id=\"org_openpsa_mypage_workgroups\" name=\"org_openpsa_workgroup_filter\" class=\"ajax_editable\" onchange=\"ooAjaxSelect(this);\">\n";
foreach ($data['virtual_groups'] as $key => $vgroup)
{
    if (is_object($vgroup))
    {
        $key = str_replace('vgroup:', '', $key);
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

    echo "<option value=\"{$key}\"{$selected}>{$label}</option>\n";
}
echo "</select>\n";

echo "        <li><a href=\"?midcom_site[logout]=1\">Logout</a></li>\n";

echo "    </ul>\n";
echo "</dd>\n";
?>
</dl>