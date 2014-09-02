$(document).ready(function()
{
    $('.content-area')
        .on('click', 'a', function(event)
        {
            event.preventDefault();
            var url = $(this).attr('href')
            title = '';

            if ($(this).parent('td').length > 0)
            {
                title = $(this).parent('td').next().find('input').val();
            }

            top.tinymce.activeEditor.windowManager.getParams().oninsert(url, {alt: title});
            top.tinymce.activeEditor.windowManager.close();
        })
        .on('hover', 'a', function()
        {
            $(this).prop('title', 'Click to insert')
        });

    if ($('#links').length > 0)
    {
        $('#links').fancytree(
        {
            click: function(event, data)
            {
                if (data.targetType === 'title')
                {
                    top.tinymce.activeEditor.windowManager.getParams().oninsert(data.node.data.href, {title: data.node.title});
                    top.tinymce.activeEditor.windowManager.close();
                }
            }
        });
    }
});
