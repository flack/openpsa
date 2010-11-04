<div class="grid-filters">
<?php
if (array_key_exists('filter_priority', $data))
{
    echo $data['l10n']->get('only tasks with priority'); ?>:
    <form id="priority_filter" action="" method="post" style="display:inline">
        <select onchange="document.forms['priority_filter'].submit();" name="priority" id="multiselect" size="1" >
        <?php
        foreach($data['filter_priority'] as $id => $priority)
        { ?>
            <option value="<?php echo $id;?>"
            <?php
            if ($priority['selected'] == true)
            {
                echo "selected=\"selected\"";
            }
            echo '>' . $priority['title'] . '</option>';
        } ?>
        </select>
    </form>
<?php }

if ($data['view'] == 'grid')
{
    $grid_id = $data['view_identifier'] . '_tasks_grid';

    echo ' ' . $_MIDCOM->i18n->get_string('group by', 'org.openpsa.core') . ': ';
    echo '<select id="chgrouping_' . $grid_id . '">';
    echo '<option value="index_project">' . $data['l10n']->get('project') . "</option>\n";
    echo '<option value="index_customer">' . $data['l10n']->get('customer') . "</option>\n";
    echo '<option value="index_manager">' . $data['l10n']->get('manager') . "</option>\n";
    echo '<option value="clear" ' . (($data['view_identifier'] == 'agreement') ? 'selected="selected"' : '' ) . ' >' . $_MIDCOM->i18n->get_string('no grouping', 'org.openpsa.core') . "</option>\n";
    echo '</select>';
} ?>
</div>