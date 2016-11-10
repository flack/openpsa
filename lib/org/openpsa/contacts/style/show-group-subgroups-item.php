<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
$view_group = $data['subgroup'];
$view_group_name = $view_group->official;
if ($view_group_name == '')
{
    $view_group_name = $view_group->name;
}
?>
<div class="vcard">
    <div class="organization-name">
        <a href="&(prefix);group/&(view_group.guid);/">&(view_group_name);</a>
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
        elseif ($view_group->street)
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