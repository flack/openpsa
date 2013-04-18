<h1><?php echo $data['topic']->extra; ?></h1>
<div id="slideshow_container">
</div>
<script type="text/javascript">
    Galleria.loadTheme(MIDCOM_STATIC_URL + '/org.openpsa.slideshow/galleria/themes/default/galleria.default.js');
</script>
<script type="text/javascript">
var slideshow_data = <?php echo json_encode($data['entries']); ?>;

$('#slideshow_container').galleria({
    width: 620, //keep this in sync with config
    height: 500,
    dataSource: slideshow_data,
    lightbox: true
});
</script>