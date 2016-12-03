<div class="full-width">
    <table id="journalgrid"></table>
</div>

<script type="text/javascript">
//call for jqgrid-plugin
var post_array = <?php echo json_encode($data['journal_constraints']); ?>;

$("#journalgrid").jqGrid({
    scroll: 1,
    url: '<?php echo $data['url_prefix'] ; ?>xml/',
    datatype: "xml",
    mtype: "POST",
    height: 150,
    postData: {journal_entry_constraints: post_array},
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
    sortname: 'remind_date'
 });
</script>