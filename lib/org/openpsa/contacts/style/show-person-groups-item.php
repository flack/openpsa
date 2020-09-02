<?php
$view_group = $data['group'];
$group_guid = $data['group']->guid;
$link = $data['router']->generate('group_view', ['guid' => $group_guid]);
$title_link = $data['router']->generate('group_update_member_title', ['guid' => $group_guid]);

if ($data['member']->can_do('midgard:update')) {
    $view_title_form = "<input name=\"member_title[{$data['member']->id}]\"
        class=\"ajax_editable\"
        style=\"width: 80%;\"
        value=\"{$data['member_title']}\"
        data-guid=\"" . $data['member']->guid . "\"
        placeholder=\"" . $data['l10n']->get('<title>') . "\"
        data-ajax-url=\"{$title_link}\" />\n";
} else {
    $view_title_form = $data['member_title'];
}

$view_group_name = $view_group->get_label();
?>
<div class="vcard">
    <div class="organization-name">
        <a href="&(link);">&(view_group_name);</a>
    </div>
    <ul>
        <?php
        echo "<li class=\"title\">{$view_title_form}</li>\n";
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