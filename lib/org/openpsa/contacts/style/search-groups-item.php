<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
$link = $prefix . 'group/' . $data['group']->guid . '/';
$view_group_name = $data['group']->get_label();
?>
<div class="vcard">
    <div class="organization-name">
        <a href="&(link);">&(view_group_name);</a>
    </div>
    <ul>
        <?php
        if ($data['group']->phone) {
            echo "<li class=\"tel work\">{$data['group']->phone}</li>\n";
        }

        if ($data['group']->postalStreet) {
            echo "<li>{$data['group']->postalStreet}, {$data['group']->postalCity}</li>\n";
        } elseif ($data['group']->street) {
            echo "<li>{$data['group']->street}, {$data['group']->city}</li>\n";
        }

        if ($data['group']->homepage) {
            echo "<li class=\"url\"><a href=\"{$data['group']->homepage}\">{$data['group']->homepage}</a></li>\n";
        }
        ?>
    </ul>
</div>