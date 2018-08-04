$.widget( "custom.category_complete", $.ui.autocomplete,
{
    _create: function() {
        this._super();
        this.widget().menu( "option", "items", "> :not(.ui-autocomplete-category)" );
    },
    _renderMenu: function(ul, items) {
        var self = this,
        currentCategory = "";
        $.each(items, function(index, item) {
            if (item.category !== currentCategory) {
                ul.append( "<li class='ui-autocomplete-category'>" + item.category + "</li>" );
                currentCategory = item.category;
            }
            self._renderItemData(ul, item);
        });
    }
});

var midcom_helper_datamanager2_autocomplete = {
    get_default_options: function() {
        return {
            minLength: 2,
            source: midcom_helper_datamanager2_autocomplete.query,
            select: midcom_helper_datamanager2_autocomplete.select,
            position: {collision: 'flipfit'}
        };
    },
    query: function(request, response) {
        var identifier = $('.ui-autocomplete-loading').attr('id').replace(/_search_input$/, ''),
            query_options_var = identifier + '_handler_options',
            query_options = window[query_options_var],
            cache = $('.ui-autocomplete-loading').data('cache'),
            term = request.term;

        function filter_existing(data) {
            if ($('#' + identifier + '_selection_holder').length === 0) {
                return data;
            }
            var filtered = [];

            $.each(data, function(index, element) {
                if ($('#' + identifier + '_selection_holder span.autocomplete-selected[data-id="' + element.id + '"]').length === 0) {
                    filtered.push(element);
                }
            });

            return filtered;
        }

        if (cache === undefined) {
            cache = {};
        }
        if (term in cache) {
            response(filter_existing(cache[term]));
            return;
        }

        query_options.term = term;
        $.ajax({
            url: query_options.handler_url,
            dataType: "json",
            data: query_options,
            success: function(data) {
                if (!$.isEmptyObject(data)) {
                    cache[term] = data;
                }
                data = filter_existing(data);
                $('.ui-autocomplete-loading').data('cache', cache);
                response(data);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $('.ui-autocomplete-loading')
                    .addClass('ui-autocomplete-error')
                    .prop('title', errorThrown);
                response();
            }
        });
    },

    select: function(event, ui) {
        var identifier = $(event.target).attr('id').replace(/_search_input$/, '');

        if ($('#' + identifier + '_selection_holder').length > 0) {
            midcom_helper_datamanager2_autocomplete.update_selection(identifier, ui.item.id, 'add');
            midcom_helper_datamanager2_autocomplete.add_item(identifier, ui.item.id, ui.item.label, 'autocomplete-new');
            event.preventDefault();
        } else {
            $('#' + identifier + '_selection').val(JSON.stringify([ui.item.id]));
            $(event.target).data('selected', ui.item.label);
        }
    },

    open: function() {
        var offset = $(this).offset(),
        height = $(window).height() - (offset.top + $(this).height() + 10);
        $('ul.ui-autocomplete').css('maxHeight', height);
    },

    /**
     * Enable the creation mode
     */
    enable_creation_mode: function(identifier, creation_url) {
        var dialog_id = identifier + '_creation_dialog',
            handler_options = window[identifier + '_handler_options'],
            input = $('#' + identifier + '_search_input'),
            create_dialog = $('<div class="autocomplete_widget_creation_dialog" id="' + dialog_id + '"><div class="autocomplete_widget_creation_dialog_content_holder"></div></div>').insertAfter(input),
            create_button = $('<div class="autocomplete_widget_create_button" id="' + identifier + '_create_button"></div>').insertAfter(create_dialog);

        input.css({float: 'left'});

        create_button
            .button({
                icons: {
                    primary: 'ui-icon-plusthick'
                },
                text: false
            })
            .on('click', function() {
                var url = creation_url + '?chooser_widget_id=' + identifier;
                if (   $('#' + identifier + '_search_input').val() !== ''
                    && handler_options.creation_default_key !== undefined) {
                    url += '&defaults[' + handler_options.creation_default_key + ']=' + $('#' + identifier + '_search_input').val();
                }

                var iframe_html = '<iframe src="' + url + '" id="' + identifier + '_creation_dialog_content"'
                    + ' class="chooser_widget_creation_dialog_content"'
                    + ' frameborder="0"'
                    + ' marginwidth="0"'
                    + ' marginheight="0"'
                    + ' width="100%"'
                    + ' height="100%"'
                    + ' scrolling="auto" />';

                create_dialog
                    .html(iframe_html)
                    .dialog({
                        height: 350,
                        width: 500
                    });
            });
    },

    /**
     * Add creation result to form (from chooser-compatible data)
     */
    add_result_item: function(identifier, data) {
        var handler_options = window[identifier + '_handler_options'],
            input_value = '';

        $(handler_options.result_headers).each(function(index, value) {
            if (data[value.name] !== undefined) {
                input_value += data[value.name] + ', ';
            }
        });
        midcom_helper_datamanager2_autocomplete.update_selection(identifier, data[handler_options.id_field], 'add');
        midcom_helper_datamanager2_autocomplete.add_item(identifier, data[handler_options.id_field], input_value.replace(/, $/, ''), 'autocomplete-new');
    },

    create_dm2_widget: function(selector, min_length) {
        var identifier = selector.replace(/_search_input$/, ''),
            handler_options = window[identifier + '_handler_options'],
            dm2_defaults = {
                minLength: min_length,
                //Don't change input field during keyboard navigation:
                focus: function(event) {
                    event.preventDefault();
                }
            },
            options =  $.extend(dm2_defaults, midcom_helper_datamanager2_autocomplete.get_default_options()),
            input = $('#' + selector),
            readonly = (input.attr('type') === 'hidden') ? true : false,
            selection_holder_class = 'autocomplete-selection-holder';

        function remove_item(item) {
            var animate_property = 'height',
                animation_config = {};

            midcom_helper_datamanager2_autocomplete.update_selection(identifier, item.data('id'), 'remove');
            if (handler_options.allow_multiple !== true) {
                input.show().focus();
                if (handler_options.creation_mode_enabled) {
                    $('#' + identifier + '_create_button').show();
                }
            }
            if (item.hasClass('autocomplete-saved')) {
                item.removeClass('autocomplete-selected');
                item.addClass('autocomplete-todelete');
            } else {
                if (handler_options.allow_multiple !== true) {
                    item.remove();
                } else {
                    if (   item.next().length > 0
                        && item.offset().top === item.next().offset().top) {
                        animate_property = 'width';
                    }
                    animation_config[animate_property] = 0;
                    item
                        .css('visibility', 'hidden')
                        .find('.autocomplete-item-label')
                        .animate(animation_config, {duration: 200, complete: function(){item.remove();}});
                }
            }
        }

        if (readonly) {
            selection_holder_class += ' autocomplete-selection-holder-readonly';
        }

        input.parent()
            .append('<span class="' + selection_holder_class + '" id="' + identifier + '_selection_holder"></span>')
            .addClass('autocomplete-widget');
        if (handler_options.creation_mode_enabled) {
            midcom_helper_datamanager2_autocomplete.enable_creation_mode(identifier, handler_options.creation_handler);
            input.parent().addClass('autocomplete-widget-creation-enabled');
        }
        if (!$.isEmptyObject(handler_options.preset)) {
            $.each(handler_options.preset_order, function(key, id) {
                var text = handler_options.preset[id];
                if (handler_options.id_field === 'id') {
                    id = parseInt(id);
                }
                midcom_helper_datamanager2_autocomplete.add_item(identifier, id, text, 'autocomplete-saved');
                if (input.is('[required]')) {
                    input.prop('required', false);
                    input.data('required', true)
                }
            });
        }
        if (readonly) {
            return;
        }

        if (handler_options.categorize_by_parent_label !== false) {
            input.category_complete(options);
        } else {
            input.autocomplete(options);
        }

        input.parent().on('click', '.autocomplete-selection-holder .autocomplete-action-icon', function() {
            var item = $(this).parent(),
                item_id = item.data('id');

            if (item.hasClass('autocomplete-selected')) {
                remove_item(item);
            } else if (item.hasClass('autocomplete-todelete')) {
                midcom_helper_datamanager2_autocomplete.update_selection(identifier, item_id, 'add');
                midcom_helper_datamanager2_autocomplete.restore_item(identifier, item);
            } else {
                midcom_helper_datamanager2_autocomplete.hide_input(identifier, true);
            }
        });

        if (handler_options.sortable === true) {
            $("#" + identifier + "_selection_holder").sortable({
                items: "> span.autocomplete-item",
                placeholder: 'autocomplete-placeholder',
                forcePlaceholderSize: true,
                update: function() {
                    var result = [];
                    $("#" + identifier + "_selection_holder .autocomplete-item:not(.autocomplete-todelete)")
                        .each(function() {
                            result.push($(this).data("id"));
                        });
                    $("#" + identifier + "_selection").val(JSON.stringify(result));
                }
            });

            $("#" + identifier + "_search_input").on("autocompleteselect", function() {
                $("#" + identifier + "_selection_holder").sortable("refresh");
            });
        }
    },

    restore_item: function(identifier, item) {
        midcom_helper_datamanager2_autocomplete.hide_input(identifier, true);

        item.removeClass('autocomplete-todelete');
        item.addClass('autocomplete-selected');
    },

    hide_input: function(identifier, switch_focus) {
        var handler_options = window[identifier + '_handler_options'];

        if (handler_options.allow_multiple !== true) {
            $('#' + identifier + '_search_input').hide();
            if (handler_options.creation_mode_enabled) {
                $('#' + identifier + '_create_button').hide();
            }
            $('#' + identifier + '_selection_holder').find('.autocomplete-new').remove();
            if (switch_focus === true) {
                $('#' + identifier + '_search_input').closest('.form .element').nextAll().find(':focusable:visible').first().focus();
            }
        }
    },

    add_item: function(identifier, item_id, text, status) {
        var selection_holder = $('#' + identifier + '_selection_holder'),
            existing_item = selection_holder.find('[data-id="' + item_id + '"]'),
            selected = midcom_helper_datamanager2_autocomplete.is_selected(identifier, item_id),
            item;

        if (existing_item.length === 0) {
            midcom_helper_datamanager2_autocomplete.hide_input(identifier, status !== 'autocomplete-saved');

            status = (selected === true ? 'selected ' : 'todelete ') + status;
            item = $('<span class="autocomplete-item autocomplete-' + status + '" data-id="' + item_id + '"><span class="autocomplete-item-label" title="' + text + '">' + text + '</span></span>');
            if (!selection_holder.hasClass('autocomplete-selection-holder-readonly')) {
                item.append('<span class="autocomplete-action-icon"><i class="fa fa-check"></i><i class="fa fa-plus"></i><i class="fa fa-trash"></i></span>');
            }
            item.prependTo(selection_holder);
        } else if (existing_item.hasClass('autocomplete-todelete')) {
            midcom_helper_datamanager2_autocomplete.restore_item(identifier, existing_item);
        }
    },
    is_selected: function(identifier, item_id) {
        var selection = JSON.parse($('#' + identifier + '_selection').val());
        return ($.inArray(item_id, selection) !== -1);
    },
    update_selection: function(identifier, item_id, operation) {
        var selection = JSON.parse($('#' + identifier + '_selection').val()),
            new_selection = [],
            handler_options = window[identifier + '_handler_options'],
            input = $('#' + identifier + '_search_input');

        if (operation === 'add') {
            if (handler_options.allow_multiple !== true) {
                new_selection.push(item_id);
            } else {
                new_selection = selection;
                if ($.inArray(item_id, new_selection) === -1) {
                    new_selection.push(item_id);
                }
            }
            if (input.data('required')) {
                input.prop('required', false);
            }
        } else {
            $.each(selection, function(index, item) {
                if (String(item) !== String(item_id)) {
                    new_selection.push(item);
                }
            });
            if (input.data('required') && new_selection.length === 0) {
                input.prop('required', true);
            }
        }
        $('#' + identifier + '_selection').val(JSON.stringify(new_selection));
    },

    /**
     * Generate and attach HTML for autocomplete widget (for use outside of DM2)
     */
    create_widget: function(config, autocomplete_options)
    {
        var default_config = {
                id_field: 'guid',
                auto_wildcards: 'both',
                categorize_by_parent_label: false,
                placeholder: '',
                default_value: '',
                default_text: ''
            },
            default_value = config.default_value || default_config.default_value,
            default_text = config.default_text || default_config.default_text,
            placeholder = config.placeholder || default_config.placeholder,
            widget_html = '<input type="text" id="' + config.id + '_search_input" name="' + config.id + '_search_input" style="display: none" class="batch_widget" placeholder="' + placeholder + '" value="' + default_text + '" />';

        widget_html += '<input type="hidden" id="' + config.id + '_selection" name="' + config.id + '_selection" value="' + default_value + '" />';
        autocomplete_options = $.extend({autoFocus: true}, midcom_helper_datamanager2_autocomplete.get_default_options(), autocomplete_options || {});
        window[config.id + '_handler_options'] = $.extend({}, default_config, config.widget_config);

        if (config.insertAfter !== undefined) {
            $(widget_html).insertAfter($(config.insertAfter));
        } else if (config.appendTo !== undefined) {
            $(widget_html).appendTo($(config.appendTo));
        }

        if (window[config.id + '_handler_options'].categorize_by_parent_label === true) {
            $('#' + config.id + '_search_input').category_complete(autocomplete_options);
        } else {
            $('#' + config.id + '_search_input').autocomplete(autocomplete_options);
        }
    }
};
