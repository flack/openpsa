$.fn.extend({
    midcom_services_toolbar: function(options, items) {
        return new $.midcom_services_toolbars(this, options, items);
    },
    mst_add_item: function(data) {
        return this.trigger("add_item",[data]);
    },
    mst_read_item: function(identifier, options) {
        return this.trigger("read_item",[identifier, options]);
    },
    mst_remove_item: function(object_or_id, options) {
        return this.trigger("remove_item",[object_or_id, options]);
    },
    mst_hide: function() {
        return this.trigger("self_hide",[]);
    },
    mst_show: function() {
        return this.trigger("self_show",[]);
    }
});

$.midcom_services_toolbars = function(root_element) {
    if (root_element.length === 0) {
        return;
    }

    var minimizer = $('#midcom_services_toolbars_minimizer'),
        default_position = get_default_position(root_element),
        memorized_position = get_memorized_position(),
        visible = true,
        posX = default_position.x,
        posY = default_position.y;

    if (minimizer.length === 0) {
        minimizer = $('<div>')
            .attr('id', 'midcom_services_toolbars_minimizer')
            .prependTo($('body'));
    }

    if (memorized_position != null) {
        posX = (memorized_position.x != '' && memorized_position.x != undefined ? memorized_position.x : default_position.x);
        posY = (memorized_position.y != '' && memorized_position.y != undefined ? memorized_position.y : default_position.y);
        visible = memorized_position.visible;
    }

    if (parseInt(posY) === 0) {
        root_element.addClass('type_menu');
    } else {
        root_element.addClass('type_palette');

        if (Math.ceil(posX) + root_element.width() > $(window).width()) {
            posX = $(window).width() - (root_element.width() + 4);
        }

        root_element.css({ left: posX + 'px', top: posY + 'px', width: (root_element.width() + 25) + 'px'});
    }

    attach_events();

    root_element.css({ cursor: 'default' });

    if (visible === true) {
        root_element.show();
        minimizer.addClass('toolbar-visible');
    } else {
        minimizer.addClass('toolbar-hidden');
    }

    function attach_events() {
        var item_holder = $('div.items', root_element);

        $('div.item', item_holder).each(function(i, n) {
            var item = $(n),
                handle = $('.midcom_services_toolbars_topic_title', item),
                children = $('ul', item);

            item.on('mouseover', function() {
                clearTimeout($(item_holder).data("hide"));
                $('.item ul', item_holder).hide();
                $('.midcom_services_toolbars_topic_title.hover', item_holder).removeClass("hover");
                handle.addClass("hover");
                children.show();
            });
            item.on('mouseout',function() {
                $(item_holder).data('hide', setTimeout(function() {
                    handle.removeClass("hover");
                    children.hide();
                }, 1000));
            });
        });

        minimizer.on('click', toggle_visibility);
        root_element.find('.minimizer').on('click', toggle_visibility);

        root_element.draggable({
            start: function() {
                if (root_element.hasClass('type_menu')) {
                    root_element
                        .removeClass('type_menu')
                        .addClass('type_palette switch_to_palette');
                }
            },
            drag: function(event, ui) {
                if (root_element.hasClass('switch_to_palette')) {
                    root_element.removeClass('switch_to_palette');
                    //TODO: this should work, but doesn't...
                    ui.position.left = $(window).width() - root_element.width();
                } else if (ui.position.top === 0) {
                    root_element
                        .removeClass('type_palette')
                        .addClass('type_menu');
                }
            },
            stop: function() {
                save_settings(true);
            },
            containment: 'window',
            cancel: '.items ul'
        });
    }

    function toggle_visibility(e) {
        if (minimizer.hasClass('toolbar-visible')) {
            minimizer
                .removeClass('toolbar-visible')
                .addClass('toolbar-hidden');
            root_element.hide();
        } else {
            minimizer
                .removeClass('toolbar-hidden')
                .addClass('toolbar-visible');
            root_element.show();
        }
        e.stopPropagation();
        save_settings(false);
    }

    function save_settings(save_position) {
        if (window.localStorage === undefined || !window.localStorage) {
            return false;
        }
        window.localStorage.setItem('midcom_services_toolbars_visible', (root_element.is(':visible')) ? 'true' : 'false');

        if (save_position) {
            var new_pos = root_element.position();
            window.localStorage.setItem('midcom_services_toolbars_x', new_pos.left);
            window.localStorage.setItem('midcom_services_toolbars_y', new_pos.top);
        }
    }

    function get_memorized_position() {
        if (window.localStorage === undefined || !window.localStorage) {
            return false;
        }
        return {
            visible: window.localStorage.getItem('midcom_services_toolbars_visible') === 'true',
            x: window.localStorage.getItem('midcom_services_toolbars_x'),
            y: window.localStorage.getItem('midcom_services_toolbars_y')
        };
    }

    function get_default_position(re) {
        var x = 20,
            y = 20,
            dw = $(document).width(),
            ew = $(re).width();

        if (ew == 0) {
            return {x: 0, y: 0};
        }

        var left = (dw / 2) - ew / 2;
        x = left;

        return {x: x, y: y};
    }
};
