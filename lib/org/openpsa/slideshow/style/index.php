<h1><?php echo $data['topic']->extra; ?></h1>
<div id="slideshow_container">
<?php
foreach ($data['images'] as $image)
{
    try
    {
        $attachment = new midcom_db_attachment($image->attachment);
    }
    catch (midcom_error $e)
    {
        continue;
    }
    echo '<img src="' . midcom_db_attachment::get_url($attachment) . '" alt="' . $image->description . '" title="' . $image->title . '" />';
}
?>
</div>
<script type="text/javascript">
    Galleria.loadTheme(MIDCOM_STATIC_URL + '/org.openpsa.slideshow/galleria/themes/default/galleria.default.js');
</script>
<script type="text/javascript">
$('#slideshow_container').galleria({
    width:"100%",
    height:500
});
</script>