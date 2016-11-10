<div class="full-width">
<table id="journal_entry_grid"></table>
<div id="p_journal_entry_grid"></div>
</div>

<script type="text/javascript">
    <?php
    //add static data to jqgrid if wanted
    $start = true;
    if (    !array_key_exists('dynamic_load', $data)
         && array_key_exists('entries', $data)) {
        $rows = array();
        $workflow = new midcom\workflow\datamanager2;
        foreach ($data['entries'] as $entry) {
            $row = array(
                'id' => $entry->id,
                'index_name' => $entry->title,
                'description' => $entry->text,
                'index_date' => $entry->followUp,
            );

            $link_html = '<a href="' . $data['url_prefix'] . 'edit/' . $entry->guid . '" ' . $workflow->render_attributes() . '>';
            $link_html .= "<span>" . $entry->title . "</span></a>";
            $row['name'] = $link_html;

            $row['date'] = date('Y-m-d', $entry->metadata->created);

            try {
                $creator = org_openpsa_contacts_person_dba::get_cached($entry->metadata->creator);
                $row['creator_index'] = $creator->rname;
                $creator_card = org_openpsa_widgets_contact::get($entry->metadata->creator);
                $row['creator'] = $creator_card->show_inline();
            } catch (midcom_error $e) {
                $row['creator_index'] = '';
                $row['creator'] = '';
            }

            if ($entry->closed) {
                $row['closed'] = midcom::get()->i18n->get_string('finished', 'org.openpsa.relatedto');
            } else {
                $row['closed'] = midcom::get()->i18n->get_string('open', 'org.openpsa.relatedto');
            }
            $rows[] = $row;
        }
        echo 'var entries = ' . json_encode($rows) . ";\n";
    }
    ?>
    //check if jqgrid was loaded in tab
    var loaded_in_tab = false;
    if ($(".ui-tabs").length > 0)
    {
        loaded_in_tab = true;
    }

    //jqgrid call
    jQuery("#journal_entry_grid").jqGrid({
        <?php
        if (!array_key_exists('dynamic_load', $data)) {
            ?>
            datatype: "local",
            data: entries,
        <?php 
        } else {
            ?>
            url: '<?php echo $data['data_url'] ; ?>',
            datatype: "xml",
            mtype: "POST",
        <?php 
        } ?>
        colNames:["id",
                  <?php
                  //index is needed for sorting
                  echo "'index_name',";
                  echo "'" . midcom::get()->i18n->get_string('entry title', 'org.openpsa.relatedto') ."',";
                  echo "'" . midcom::get()->i18n->get_string('entry text', 'org.openpsa.relatedto') . "',";
                  echo "'" . midcom::get()->i18n->get_string('entry created', 'org.openpsa.relatedto') . "',";
                  echo "'index_creator', '" . midcom::get()->i18n->get_string('creator', 'midcom') . "',";
                  echo "'" . midcom::get()->i18n->get_string('status', 'org.openpsa.relatedto') . "'";
                  ?>
        ],
        colModel:[
                  {name:'id',index:'id', hidden: true, key: true },
                  {name:'index_name',index:'index_name', hidden: true},
                  {name:'name', index: 'index_name', width: 100 },
                  {name:'description', index: 'description', classes: 'longtext' },
                  {name:'date', index: 'date', align: 'right', width: 125, formatter: 'date', fixed: true},
                  {name:'index_creator', index: 'index_creator', hidden: true},
                  {name:'creator', index: 'index_creator', width: 150, fixed: true},
                  {name:'closed',index:'closed', width: 60, fixed: true }
        ],
        gridComplete: function()
        {
            if (loaded_in_tab)
            {
                new_height = $(this).attr('clientHeight') + $("#gbox_journal_entry_grid").siblings('.org_openpsa_toolbar').attr('clientHeight') + 15;
                $("#gbox_journal_entry_grid").parent().css('height', new_height);
            }
        },
        pager : "#p_journal_entry_grid",
        loadonce: true,
        grouping: true,
        groupingView: {
          groupField: ['date'],
          groupColumnShow: [false],
          groupText : ['<strong>{0}</strong>'],
          groupOrder: ['desc'],
       }
    });

</script>
