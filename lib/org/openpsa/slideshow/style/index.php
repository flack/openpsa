<h1><?php echo $data['topic']->extra; ?></h1>
<div id="slideshow_container">
<?php
$entries = array();
foreach ($data['images'] as $image)
{
    try
    {
        $attachment = new midcom_db_attachment($image->attachment);
        $main = new midcom_db_attachment($image->image);
        $thumbnail = new midcom_db_attachment($image->thumbnail);
    }
    catch (midcom_error $e)
    {
        continue;
    }
    $entries[] = array
    (
        'big' => midcom_db_attachment::get_url($attachment),
        'image' => midcom_db_attachment::get_url($main),
        'thumb' => midcom_db_attachment::get_url($thumbnail),
        'title' => $image->title,
        'description' => $image->description
    );
}
?>
</div>
<script type="text/javascript">
    Galleria.loadTheme(MIDCOM_STATIC_URL + '/org.openpsa.slideshow/galleria/themes/default/galleria.default.js');
</script>
<script type="text/javascript">
var slideshow_data = <?php echo json_encode($entries); ?>;

$('#slideshow_container').galleria({
    width: 620, //keep this in sync with config
    height: 500,
    dataSource: slideshow_data
});
</script>