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

$.midcom_services_toolbars = function(root, settings) {
    settings = $.extend({
        visible: true,
        create_root: false,
        class_name: 'midcom_services_toolbars_fancy',
        allow_auto_create: false
    }, settings);

    var root_element = null,
    item_holder = null,
    memorized_position = null;

    if (settings.create_root)
    {
        root_element = create_root();
    }
    else
    {
        if (root[0])
        {
            settings.class_name = root.attr('class');
            root_element = root;
            item_holder = $('div.items', root_element);
        }
        else
        {
            if (!settings.allow_auto_create)
            {
                return;
            }
            root_element = create_root();
        }
    }

    if (document.cookie)
    {
        var cookie_array = document.cookie.split(';');
        for (var i = 0; i < cookie_array.length; i++)
        {
            if (cookie_array[i].match(/^\s?midcom_services_toolbars_position=/))
            {
                var pos = cookie_array[i].replace(/^\s?midcom_services_toolbars_position=/, '');
                memorized_position = {};
                memorized_position.x = pos.split('_')[0];
                memorized_position.y = pos.split('_')[1];
                break;
            }
        }
    }

    // Fallback uses PHP API for storing the toolbar information
    if (!memorized_position)
    {
        $.get(MIDCOM_PAGE_PREFIX + 'midcom-exec-midcom/toolbar.php', function(raw_memory_data)
        {
            var regs = raw_memory_data.match(/^([0-9]+),([0-9]+)$/);
            if (   !regs
                || !regs[1]
                || !regs[2])
            {
                return null;
            }

            // Prevent the toolbar from going off the viewing window, otherwise it will
            // always remain out of reach
            if (Number(regs[1]) > $(window).width())
            {
                regs[1] = Number(regs[1]) - $(window).width();
            }

            if (Number(regs[2]) > $(window).height())
            {
                regs[2] = Number(regs[2]) - $(window).height();
            }

            root_element.css({ left: regs[1] + 'px', top: regs[2] + 'px' });
        });
    }


    var default_position = get_default_position(root_element),
    posX = default_position.x,
    posY = default_position.y;

    if (memorized_position != null)
    {
        posX = (memorized_position.x != '' && memorized_position.x != undefined ? memorized_position.x : default_position.x);
        posY = (memorized_position.y != '' && memorized_position.y != undefined ? memorized_position.y : default_position.y);
    }
    else
    {
        $.get(
            MIDCOM_PAGE_PREFIX + 'midcom-exec-midcom/toolbar.php',
            {
                'position_x': default_position.x,
                'position_y': default_position.y
            }
        );
    }

    enable_toolbar();

    function create_root(target)
    {
        target = target || $('body');
        var root = $('<div>')
            .addClass(settings.class_name)
            .appendTo(target)
            .hide()
            .css({ zIndex: 6001 }),
        item_holder = $('<div>').addClass('items');

        $(root).append(item_holder);

        $(root).append(
            $('<div>').addClass('dragbar')
        );

        return root;
    }

    function enable_toolbar()
    {
        if (parseInt(posY) === 0)
        {
            root_element.addClass('type_menu');
        }
        else
        {
            root_element.addClass('type_palette');

            if (Math.ceil(posX) + root_element.width() > $(window).width())
            {
                posX = $(window).width() - (root_element.width() + 4);
            }

            root_element.css({ left: posX + 'px', top: posY + 'px', width: (root_element.width() + 25) + 'px'});
        }

        $('div.item', item_holder).each(function(i, n){
            var item = $(n),
            handle = $('.midcom_services_toolbars_topic_title', item),
            children = $('ul',item);

            item.bind('mouseover',function(e){
                $(item_holder).stopTime("hide");
                $('.item ul', item_holder).hide();
                $('.midcom_services_toolbars_topic_title.hover', item_holder).removeClass("hover");
                handle.addClass("hover");
                children.show();
            });
            item.bind('mouseout',function(e){
                $(item_holder).oneTime(1000, "hide", function() {
                handle.removeClass("hover");
                children.hide();
                });
            });
        });

        root_element.draggable({
            start: function(event, ui)
            {
                if (root_element.hasClass('type_menu'))
                {
                    root_element
                        .removeClass('type_menu')
                        .addClass('type_palette switch_to_palette');
                }
            },
            drag: function(event, ui)
            {
                if (root_element.hasClass('switch_to_palette'))
                {
                    root_element.removeClass('switch_to_palette');
                    //TODO: this should work, but doesn't...
                    ui.position.left = $(window).width() - root_element.width();
                }
                else if (ui.position.top === 0)
                {
                    root_element
                        .removeClass('type_palette')
                        .addClass('type_menu');
                }
            },
            stop: function(e)
            {
                save_position(e);
            },
            containment: 'window'
        });
        root_element.css({ cursor: 'default' });
        root_element.show();
    }

    function save_position(event)
    {
        var new_pos = root_element.position();

        $.get(
            MIDCOM_PAGE_PREFIX + 'midcom-exec-midcom/toolbar.php',
            {
                'position_x': new_pos.left,
                'position_y': new_pos.top
            }
        );
    }

    function get_default_position(re)
    {
        var x = 20,
        y = 20,
        dw = $(document).width(),
        ew = $(re).width();

        if (ew == 0)
        {
            return {x: 0, y: 0};
        }

        var left = (dw / 2) - ew / 2;
        x = left;

        return {x: x, y: y};
    }
}