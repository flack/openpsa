<?php
$view_group = $data['subgroup'];
$link = $data['router']->generate('group_view', ['guid' => $view_group->guid]);
$view_group_name = $view_group->get_label();
?>
<div class="vcard">
    <div class="organization-name">
        <a href="&(link);">&(view_group_name);</a>
    </div>
    <ul>
        <?php
        if ($view_group->phone) {
            echo "<li class=\"tel work\">{$view_group->phone}</li>\n";
        }

        if ($view_group->postalStreet) {
            echo "<li>{$view_group->postalStreet}, {$view_group->postalCity}</li>\n";
        } elseif ($view_group->street) {
            echo "<li>{$view_group->street}, {$view_group->city}</li>\n";
        }

        if ($view_group->homepage) {
            echo "<li class=\"url\"><a href=\"{$view_group->homepage}\">{$view_group->homepage}</a></li>\n";
        }
        ?>
    </ul>
</div>