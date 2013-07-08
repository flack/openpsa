var org_openpsa_tree =
{
    setup: function(identifier, prefix, options)
    {
        var default_options =
        {
            minExpandLevel: 1,
            persist: true,
            cookie: {path: prefix},
            cookieId: identifier,
            clickFolderMode: 2,
            autoCollapse: false,
            debugLevel: -1,

            onActivate: function(dtnode)
            {
                if (dtnode.data.href !== undefined)
                {
                    window.location.href = dtnode.data.href;
                }
            },
            onClick: function(dtnode, event)
            {
                if (   dtnode.tree.activeNode !== undefined
                    && dtnode.tree.activeNode === dtnode)
                {
                    dtnode.deactivate();
                }
                return true;
            },
            onCustomRender: function(dtnode)
            {
                var url = '#',
                tooltip = dtnode.data.tooltip ? " title='" + dtnode.data.tooltip + "'" : "";

                if (dtnode.data.href !== undefined)
                {
                    url = dtnode.data.href;
                }
                return '<a href="' + url + '" class="' + dtnode.tree.options.classNames.title + '"' + tooltip + '>' + dtnode.data.title + '</a>';
            },
            onPostInit: function(isReloading, isError)
            {
                $(window).trigger('resize');
            },
            onExpand: function(flag, dtnode)
            {
                $(window).trigger('resize');
            }
        };

        options = $.extend({}, default_options, options || {});

        $(window).bind('resize', function()
        {
            org_openpsa_tree.crop_height($('#' + identifier));
        });

        $('#' + identifier)
            .css('overflow', 'auto')
            .dynatree(options);
    },
    crop_height: function(tree)
    {
        var container_height = $('#content-text').height(),
        tree_content_height = tree.find('.dynatree-container').height(),
        available_height = container_height - (tree.closest('.sidebar').height() - tree.outerHeight(true)),
        new_height = Math.max(Math.min(tree_content_height, available_height, container_height), 20);

        if (new_height !== tree.height())
        {
            tree.height(new_height);
        }
    }
};