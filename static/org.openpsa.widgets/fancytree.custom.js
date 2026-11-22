const org_openpsa_tree = {
    setup: function(identifier, options) {
        const tree = $('#' + identifier),
            resize_handler = () => org_openpsa_tree.crop_height(tree),
            default_options = {
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
                init: resize_handler,
                expand: resize_handler,
                collapse: resize_handler,
                glyph: {
                    preset: "awesome4"
                },
                persist: {
                    store: 'local',
                    expandOpts: {
                        noAnimation: true,
                        noEvents: false
                    }
                }
            };

        options = $.extend({}, default_options, options || {});

        tree
            .css('overflow', 'auto')
            .fancytree(options);

        const content = document.getElementById('content-text');
        if (content) {
            // use border-box so that changes in the tree's scrollbar don't trigger the observer
            new ResizeObserver(() => org_openpsa_tree.crop_height(tree)).observe(content, {box: 'border-box'});
        }
    },
    crop_height: function(tree) {
        if ($('#content-text').length === 0) {
            return;
        }
        var container_height = $('#content-text').height(),
            tree_content_height = tree.find('.fancytree-container').height() + 2,
            available_height = container_height - ((tree.closest('aside').height() || 0) - tree.outerHeight(true)),
            new_height = Math.max(Math.min(tree_content_height, available_height, container_height), 20);

        if (new_height !== tree.height()) {
            tree.height(new_height);
        }
    }
};
