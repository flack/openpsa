<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<h1><?php echo $data['title']; ?></h1>
<form method="post">
    <div class="midcom_admin_content_folderlist">
        <ul>
        <?php
        $root_folder = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ROOTTOPIC);
        
        $class = '';
        $selected = '';
        $disabled = '';
        if ($data['current_folder']->guid === $root_folder->guid)
        {
            $class = 'current';
            $selected = ' checked="checked"';
        }
        
        if (   !is_a($data['object'], 'midcom_db_topic')
            && $root_folder->component !== $data['current_folder']->component)
        {
            // Non-topic objects may only be moved under folders of same component
            $class = 'wrong_component';
            $disabled = ' disabled="disabled"';
        }
        
        echo "            <li class=\"{$class}\"><label><input{$selected}{$disabled} type=\"radio\" name=\"move_to\" value=\"{$root_folder->id}\" /> {$root_folder->extra}</label>\n";
        
        function midcom_admin_folder_list_folders($up = 0, $tree_disabled = false)
        {
            $data =& $_MIDCOM->get_custom_context_data('request_data');
            if (   is_a($data['object'], 'midcom_db_topic')
                && $up == $data['object']->id)
            {
                $tree_disabled = true;
            }
        
            $qb = midcom_db_topic::new_query_builder();
            $qb->add_constraint('up', '=', $up);
            $qb->add_constraint('component', '<>', '');
            $folders = $qb->execute();
            if (count($folders) > 0)
            {
                echo "<ul>\n";
                foreach ($folders as $folder)
                {
                    $class = '';
                    $selected = '';
                    $disabled = '';
                    if ($folder->guid == $data['current_folder']->guid)
                    {
                        $class = 'current';
                        $selected = ' checked="checked"';
                    }
                    
                    if (   !is_a($data['object'], 'midcom_db_topic')
                        && $folder->component !== $data['current_folder']->component)
                    {
                        // Non-topic objects may only be moved under folders of same component
                        $class = 'wrong_component';
                        $disabled = ' disabled="disabled"';
                    }
                    
                    if ($tree_disabled)
                    {
                        $class = 'child';
                        $disabled = ' disabled="disabled"';
                    }
                    
                    if ($folder->guid == $data['object']->guid)
                    {
                        $class = 'self';
                        $disabled = ' disabled="disabled"';
                    }
                    
                    echo "<li class=\"{$class}\"><label><input{$selected}{$disabled} type=\"radio\" name=\"move_to\" value=\"{$folder->id}\" /> {$folder->extra}</label>\n";
                    midcom_admin_folder_list_folders($folder->id, $tree_disabled);
                    echo "</li>\n";
                }
                echo "</ul>\n";
            }
        }
        
        midcom_admin_folder_list_folders($root_folder->id);
        ?>
            </li>
        </ul>
    </div>
    <div class="form_toolbar">
        <input type="submit" class="save" accesskey="s" value="<?php echo $_MIDCOM->i18n->get_string('move', 'midcom.admin.folder'); ?>" />
    </div>
</form>