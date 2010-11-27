<script type="text/javascript">
    <?php
    //add static data to jqgrid if wanted
    $start = true;
    if( !array_key_exists('dynamic_load' , $data)
        && array_key_exists('entries' , $data))
    {
        echo "var entries = [\n";
        foreach($data['entries'] as $entry)
        {
            if ($start)
            {
                $start = false;
            }
            else
            {
                echo ",\n";
            }
            echo "{";
            echo "id:\"" . $entry->id ."\"";
            echo ",index_name:\"" . $entry->title ."\"";

            $link_html = "<a href='" . $data['url_prefix'] . "edit/" . $entry->guid ."/'>";
            $link_html .= "<span >" . $entry->title . "</span></a>";
            echo ",name:\"" . $link_html . "\"";

            echo ",description:\"" . $entry->text . "\"";
            echo ",index_date:\"" . $entry->followUp . "\"";

            if ($entry->followUp == 0)
            {
                echo ",remind_date:'none'";
            }
            else
            {
                echo ",remind_date:\"" . date('d.m.Y' , $entry->followUp) ."\"";
            }
            if ($entry->closed)
            {
                echo ",closed:\"" . $_MIDCOM->i18n->get_string('finished', 'org.openpsa.relatedto') . "\"";
            }
            else
            {
                echo ",closed:\"" . $_MIDCOM->i18n->get_string('open', 'org.openpsa.relatedto') . "\"";
            }
            echo "}";
        }
        echo "\n];";
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
    $(document).ready(
    function()
    {
        jQuery("#journal_entry_grid").jqGrid({
            <?php
            if( !array_key_exists('dynamic_load' , $data))
            {
                ?>
                datatype: "local",
                data: entries,
                <?php
            }
            else
            {
                ?>
                url: '<?php echo $data['data_url'] ;?>',
                datatype: "xml",
                mtype: "POST",
                <?php
            }
            ?>
            colNames:["id",
            <?php
                //index is needed for sorting
                echo "'index_name',";
                echo "'" . $_MIDCOM->i18n->get_string('entry title', 'org.openpsa.relatedto') ."',";
                echo "'" . $_MIDCOM->i18n->get_string('entry text', 'org.openpsa.relatedto') . "',";
                echo "'index_date',";
                echo "'" . $_MIDCOM->i18n->get_string('followUp', 'org.openpsa.relatedto') . "',";
                echo "'" . $_MIDCOM->i18n->get_string('status', 'org.openpsa.relatedto') . "'";
            ?>
            ],
            colModel:[
                {name:'id',index:'id', hidden:true, key:true },
                {name:'index_name',index:'index_name', hidden:true},
                {name:'name', index: 'index_name' , width: 100 },
                {name:'description',index: 'description' },
                {name:'index_date' , index: 'index_date' , sorttype: "integer" , hidden:true },
                {name:'remind_date', index:'index_date', width: 140, fixed: true},
                {name:'closed',index:'closed', width: 60, fixed: true }
             ],
            gridComplete: function(){
                //window.console.log('gridcomplete');
                if(loaded_in_tab)
                {
                    new_height = $(this).attr('clientHeight') + $("#gbox_journal_entry_grid").siblings('.org_openpsa_toolbar').attr('clientHeight') + 15;
                    $("#gbox_journal_entry_grid").parent().css('height' , new_height);
                    //if this is loaded into a tab - load the links into tabs also
                    //window.console.log($('#tabs'));
                    $('#tabs').bind('tabsload', function(event , ui)
                    {
                        //just take links which have no onclick-handling
                        $('a', ui.panel).not('onclick').click(function() {
                            //check if a target is set - if it is - don't load it into tab
                            if (this.target == undefined || this.target == '')
                            {
                                if(this.href.slice(this.href.length - 1, this.href.length) != '#')
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
            //caption: "<?php echo $_MIDCOM->i18n->get_string('journal entries', 'org.openpsa.relatedto');?>",
         });
         jqgrid_resize();

         jQuery(window).resize(function()
         {
            jqgrid_resize();
         });
    });

</script>
<table id="journal_entry_grid"></table> <div id="p_journal_entry_grid"></div>
