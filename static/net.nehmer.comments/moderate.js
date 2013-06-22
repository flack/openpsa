$(document).ready(function()
{
    $('.net_nehmer_comments_comment_toolbar').on('click', '.moderate-ajax', function(event)
    {
        event.preventDefault();
        var comment = $(this).closest('.net_nehmer_comments_comment');
        $.post($(this).attr('href'), {guid: $(this).data('guid'), action: $(this).data('action')}, function(data, textStatus, jqXHR)
        {
            var container = comment.parent();
            comment.slideUp('fast', function()
            {
                $(this).remove()
            });
            if ($('.net_nehmer_comments_comment').length > 0)
            {
                $('.net_nehmer_comments_comment').last().after($(data));
            }
            else
            {
                container.append($(data));
            }
        });
    });
});