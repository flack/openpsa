<?php
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());
//$data =& $_MIDCOM->get_custom_context_data('request_data');
$view_group = $data['subgroup'];
$view_group_name = $view_group->official;
if ($view_group_name == '')
{
    $view_group_name = $view_group->name;
}
?>
<div class="vcard">
    <div class="organization-name">
        <a href="&(node[MIDCOM_NAV_FULLURL]);group/&(view_group.guid);/">&(view_group_name);</a>
    </div>
    <ul>
        <?php
        if ($view_group->phone)
        {
            echo "<li class=\"tel work\">{$view_group->phone}</li>\n";
        }

        if ($view_group->postalStreet)
        {
            echo "<li>{$view_group->postalStreet}, {$view_group->postalCity}</li>\n";
        }
        else if ($view_group->street)
        {
            echo "<li>{$view_group->street}, {$view_group->city}</li>\n";
        }

        if ($view_group->homepage)
        {
            echo "<li class=\"url\"><a href=\"{$view_group->homepage}\">{$view_group->homepage}</a></li>\n";
        }
        ?>
    </ul>
</div>