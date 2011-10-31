var MIDCOM_SERVICES_TOOLBARS_TYPE_MENU = 'menu';
var MIDCOM_SERVICES_TOOLBARS_TYPE_PALETTE = 'palette';

var memorized_position = null;

jQuery.fn.extend({
	midcom_services_toolbar: function(options, items) {
	    return new jQuery.midcom_services_toolbars(this, options, items);
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

jQuery.midcom_services_toolbars = function(root, settings, with_items) {
    settings = jQuery.extend({
        type: MIDCOM_SERVICES_TOOLBARS_TYPE_PALETTE,
        type_config: {},
        visible: true,
        create_root: false,
        debug: false,
        class_name: 'midcom_services_toolbars_fancy',
        show_logos: true,
        allow_auto_create: false
    }, settings);
    
    debug('Initializing', 'info');

    var default_logo = {
            title: 'Midgard',
            href: '/midcom-exec-midcom/about.php',
            target: '_blank',
            src: 'images/midgard-logo.png',
            width: '16',
            height: '16'
    };
    var all_logos = Array
    (
        default_logo
    );
    var logo_tpl = function() {
        return [
            'a', { href: this.href, title: this.title, target: this.target }, [
                'img', { src: this.src, width: this.width, height: this.height }, ''
            ]
        ];
    };
    
    var root_element = null;
    var item_holder = null;

    var type_configs = Array();
    type_configs[MIDCOM_SERVICES_TOOLBARS_TYPE_MENU] = {
        height: 25,
        width: 0,
        draggable: false
    };
    type_configs[MIDCOM_SERVICES_TOOLBARS_TYPE_PALETTE] = {
        height: 20,
        draggable: true
    };
    type_configs[settings.type] = jQuery.extend(type_configs[settings.type], settings.type_config);
    
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
            item_holder = jQuery('div.items', root_element);
        }
        else
        {
            if (settings.allow_auto_create)
            {
                root_element = create_root();
            }
            else
            {
                return;
            }
        }
    }

    debug('root_element: '+root_element, 'info');
    
    var menu_items = with_items || Array();

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
        jQuery.get(MIDCOM_PAGE_PREFIX + 'midcom-exec-midcom/toolbar.php', function(raw_memory_data)
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
            if (Number(regs[1]) > jQuery(window).width())
            {
                regs[1] = Number(regs[1]) - jQuery(window).width();
            }
            
            if (Number(regs[2]) > jQuery(window).height())
            {
                regs[2] = Number(regs[2]) - jQuery(window).height();
            }
            
            root_element.css({ left: regs[1] + 'px', top: regs[2] + 'px' });
        });
    }
    
    
    var default_position = get_default_position(root_element),
    posX = default_position.x + 'px',
    posY = default_position.y + 'px';

    if (memorized_position != null)
    {
        debug("memorized_position.x: " + memorized_position.x);
        debug("memorized_position.y: " + memorized_position.y);
        posX = (memorized_position.x != '' && memorized_position.x != undefined ? memorized_position.x : default_position.x) + 'px';
        posY = (memorized_position.y != '' && memorized_position.y != undefined ? memorized_position.y : default_position.y) + 'px';
    }
    else
    {
        jQuery.get(
            MIDCOM_PAGE_PREFIX + 'midcom-exec-midcom/toolbar.php',
            {
                'position_x': default_position.x,
                'position_y': default_position.y
            }
        );
    }
    
    debug('posX: ' + posX);
    debug('posY: ' + posY);
    debug('Initializing Finished', 'info');
    
    enable_toolbar();
    
    function create_root(target)
    {
        debug('create_root start', 'info');
        
        var target = target || jQuery('body');
        
        debug("target: "+target);
        
        var root_class = settings.class_name + ' type_' + settings.type;

        var root = jQuery(target).createAppend(
            'div', { className: root_class }, []
        ).hide().css({ zIndex: 6001, height: type_configs[settings.type].height });
        
        if (settings.show_logos)
        {
            var logo_holder = jQuery(root).createAppend(
                'div', { className: 'logos' }, ''
            );
            for (var i=0; i<all_logos.length;i++)
            {
                var logo = all_logos[i];
                jQuery(logo_holder).tplAppend(logo, logo_tpl);
            }
        }
        
        item_holder = jQuery('<div>').addClass('items');

        jQuery(root).append(item_holder);
        
        if (   type_configs[settings.type].draggable)
        {
            jQuery(root).append(
                jQuery('<div>').addClass('dragbar')
            );
        }
        
        debug('create_root fnished', 'info');
        
        return root;
    }
    
    function enable_toolbar()
    {
        debug('enable_toolbar start', 'info');
        if (type_configs[settings.type].width > 0)
        {
            root_element.css({ width: type_configs[settings.type].width });
        }        
        root_element.css({ left: posX, top: posY });
        
        // if (jQuery.browser.safari)
        // {
        //     root_element.css({ position: 'fixed' });
        // }
        // if (jQuery.browser.ie)
        // {
        root_element.css({ position: 'absolute' });
        // }
        
        jQuery('div.item', item_holder).each(function(i,n){
            debug("i: "+i+" n: "+n);
            var item = jQuery(n);
            var handle = jQuery('.midcom_services_toolbars_topic_title',item);
            var children = jQuery('ul',item);
            
            if (jQuery.browser.ie)
            {
                jQuery('li', children).css({ width: '9em' });
            }
            
            item.bind('mouseover',function(e){
                jQuery(item_holder).stopTime("hide");
                jQuery('.item ul', item_holder).hide();
                jQuery('.midcom_services_toolbars_topic_title.hover', item_holder).removeClass("hover");
                handle.addClass("hover");
                children.show();
            });
            item.bind('mouseout',function(e){
                jQuery(item_holder).oneTime(1000, "hide", function() {
                handle.removeClass("hover");
                children.hide();
                });
            });
            
        });

        if (   type_configs[settings.type].draggable)
        {
            root_element.draggable({
                stop: function(e){save_position(e);},
                handle: '.dragbar'
            });
            root_element.css({ cursor: 'default' });
        }
        else
        {
            jQuery('.dragbar',root_element).hide();
        }
        
        root_element.show();

        if (jQuery.browser.msie && jQuery.browser.version < 8)
        {
            var width = 0;
	    root_element.children().each(function(){
	        width += jQuery(this).width();
            });
            root_element.width(width + 30);
        }
        debug('enable_toolbar finished', 'info');
        
        init_auto_move();
    }
    
    function init_auto_move()
    {
        // jQuery('window').bind('scroll', function(e){
        //     console.log("Body scroll");
        // });
    }
    
    function save_position(event)
    {
        debug('save_position start', 'info');
        
        var new_pos = root_element.position();
        
        // Remove scrolling offset
        new_pos.top -= jQuery(window).scrollTop();
        new_pos.left -= jQuery(window).scrollLeft();
        
        var pos = { x: new_pos.left,
                    y: new_pos.top };

        jQuery.get(
            MIDCOM_PAGE_PREFIX + 'midcom-exec-midcom/toolbar.php',
            {
                'position_x': new_pos.left,
                'position_y': new_pos.top
            }
        );
        
        debug('save_position finished', 'info');
    }
    
    function get_default_position(re)
    {
        var x = 20;
        var y = 20;
        
        var dw = jQuery(document).width();
        
        var ew = type_configs[settings.type].width || jQuery(re).width();

        if (ew == 0)
        {
            return {
                x: 0,
                y: 0
            };
        }
        
        var left = (dw/2) - ew/2;
        x = left;

        return {
            x: x,
            y: y
        };
    }
    
    function debug(msg, type)
    {
        // var console_type = 'debug';
        // 
        // if (type != "undefined")
        // {
        //     console_type = type;
        // }

        if (settings.debug)
        {
            console.log('midcom_services_toolbars: ' + msg);
        }
    }
    
}