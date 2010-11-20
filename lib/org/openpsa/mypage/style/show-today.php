<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
$view_today =& $data['view_today'];
?>

<div class="sidebar">
    <?php
    if ($data['projects_url'])
    {
        midcom_show_style('workingon');
    }

    // List expenses
    if ($data['expenses_url'])
    {
        echo "<div class=\"expenses\">\n";
        echo "<h2>" . $data['l10n']->get('this week') . "</h2>\n";
        echo "<div id=\"content_expenses\">";
        midcom_show_style('workingon_expenses');
        echo "</div>";
        echo "</div>\n";
    }
    ?>
</div>

<div class="org_openpsa_mypage main">
    <?php

    if ($data['calendar_url'])
    {
        ?>
        <div class="agenda">
            <?php
            $_MIDCOM->dynamic_load($data['calendar_url'] . 'agenda/day/' . $data['requested_time']);
            ?>
        </div>
        <?php
    }


    if($data['journal_url'])
    {
        ?>
        <div class="journal full-width" style="padding-bottom:20px;">
        <div >
        <table id="treegrid"></table> <div id="ptreegrid"></div>
        </div>
        </div>

        <script type="text/javascript">
        //call for jqgrid-plugin
        $(document).ready(
        function()
        {
            var post_array = <?php echo json_encode($data['journal_constraints']) ;?>;

            jQuery("#treegrid").jqGrid({
                scroll: 1,
                url: '<?php echo $data['journal_url'] ;?>',
                datatype: "xml",
                mtype: "POST",
                height: 150,
                postData: {journal_entry_constraints:post_array},
                colNames:["id",
                <?php
                    //index is needed for sorting
                    echo "'index_name',";
                    echo "'" . $_MIDCOM->i18n->get_string('entry title', 'org.openpsa.relatedto') ."',";
                    echo "'" . $_MIDCOM->i18n->get_string('entry text', 'org.openpsa.relatedto') . "',";
                    echo "'index_date',";
                    echo "'" . $_MIDCOM->i18n->get_string('followUp', 'org.openpsa.relatedto') . "',";
                    echo "'index_object',";
                    echo "'" . $_MIDCOM->i18n->get_string('linked object', 'org.openpsa.relatedto') . "'";
                ?>
                ],
                colModel:[
                    {name:'id',index:'id', hidden:true, key:true },
                    {name:'index_name', index: 'index_name' ,hidden: true},
                    {name:'name', index: 'index_name' },
                    {name:'description',index: 'description' },
                    {name:'index_date' , index: 'index_date' , sorttype: 'integer', hidden:true },
                    {name:'remind_date', index:'index_date', fixed: true , width:140 },
                    {name:'index_object', index: 'index_object', sorttype:"text" ,hidden: true},
                    {name:'object',index:'index_object', width: 120 }
                 ],
                rownumbers: false,
                loadonce: true,
                caption: "<?php echo $_MIDCOM->i18n->get_string('journal entries', 'org.openpsa.relatedto');?>",
             });
             jQuery("#gbox_treegrid").css('float' , 'none');
        });


        </script>
        <?php
    }

    if ($data['projects_relative_url'])
    {
        ?>
        <div class="tasks">
            <?php
            $_MIDCOM->dynamic_load($data['projects_relative_url'] . 'task/list/');
            ?>
        </div>
        <?php
    }

    if ($data['wiki_url'])
    {
        ?>
        <div class="wiki">
            <?php
            $_MIDCOM->dynamic_load($data['wiki_url'] . 'latest/');
            ?>
        </div>
        <?php
    }
    ?>
</div>
