$(document).ready(function() {
    $('#reverse').on('click', function() {
        var entries = $('#item_container > div').not('.entry-template').detach();
        $('#item_container').prepend($(entries.get().reverse()));
    });
    $('.existing-entry').each(function(index, item) {
        $(item).data('saved_values', {
            position: index,
            title: $(item).find('.title input').val(),
            description: $(item).find('.description textarea').val()
        });
    });
    $('#upload_field').on('change', function() {
        var image, entry, reader,
            entry_template = $('#item_container .entry-template')[0];

        $.each(this.files, function(index, file) {
            if (!file.type.match(/image.*/)) {
                // this file is not an image. TODO: Report an error?
                return;
            }

            image = document.createElement('img');
            image.file = file;
            reader = new FileReader();
            reader.onload = (function(img) { return function(e) {img.src = e.target.result;};})(image);
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
        .on('click', '.image-delete', function() {
            var entry = $(this).closest('.entry');
            if (entry.hasClass('new-entry')) {
                entry.remove();
            } else {
                entry.addClass('entry-deleted');
            }
        })
        .on('click', '.image-cancel-delete', function() {
            $(this).closest('.entry').removeClass('entry-deleted');
        })
        .on('click', '.entry', function() {
            var viewer = $('#entry-viewer');

            if ($(this).find('.thumbnail img').data('originalUrl')) {
                viewer.find('.image').html('<img src="' + $(this).find('.thumbnail img').data('originalUrl') + '" />');
            } else {
                viewer.find('.image').html($(this).find('.thumbnail img').clone());
            }
            viewer.find('.title input')
                .val($(this).find('.title input').val());
            viewer.find('.description textarea').val($(this).find('.description textarea').val());
            viewer.find('.filename').text($(this).find('.filename').text());
            if (!viewer.hasClass('active')) {
                viewer.addClass('active');
            }
            viewer.data('active', $(this).attr('id'));
            $('.entry.active').removeClass('active');
            $(this).addClass('active');
        })
        .sortable();

    $('#entry-viewer').on('blur', '.title input, .description textarea', function() {
        var viewer = $('#entry-viewer'),
        active = viewer.data('active');
        if (active !== undefined) {
            $('#' + active).find('.title input').val(viewer.find('.title input').val());
            $('#' + active).find('.description textarea').val(viewer.find('.description textarea').val());
        }
    });

    $('#save_all').on('click', function() {
        var delete_guids = [],
            update_items = [],
            fd, xhr,
            label = $('#progress_bar .progress-label'),
            progressbar = $('#progress_bar'),
            progress_dialog = $('#progress_dialog'),
            pending_requests = [];

        $('#progress_total').text('0');
        $('#progress_completed').text('0');
        $('#progress_filesize_total').text('');

        progressbar
            .data('pending', 0)
            .data('filesize', 0)
            .data('total', 0);

        function close_dialog() {
            if (progressbar.data('pending') < 1) {
                progress_dialog.dialog('close');
            }
        }

        function create_entry(index, item) {
            var file =  $(item).find('.thumbnail img')[0].file,
                xhr = new XMLHttpRequest(),
                fd = new FormData();

            // todo: This has to be supported by server side
            xhr.upload.addEventListener("progress", function(e) {
                if (e.lengthComputable) {
                    var delta = e.loaded - $(item).data('completed'),
                    completed = $('#progress_bar').data('filesize_completed') + delta;
                }
            }, false);

            fd.append("title", $(item).find('.title input').val());
            fd.append("description", $(item).find('.description textarea').val());
            fd.append('image', file);
            fd.append("position", index);
            fd.append("operation", 'create');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    try {
                        var reply = JSON.parse(xhr.responseText);
                        if (!reply.success) {
                            $.midcom_services_uimessage_add({type: 'error', message: reply.error, title: reply.title});
                        } else {
                            $(item)
                                .removeClass('new-entry')
                                .addClass('existing-entry')
                                .attr('id', 'image-' + reply.guid)
                                .data('saved_values', {
                                    position: reply.position,
                                    title: $(item).find('.title input').val(),
                                    description: $(item).find('.description textarea').val()
                                })
                                .find('.filename').text(reply.filename);
                        }
                    } catch (e) {
                        $.midcom_services_uimessage_add({type: 'error', message: e.message, title: e.name});
                    }
                    remove_pending_request();
                }
            };
            add_pending_request(xhr, fd);
            $('#progress_bar').data('filesize', $('#progress_bar').data('filesize') + file.size);

            function format_filesize(size) {
                var i = 0,
                    units = ['B', 'KB', 'MB', 'GB'];

                while (size > 1024) {
                    size = size / 1024;
                    i++;
                }

                return Math.max(size, 0.1).toFixed(1) + ' ' + units[i];
            }

            $('#progress_filesize_total').text(format_filesize($('#progress_bar').data('filesize')));
        }

        function update_entry(index, item) {
            var entry = {},
                title = $(item).find('.title input').val(),
                description = $(item).find('.description textarea').val(),
                saved_values = $(item).data('saved_values');

            if (   title === saved_values.title
                && description === saved_values.description
                && index === saved_values.position) {
                return;
            }

            entry.title = title;
            entry.description = description;
            entry.position = index;
            entry.guid = $(item).attr('id').slice(6);

            update_items.push(entry);
        }

        function add_pending_request(xhr, fd) {
            var pending = $('#progress_bar').data('pending') + 1,
                total = $('#progress_bar').data('total') + 1;

            $('#progress_bar')
                .data('pending', pending)
                .data('total', total);
            $('#progress_total').text(total);

            xhr.open("POST", window.location.href + 'ajax/');
            pending_requests.push({xhr: xhr, fd: fd});
        }

        function remove_pending_request() {
            var pending = $('#progress_bar').data('pending') - 1,
                total = $('#progress_bar').data('total'),
                completed = total - pending;

            $('#progress_bar')
                .data('pending', pending)
                .progressbar('value', Math.round((completed / total) * 100));
            $('#progress_completed').text(completed);
        }

        function process_pending_requests() {
            pending_requests.forEach(function(request) {
                request.xhr.send(request.fd);
            });
        }

        function process_update_request() {
            var xhr = new XMLHttpRequest(),
                fd = new FormData();

            fd.append("items", JSON.stringify(update_items));
            fd.append("operation", 'batch_update');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    remove_pending_request();
                    update_items.forEach(function(item) {
                        $('#image-' + item.guid).data('saved_values', {
                            position: item.position,
                            title: item.title,
                            description: item.description
                        });
                    });
                    update_items = [];
                }
            };

            add_pending_request(xhr, fd);
        }

        $('#item_container .entry-deleted').each(function(index, item) {
            if ($(item).hasClass('new-entry')) {
                $(item).remove();
                return;
            }
            delete_guids.push($(item).attr('id').slice(6));
        });

        if (delete_guids.length > 0) {
            fd = new FormData();
            xhr = new XMLHttpRequest();

            fd.append("guids", delete_guids.join('|'));
            fd.append("operation", 'delete');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    $('#item_container .entry-deleted').remove();
                    remove_pending_request();
                }
            };
            add_pending_request(xhr, fd);
        }

        $('#item_container .entry:not(.entry-template):not(.entry-deleted)').each(function(index, item) {
            if ($(item).hasClass('new-entry')) {
                create_entry(index, item);
            } else {
                update_entry(index, item);
            }
        });
        if (update_items.length > 0) {
            process_update_request();
        }

        progressbar
            .progressbar({
                value: false,
                change: function() {
                    if (progressbar.progressbar('value') !== false) {
                        label.text(progressbar.progressbar('value') + '%');
                    }
                },
                complete: function() {
                    window.setTimeout(close_dialog, 1000);
                }
            });
        progress_dialog.dialog({
            autoOpen: true,
            modal: true,
            open: function() {
                process_pending_requests();
            },
            close: function() {
                progressbar.progressbar('value', false);
                label.text('');
            }
        });
    });
});
