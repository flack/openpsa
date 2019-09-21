$(document).ready(function() {
    $('#net_nehmer_comments_admin').on('click', '.net_nehmer_comments_comment_toolbar .moderate-ajax', function(event) {
        event.preventDefault();
        var comment = $(this).closest('.net_nehmer_comments_comment'),
            toolbar = comment.find('.net_nehmer_comments_comment_toolbar');

        if (toolbar.hasClass('net_nehmer_comments_comment_toolbar_busy')) {
            return;
        }
        toolbar.addClass('net_nehmer_comments_comment_toolbar_busy');
        $.post(this.href, {guid: $(this).data('guid'), action: $(this).data('action')}, function(data) {
            var container = comment.parent();
            comment.slideUp('fast', function() {
                $(this).remove();
            });
            if ($('.net_nehmer_comments_comment').length > 0) {
                $('.net_nehmer_comments_comment').last().after($(data));
            } else {
                container.append($(data));
            }
        });
    });
});
