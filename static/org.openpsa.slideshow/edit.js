$(document).ready(function()
{
    $('.existing-entry').each(function(index, item)
    {
        $(item).data('original_values',
        {
            position: index,
            title: $(item).find('.title input').val(),
            description: $(item).find('.description textarea').val()
        });
    });
    $('#upload_field').bind('change', function()
    {
        var image, thumbnail, entry, reader,
        entry_template = $('#item_container .entry-template')[0];
        $.each(this.files, function(index, file)
        {
            if (!file.type.match(/image.*/))
            {
                // this file is not an image. TODO: Report an error?
                return;
            }

            image = document.createElement('img');
            image.file = file;
            reader = new FileReader();
            reader.onload = (function(img) { return function(e) {img.src = e.target.result};})(image);
            reader.readAsDataURL(file);

            entry = $.clone(entry_template);
            $(entry)
                .removeClass('entry-template')
                .addClass('new-entry');
            $('.thumbnail', entry).prepend(image);
            $('.filename', entry).text(file.name);

            $('#item_container').prepend(entry);
        });
    });
    $('#item_container')
        .delegate('.image-delete', 'click', function()
        {
            $(this).closest('.entry').addClass('entry-deleted');
        })
        .delegate('.image-cancel-delete', 'click', function()
        {
            $(this).closest('.entry').removeClass('entry-deleted');
        })
        .sortable();

    $('#save_all').bind('click', function()
    {
        var service_url = window.location.href + 'ajax/',
        delete_guids = [],
        update_items = {},
        fd, xhr;

        $('#save_all').hide();
        $('#progress_bar')
            .progressbar({value: 0})
            .show()
            .data('pending', 0)
            .data('total', 0);

        function create_entry(index, item)
        {
            var file =  $(item).find('.thumbnail img')[0].file,
            xhr = new XMLHttpRequest(),
            fd = new FormData();

            xhr.upload.addEventListener("progress", function(e)
            {
                if (e.lengthComputable)
                {
                    var percentage = Math.round((e.loaded * 100) / e.total);
                    // do something
                }
            }, false);

            fd.append("title", $(item).find('.title input').val());
            fd.append("description", $(item).find('.description textarea').val());
            fd.append('image', file);
            fd.append("position", index);
            fd.append("operation", 'create');

            xhr.onreadystatechange = function()
            {
                if (xhr.readyState === 4)
                {
                    var reply = $.parseJSON(xhr.responseText);
                    if (!reply.success)
                    {
                        $.midcom_services_uimessage_add({type: 'error', message: reply.error, title: reply.title});
                    }
                    remove_pending_request();
                }
            };
            xhr.open("POST", window.location.href + 'ajax/');
            xhr.send(fd);
            add_pending_request();
        }

        function update_entry(index, item)
        {
            var xhr = new XMLHttpRequest(),
            fd = new FormData(),
            title = $(item).find('.title input').val(),
            description = $(item).find('.description textarea').val(),
            original_values = $(item).data('original_values');

            if (  title === original_values.title
               && description === original_values.description
               && index === original_values.position)
            {
                return;
            }

            fd.append("title", title);
            fd.append("description", description);
            fd.append("position", index);
            fd.append("guid", $(item).attr('id').slice(6));
            fd.append("operation", 'update');

            xhr.onreadystatechange = function()
            {
                if (xhr.readyState === 4)
                {
                    remove_pending_request();
                }
            };

            xhr.open("POST", window.location.href + 'ajax/');
            xhr.send(fd);
            add_pending_request();
        }

        function add_pending_request()
        {
            var pending = $('#progress_bar').data('pending') + 1,
            total = $('#progress_bar').data('total') + 1,
            completed = total - pending;

            $('#progress_bar')
                .data('pending', pending)
                .data('total', total)
                .progressbar('value', (completed / total) * 100);
        }

        function remove_pending_request()
        {
            var pending = $('#progress_bar').data('pending') - 1,
            total = $('#progress_bar').data('total') + 1,
            completed = total - pending;

            $('#progress_bar')
                .data('pending', pending)
                .progressbar('value', (completed / total) * 100);

            if (pending < 1)
            {
                $('#progress_bar').fadeOut('slow', function()
                {
                    $('#save_all').show();
                });
            }
        }

        $('#item_container .entry-deleted').each(function(index, item)
        {
            if ($(item).hasClass('new-entry'))
            {
                $(item).remove();
                return;
            }
            delete_guids.push($(item).attr('id').slice(6));
        });

        if (delete_guids.length > 0)
        {
            fd = new FormData();
            xhr = new XMLHttpRequest();

            fd.append("guids", delete_guids.join('|'));
            fd.append("operation", 'delete');
            xhr.onreadystatechange = function()
            {
                if (xhr.readyState === 4)
                {
                    $('#item_container .entry-deleted').remove();
                }
                remove_pending_request();
            };
            xhr.open("POST", service_url);
            xhr.send(fd);
            add_pending_request();
        }

        $('#item_container .entry:not(.entry-template):not(.entry-deleted)').each(function(index, item)
        {
            if ($(item).hasClass('new-entry'))
            {
                create_entry(index, item);
            }
            else
            {
                update_entry(index, item);
            }
        });
        if ($('#progress_bar').data('total') === 0)
        {
            remove_pending_request();
        }
    });
});