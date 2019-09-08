var org_openpsa_tree = {
    setup: function(identifier, prefix, options) {
        var default_options = {
            minExpandLevel: 1,
            extensions: ['persist', 'glyph'],
            clickFolderMode: 2,
            autoCollapse: false,
            debugLevel: -1,

            activate: function(event, data) {
                if (   data.node.data.href !== undefined
                    && event.originalEvent !== undefined
                    && window.location.pathname !== data.node.data.href) {
                    window.location.href = data.node.data.href;
                }
                data.node.scrollIntoView();
            },
            click: function(event, data) {
                if (   data.tree.activeNode !== undefined
                    && data.tree.activeNode === data.node) {
                    data.node.setActive(false);
                }
                return true;
            },
            init: function() {
                $(window).trigger('resize');
            },
            expand: function() {
                $(window).trigger('resize');
            },
            glyph: {
                preset: "awesome4",
            },
            persist: {
                store: 'local'
            }
        };

        options = $.extend({}, default_options, options || {});

        $(window).on('resize', function() {
            org_openpsa_tree.crop_height($('#' + identifier));
        });

        $('#' + identifier)
            .css('overflow', 'auto')
            .fancytree(options);
    },
    crop_height: function(tree) {
        if ($('#content-text').length === 0) {
            return;
        }
        var container_height = $('#content-text').height(),
            tree_content_height = tree.find('.fancytree-container').height(),
            available_height = container_height - (tree.closest('.sidebar').height() - tree.outerHeight(true)),
            new_height = Math.max(Math.min(tree_content_height, available_height, container_height), 20);

        if (new_height !== tree.height()) {
            tree.height(new_height);
        }
    }
};
