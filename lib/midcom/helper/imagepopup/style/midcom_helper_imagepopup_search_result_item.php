<?php
$item = $data['result'];
$url = midcom_db_attachment::get_url($item);
$item_type = "image";

if (!in_array($item->mimetype, ['image/x-png', 'image/png', 'image/jpeg', 'image/pjpeg', 'image/gif'])) {
    $item_type = "attachment";
}
?>

<li title="&(item.guid);" class="midcom_helper_imagepopup_search_result_item" rel="&(item_type);">
<?php
if ($item_type == "image") {
    ?>
    <a href='&(url);'>
        <img src='&(url);' height='54' />
    </a>
    <a href='&(url);'>
        <span title="name">&(item.name);</span>
    </a>
<?php
} else {
    $mime_icon = midcom_helper_misc::get_mime_icon($item->mimetype);
    ?>
    <img src="&(mime_icon);" alt="&(item.mimetype);" />
    <a href='&(url);'>
        <span title="name">&(item.name);</span>
    </a>
<?php
}
?>

</li>
