<table id="journal_entry_grid"></table>
<div id="p_journal_entry_grid"></div>

<script type="text/javascript">
    <?php
    //add static data to jqgrid if wanted
    $start = true;
    if ( !array_key_exists('dynamic_load', $data)
         && array_key_exists('entries', $data))
    {
        $rows = array();
        foreach($data['entries'] as $entry)
        {
            $row = array
            (
                'id' => $entry->id,
                'index_name' => $entry->title,
                'description' => $entry->text,
                'index_date' => $entry->followUp,
            );

            $link_html = "<a href='" . $data['url_prefix'] . "edit/" . $entry->guid ."/'>";
            $link_html .= "<span >" . $entry->title . "</span></a>";
            $row['name'] = $link_html; 

            if ($entry->followUp == 0)
            {
                $row['remind_date'] = 'none';
            }
            else
            {
                $row['remind_date'] = date('d.m.Y', $entry->followUp);
            }

            try
            {
                $creator = org_openpsa_contacts_person_dba::get_cached($entry->metadata->creator);
                $row['creator_index'] = $creator->rname;
                $creator_card = org_openpsa_contactwidget::get($entry->metadata->creator);
                $row['creator'] = $creator_card->show_inline();
            }
            catch (midcom_error $e)
            {
                $row['creator_index'] = '';
                $row['creator'] = '';
            }

            if ($entry->closed)
            {
                $row['closed'] = $_MIDCOM->i18n->get_string('finished', 'org.openpsa.relatedto');
            }
            else
            {
                $row['closed'] = $_MIDCOM->i18n->get_string('open', 'org.openpsa.relatedto');
            }
            $rows[] = $row;
        }
        echo 'var entries = ' . json_encode($rows) . ";\n";
    }
    ?>
    function jqgrid_resize()
    {
        var new_width = jQuery("#gbox_journal_entry_grid").parent().attr('clientWidth') - 2;
        jQuery("#journal_entry_grid").jqGrid().setGridWidth(new_width);
    }
    //check if jqgrid was loaded in tab
    var loaded_in_tab = false;
    if ($(".ui-tabs").length > 0)
    {
        loaded_in_tab = true;
    }

    //jqgrid call
    jQuery("#journal_entry_grid").jqGrid({
        <?php
        if (!array_key_exists('dynamic_load', $data))
        { ?>
            datatype: "local",
            data: entries,
        <?php }
        else
        { ?>
            url: '<?php echo $data['data_url'] ;?>',
            datatype: "xml",
            mtype: "POST",
        <?php } ?>
        colNames:["id",
                  <?php
                  //index is needed for sorting
                  echo "'index_name',";
                  echo "'" . $_MIDCOM->i18n->get_string('entry title', 'org.openpsa.relatedto') ."',";
                  echo "'" . $_MIDCOM->i18n->get_string('entry text', 'org.openpsa.relatedto') . "',";
                  echo "'index_date',";
                  echo "'" . $_MIDCOM->i18n->get_string('followUp', 'org.openpsa.relatedto') . "',";
                  echo "'index_creator', '" . $_MIDCOM->i18n->get_string('creator', 'midcom') . "',";
                  echo "'" . $_MIDCOM->i18n->get_string('status', 'org.openpsa.relatedto') . "'";
                  ?>
        ],
        colModel:[
                  {name:'id',index:'id', hidden:true, key:true },
                  {name:'index_name',index:'index_name', hidden:true},
                  {name:'name', index: 'index_name' , width: 100 },
                  {name:'description',index: 'description' },
                  {name:'index_date', index: 'index_date', sorttype: "integer", hidden:true },
                  {name:'remind_date', index:'index_date', align: 'center', width: 110, fixed: true},
                  {name:'index_creator', index: 'index_creator', hidden:true},
                  {name:'creator', index: 'index_creator' , width: 150, fixed: true},
                  {name:'closed',index:'closed', width: 60, fixed: true }
        ],
        gridComplete: function()
        {
            if (loaded_in_tab)
            {
                new_height = $(this).attr('clientHeight') + $("#gbox_journal_entry_grid").siblings('.org_openpsa_toolbar').attr('clientHeight') + 15;
                $("#gbox_journal_entry_grid").parent().css('height' , new_height);

                //if this is loaded into a tab - load the links into tabs also
                $('#tabs').bind('tabsload', function(event , ui)
                {
                    //just take links which have no onclick-handling
                    $('a', ui.panel).not('onclick').click(function()
                    {
                        //check if a target is set - if it is - don't load it into tab
                        if (this.target == undefined || this.target == '')
                        {
                            if (this.href.slice(this.href.length - 1, this.href.length) != '#')
                            {
                                $(ui.panel).load(this.href);
                                return false;
                            }
                        }
                    });
                });
            }
        },
        pager : "#p_journal_entry_grid",
        loadonce: true
    });

    jqgrid_resize();

    jQuery(window).resize(function()
    {
        jqgrid_resize();
    });

</script>
