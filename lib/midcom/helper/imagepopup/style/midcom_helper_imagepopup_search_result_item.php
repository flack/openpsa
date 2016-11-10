<?php
$prefix = midcom_connection::get_url('self');
$item = $data['result'];
$mime_icon = null;
$item_type = "image";

switch ($item->mimetype) {
    case 'image/x-png':
    case 'image/png':
    case 'image/jpeg':
    case 'image/pjpeg':
    case 'image/gif':
        break;
    default:
        $item_type = "attachment";
        $mime_icon = midcom_helper_misc::get_mime_icon($item->mimetype);
}
?>

<li title="&(item.guid);" class="midcom_helper_imagepopup_search_result_item" rel="&(item_type);">
<?php
if ($item_type == "image") {
    ?>
    <a href='&(prefix);midcom-serveattachmentguid-&(item.guid);/&(item.name);'>
        <img src='&(prefix);midcom-serveattachmentguid-&(item.guid);/&(item.name);' height='54' align='texttop' />
    </a>
    <a href='&(prefix);midcom-serveattachmentguid-&(item.guid);/&(item.name);'>
        <span title="name">&(item.name);</span>
    </a>
<?php

} else {
    ?>
    <img src="&(mime_icon);" alt="&(item.mimetype);" align='texttop'/>
    <a href='&(prefix);midcom-serveattachmentguid-&(item.guid);/&(item.name);'>
        <span title="name">&(item.name);</span>
    </a>
<?php

}
?>

</li>
