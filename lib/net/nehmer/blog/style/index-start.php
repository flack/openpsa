<?php if (array_key_exists('base_ajax_comments_url', $data)): ?>
<script type="text/javascript" charset="utf-8">
var base_ajax_comments_url = MIDCOM_PAGE_PREFIX + '<?php echo $data['base_ajax_comments_url']; ?>';
function showAjaxComments(element, guid)
{
    var container = $(element).parent().siblings('.ajax_comments_container');
    if (container.text().length) {
        container.slideUp(function(){ $(this).text(''); });
    } else {
        container.load(base_ajax_comments_url + guid, function(){ $(this).slideDown(); });
    }
}
</script>
<?php endif; ?>

<h1><?php echo $data['page_title']; ?></h1>

