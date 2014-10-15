<?php
$navi = new fi_protie_navigation();
$navi->list_leaves = true;
$navi->list_levels = 3;
$navi->follow_all = true;
$navi->draw();
?>
<script type="text/javascript">
    $('#nav').fancytree(
    {
        minExpandLevel: 1,
        extensions: ['persist'],
        persist:
        {
            cookie: {path: MIDCOM_PAGE_PREFIX}
        },
        clickFolderMode: 3,
        autoCollapse: false,
        debugLevel: -1,

        click: function(event, data)
        {
            if (   data.targetType !== undefined
                && data.targetType !== 'expander'
                && data.node.data.href !== undefined
                && event.originalEvent !== undefined
                && window.location.pathname !== data.node.data.href)
            {
                window.location.href = data.node.data.href;
            }
        }
    });
</script>