<div class="sidebar">
    <?php midcom_show_style('show-directory-navigation'); ?>
</div>

<div class="main">
<h1><?php echo $data['directory']->extra; ?></h1>
<div class="fill-height full-width">
    <table id="treegrid"></table>
</div>
</div>

<script type="text/javascript">

//function to set the right title for creator-cell
function setCellTitle(rowid, rowdata, rowelem)
{
    var cell = $("#" + rowid).children(':eq(3)');
    var source_cell = $("#" + rowid).children(':eq(2)');
    cell.attr('title', source_cell.text());
}

jQuery("#treegrid").jqGrid({
    treeGrid: true,
    treeGridModel: 'adjacency',
    url: '<?php echo $data['prefix'] ;?>directory/xml/<?php echo $data['current_guid']; ?>/',
    treedatatype: "xml",
    mtype: "POST",
    rowNum: 100, //TODO: this should be set by JS in the loadComplete event (which isn't working ATM)
    colNames:["id",
    <?php
        //index is needed for sorting
        echo "'name_index',";
        echo "'" . $data['l10n']->get("title") ."',";
        echo "'download_url', 'creator_index',";
        echo "'" . $data['l10n']->get("creator") . "',";
        echo "'last_mod_index',";
        echo "'" . $data['l10n']->get("last modified") . "',";
        echo "'file_size_index',";
        echo "'" . $data['l10n']->get("file size") . "'";
    ?>
    ],
    colModel:[
        {name:'id',index:'id', hidden: true, key: true },
        {name:'name_index', index:'name_index', hidden: true},
        {name:'name', index: 'name_index', width: 100, classes: "ui-ellipsis"},
        {name:'download_url', index: 'download_url', classes: 'download_url', hidden: true },
        {name:'creator_index', index: 'creator_index', hidden: true },
        {name:'creator', index: 'creator_index', width: 70, classes: "ui-ellipsis"},
        {name:'last_mod_index', index:'last_mod_index', hidden: true},
        {name:'last_mod', width: 105, index:'last_mod_index', align: 'center', fixed: true},
        {name:'file_size_index', index:'file_size_index', hidden: true, sortable: true, sorttype:'integer'},
        {name:'file_size', index:'file_size_index', width: 90, fixed: true  }
     ],
    gridview: false,
    ExpandColumn : 'name',
    afterInsertRow: setCellTitle
 });

$('#treegrid').on('contextmenu', '.document', function(e)
{
    var item = $(this),
    guid = item.attr('href').replace(/^.*?\/([a-z0-9]+?)\/$/, '$1'),
    download_url = $.trim(item.closest('.jqgrow').find('.download_url').text());

    if (item.hasClass('new'))
    {
        $.ajax
        ({
            type: "POST",
            url: MIDCOM_PAGE_PREFIX.replace(/\/$/, '') + "/midcom-exec-org.openpsa.documents/mark_visited.php",
            data: "guid=" + guid,
            complete: function()
            {
                item.removeClass('new').addClass('visited');
                window.location.href = download_url;
            }
        });
    }
    else
    {
        window.location.href = download_url;
    }
    return false;
});
</script>