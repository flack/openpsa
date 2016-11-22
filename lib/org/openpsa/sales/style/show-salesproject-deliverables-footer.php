</ol>
</div>

<script type="text/javascript">
$('.deliverable_list .deliverable > .icon').click(function(e)
{
    var container = jQuery(this).parent();

    container.find('.information').toggle('fast', function()
    {
        if (container.hasClass('expanded')) {
            container.removeClass('expanded');
            container.addClass('collapsed');
        } else {
            container.addClass('expanded');
            container.removeClass('collapsed');
        }
        $(window).trigger('resize');
    });
});
</script>