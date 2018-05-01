$(document).ready(function() {
    $('body').on('click', '[data-dialog="delete"]', function(event) {
        event.preventDefault();
        var button = $(this),
            dialog = $('<div class="midcom-delete-dialog">'),
            spinner = $('<div class="spinner"><i class="fa fa-pulse fa-spinner"></i></div>');
            text = button.data('dialog-text');
            action = button.attr('href') || button.data('action'),
            options = {
                title:  button.data('dialog-heading'),
                modal: true,
                width: 'auto',
                maxHeight: $(window).height(),
                buttons: [{
                    text: button.text().trim() || button.data('dialog-heading'),
                    click: function() {
                        $('<form action="' + action + '" method="post" class="midcom-dialog-delete-form">')
                            .append($('<input type="submit" name="' + button.data('form-id') + '">'))
                            .append($('<input type="hidden" name="referrer" value="' + location.pathname + '">'))
                            .hide()
                            .prependTo('body');
                        $('input[name="' + button.data('form-id') + '"]').click();
                    }
                },
                {
                    text: button.data('dialog-cancel-label'),
                    click: function() {
                        $(this).dialog("close");
                    }
                }]
            };

        if ($('.midcom-delete-dialog').length > 0) {
            $('.midcom-delete-dialog').remove();
        }

        if (button.data('recursive')) {
            dialog.addClass('loading');
            options.buttons[0].disabled = true;
            $.getJSON(MIDCOM_PAGE_PREFIX + 'midcom-exec-midcom.helper.reflector/list-children.php',
                      {guid: button.data('guid')},
                      function (data) {
                          function render(items) {
                              var output = '';
                              $.each(items, function(i, item) {
                                  output += '<li class="leaf ' + item['class'] + '">' + item.icon + ' ' + item.title;
                                  if (item.children) {
                                      output += '<ul class="folder_list">';
                                      output += render(item.children);
                                      output += '</ul>';
                                  }
                                  output += '</li>';
                              });
                              return output;
                          }

                          if (data.length > 0) {
                              $('<ul class="folder_list">')
                                  .append($(render(data)))
                                  .appendTo($('#delete-child-list'));
                          } else {
                              dialog.find('p.warning').hide();
                          }
                          options.buttons[0].disabled = false;

                          dialog
                              .removeClass('loading')
                              .dialog('option', 'position', dialog.dialog('option', 'position'))
                              .dialog('option', 'buttons', options.buttons)
                              .focus();
                      });
        } else {
            text = '<p>' + text + '</p>';
        }

        dialog
            .css('min-width', '300px') // This should be handled by dialog's minWidth option, but that doesn't work with width: "auto"
                                       // Should be fixed in https://github.com/jquery/jquery-ui/commit/643b80c6070e2eba700a09a5b7b9717ea7551005
            .append($(text))
            .append(spinner)
            .appendTo($('body'))
            .dialog(options);
    });

    $('body').on('click', '[data-dialog="dialog"]', function(event) {
        event.preventDefault();
        if (!$(this).hasClass('active')) {
            create_dialog($(this), $(this).find('.toolbar_label').text() || $(this).attr('title') || '', $(this).attr('href'));
        }
    });

    $('body').on('click', '[data-dialog="confirm"]', function(event) {
        event.preventDefault();
        var button = $(this),
            dialog = $('<div class="midcom-confirm-dialog">'),
            options = {
                title:  button.data('dialog-heading'),
                modal: true,
                width: 'auto',
                maxHeight: $(window).height(),
                buttons: [{
                    text: button.data('dialog-confirm-label'),
                    click: function() {
                        button.closest('form').submit();
                    }
                },
                {
                    text: button.data('dialog-cancel-label'),
                    click: function() {
                        $(this).dialog("close");
                    }
                }]
            };

        if ($('.midcom-confirm-dialog').length > 0) {
            $('.midcom-confirm-dialog').remove();
        }

        dialog
            .css('min-width', '300px') // This should be handled by dialog's minWidth option, but that doesn't work with width: "auto"
                                       // Should be fixed in https://github.com/jquery/jquery-ui/commit/643b80c6070e2eba700a09a5b7b9717ea7551005
            .append($('<p>' + button.data('dialog-text') + '</p>'))
            .appendTo($('body'))
            .dialog(options);
    });
});

function create_dialog(control, title, url) {
    if ($('.midcom-workflow-dialog').length > 0) {
        $('.midcom-workflow-dialog .ui-dialog-content').dialog('close');
    }

    var dialog, iframe, spinner
        config = {
            dialogClass: 'midcom-workflow-dialog',
            buttons: [],
            title: title,
            height:  590,
            width: 800,
            close: function() {
                control.removeClass('active');
                iframe.css('visibility', 'hidden');
                if (iframe[0].contentWindow) {
                    iframe[0].contentWindow.stop();
                }
            },
            open: function() {
                dialog.closest('.ui-dialog').focus();
            }};

    if (control.data('dialog-cancel-label')) {
        config.buttons.push({
            text: control.data('dialog-cancel-label'),
            click: function() {
                $(this).dialog( "close" );
            }
        });
    }

    if ($('#midcom-dialog').length > 0) {
        dialog = $('#midcom-dialog');
        iframe = dialog.find('> iframe');
        spinner = dialog.find('> i').show();
        config.height = dialog.dialog('option', 'height');
        config.width = dialog.dialog('option', 'width');
        if (   config.width > window.innerWidth
            || config.height > window.innerHeight) {
            config.position = { my: "center", at: "center", of: window, collision: 'flipfit' };
        }
    } else {
        spinner = $('<i class="fa fa-pulse fa-spinner"></i>');
        iframe = $('<iframe name="datamanager-dialog"'
                   + ' frameborder="0"'
                   + ' marginwidth="0"'
                   + ' marginheight="0"'
                   + ' width="100%"'
                   + ' height="100%"'
                   + ' scrolling="auto" />')
            .on('load', function() {
                $(this).css('visibility', 'visible');
                spinner.hide();
            });

        dialog = $('<div id="midcom-dialog"></div>')
            .append(spinner)
            .append(iframe)
            .insertAfter(control);
    }

    config.height = Math.min(config.height, window.innerHeight);
    config.width = Math.min(config.width, window.innerHeight);

    if (url) {
        iframe.attr('src', url);
    }

    control.addClass('active');
    if (   control.parent().attr('role') === 'gridcell'
        && control.closest('.jqgrow').hasClass('ui-state-highlight') === false) {
        //todo: find out why the click doesn't bubble automatically
        control.parent().trigger('click');
    }
    dialog
        .on('dialogcreate', function() {
            var maximized = false,
                saved_options = {};
            $(this).prevAll('.ui-dialog-titlebar').on('dblclick', function() {
                if (!maximized) {
                    saved_options.position = dialog.dialog('option', 'position');
                    saved_options.width = dialog.dialog('option', 'width');
                    saved_options.height = dialog.dialog('option', 'height');
                    dialog.dialog('option', {
                        width: '99%',
                        height: $(window).height(),
                        position: {my: 'center top', at: 'center top', of: window}
                    });
                    maximized = true;
                } else {
                    dialog.dialog('option', {
                        height: saved_options.height,
                        width: saved_options.width,
                        position: saved_options.position
                    });
                    maximized = false;
                }
            });
        })
        .dialog(config)
        .dialog("instance").uiDialog.draggable("option", "containment", false);
}
