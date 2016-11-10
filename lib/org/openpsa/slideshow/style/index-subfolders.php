<?php
$nap = new midcom_helper_nav;
?>
<ul class="slideshow-subfolders">
<?php
foreach ($data['subfolders'] as $i => $folder) {
    $napdata = $nap->get_node($folder->id);
    echo '<li>';
    echo '<a href="' . $napdata[MIDCOM_NAV_ABSOLUTEURL] . '">';
    if (false !== $data['thumbnails'][$i]) {
        $thumbnail = $data['thumbnails'][$i];
        echo '<img src="' . midcom_db_attachment::get_url($thumbnail) . '" alt="' . $folder->title . '"/>';
    }
    echo '<span class="subfolder-title">' . $folder->extra . "</span>\n";
    echo "</a>\n";
    echo "</li>\n";
}
?>
</ul>