<div class="full-width fill-height">
    <table id="treegrid"></table>
    <script type="text/javascript">

    //function to set the right title for creator-cell
    function setCellTitle(rowid , rowdata ,rowelem)
    {
        var cell = $("#"+rowid).children(':eq(3)');
        var source_cell = $("#"+rowid).children(':eq(2)');
        cell.attr('title', source_cell.text());
    }
    //function to add zebra-stripping
    function zebraStriping()
    {
        $('#treegrid tr').removeClass("even").removeClass("odd");
        $('#treegrid tr:odd:visible').addClass('odd');
        $('#treegrid tr:even:visible').addClass('even');
    }
    jQuery("#treegrid").jqGrid({
        treeGrid: true,
        treeGridModel: 'adjacency',
        url: '<?php echo $data['prefix'] ;?>directory/xml/<?php echo $data['current_guid']; ?>/',
        treedatatype: "xml",
        mtype: "POST",
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
            {name:'id',index:'id', hidden:true, key:true },
            {name:'name_index', index:'name_index' , hidden:true},
            {name:'name', index: 'name_index', width: 100 },
            {name:'download_url', index: 'download_url', classes: 'download_url', hidden: true },
            {name:'creator_index', index: 'creator_index' , hidden:true },
            {name:'creator',index: 'creator_index', width: 70 },
            {name:'last_mod_index', index:'last_mod_index' , hidden: true},
            {name:'last_mod', index:'last_mod_index' , fixed: true},
            {name:'file_size_index',index:'file_size_index' , hidden:true, sortable:true , sorttype:'integer'},
            {name:'file_size',index:'file_size_index', width: 90, fixed: true  }
         ],
        gridview: false,
        ExpandColumn : 'name',
        afterInsertRow: setCellTitle
        //loadComplete: zebraStriping
     });

$('#treegrid .document').live('contextmenu', function(e)
{
    var guid = $(this).attr('href').replace(/^.*?\/([a-z0-9]+?)\/$/, '$1'),
    download_url = $.trim($(this).closest('.jqgrow').find('.download_url').text());

    $.ajax
    ({
        type: "POST",
        url: MIDCOM_PAGE_PREFIX + "/midcom-exec-org.openpsa.documents/mark_visited.php",
        data: "guid=" + guid
    });
    window.location.href = download_url;
    $(this).removeClass('new').addClass('visited');
    return false;
});
    </script>

<h1><?php echo $data['directory']->extra; ?></h1>