<div class="full-width">
<table id="journal_entry_grid"></table>
<div id="p_journal_entry_grid"></div>
</div>

<script type="text/javascript">
    <?php
    //add static data to jqgrid if wanted
    if (array_key_exists('entries', $data)) {
        $rows = [];
        $workflow = new midcom\workflow\datamanager;
        foreach ($data['entries'] as $entry) {
            $row = [
                'id' => $entry->id,
                'index_name' => $entry->title,
                'description' => $entry->text,
                'index_date' => $entry->followUp,
            ];

            $row['name'] = '<a href="' . $data['router']->generate('journal_entry_edit', ['guid' => $entry->guid]) . '" ' . $workflow->render_attributes() . '>';
            $row['name'] .= "<span>" . $entry->title . "</span></a>";

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

            $row['closed'] = $data['l10n']->get(($entry->closed) ? 'finished' : 'open');
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
        datatype: "local",
        data: entries,
        colNames: ["id",
                  <?php
                  //index is needed for sorting
                  echo "'index_name',";
                  echo "'" . $data['l10n']->get('entry title') ."',";
                  echo "'" . $data['l10n']->get('entry text') . "',";
                  echo "'" . $data['l10n']->get('entry created') . "',";
                  echo "'index_creator', '" . $data['l10n_midcom']->get('creator') . "',";
                  echo "'" . $data['l10n']->get('status') . "'";
                  ?>
        ],
        colModel: [
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
            if (loaded_in_tab) {
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
