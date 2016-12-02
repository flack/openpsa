<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>

<div class="sidebar">
    <?php
    if ($data['projects_relative_url']) {
        midcom::get()->dynamic_load('workingon/');
    }
    if ($data['calendar_url']) {
        ?>
            <div class="agenda">
                <?php
                midcom::get()->dynamic_load($data['calendar_url'] . 'agenda/day/' . $data['requested_time']->format('Y-m-d')); ?>
            </div>
        <?php

    }
    ?>
</div>

<div class="org_openpsa_mypage main">
    <?php
    if ($data['journal_url']) {
        ?>
        <div class="journal full-width normal" style="padding-bottom:20px;">
        <div >
        <table id="treegrid"></table> <div id="ptreegrid"></div>
        </div>
        </div>

        <script type="text/javascript">
        //call for jqgrid-plugin
            var post_array = <?php echo json_encode($data['journal_constraints']) ; ?>;

            jQuery("#treegrid").jqGrid({
                scroll: 1,
                url: '<?php echo $data['journal_url'] ; ?>',
                datatype: "xml",
                mtype: "POST",
                height: 150,
                postData: {journal_entry_constraints:post_array},
                colNames:["id",
                <?php
                    //index is needed for sorting
                    echo "'index_name',";
        echo "'" . midcom::get()->i18n->get_string('entry title', 'org.openpsa.relatedto') ."',";
        echo "'" . midcom::get()->i18n->get_string('entry text', 'org.openpsa.relatedto') . "',";
        echo "'" . midcom::get()->i18n->get_string('followup', 'org.openpsa.relatedto') . "',";
        echo "'index_object',";
        echo "'" . midcom::get()->i18n->get_string('linked object', 'org.openpsa.relatedto') . "'"; ?>
                ],
                colModel:[
                    {name:'id',index:'id', hidden:true, key: true },
                    {name:'index_name', index: 'index_name', hidden: true},
                    {name:'name', index: 'index_name' },
                    {name:'description',index: 'description' },
                    {name:'remind_date', index: 'remind_date', fixed: true, align: 'right', formatter: 'date', width:140 },
                    {name:'index_object', index: 'index_object', sorttype: "text", hidden: true},
                    {name:'object', index: 'index_object', width: 120 }
                 ],
                rownumbers: false,
                loadonce: true,
                caption: "<?php echo midcom::get()->i18n->get_string('journal entries', 'org.openpsa.relatedto'); ?>",
                sortname: 'remind_date'
             });
        </script>
        <?php

    }

    if ($data['projects_relative_url']) {
        ?>
        <div class="tasks normal">
            <?php
            midcom::get()->dynamic_load($data['projects_relative_url'] . 'task/list/'); ?>
        </div>
        <?php

    }

    if ($data['wiki_url']) {
        ?>
        <div class="wiki">
            <?php
            midcom::get()->dynamic_load($data['wiki_url'] . 'latest/'); ?>
        </div>
        <?php

    }
    ?>
</div>
