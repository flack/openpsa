<?php echo $data['renderer']->block($data['form'], 'form_start'); ?>
<div class="maa_permissions_assignees">
	<?php
	if (empty($data['form']->children['add_assignee']->vars['value'])) {
	    echo $data['renderer']->label($data['form']->children['add_assignee']);
        echo $data['renderer']->widget($data['form']->children['add_assignee']);
	}
	?>
</div>
<table class="maa_permissions_items">
    <thead>
    <tr class="maa_permissions_rows_header">
        <?php echo $data['editor_header_titles']; ?>
    </tr>
    </thead>
    <tbody>
    <?php
    foreach ($data['row_labels'] as $identifier => $label) {
        echo '<tr id="privilege_row_' . $identifier . '" class="maa_permissions_rows_row">';
        echo '<th class="row_value assignee_name"><span>' . $label . '</span></th>';
        $started = false;
        foreach ($data['form']->children as $name => $child) {
            if (substr($name, 0, strlen($identifier) + 2) !== $identifier . '__') {
                if ($started) {
                    echo '<td class="row_value row_actions">';
                    echo '<div class="actions" id="privilege_row_actions_' . $identifier . '"></div>';
                    echo '</td></tr>';
                    continue 2;
                }
                continue;
            }
            $started = true;
            echo '<td class="row_value">';
            echo $data['renderer']->widget($child);
            echo '</td>';
        }
    }
    ?>
    </tbody>
</table>
<div class="maa_permissions_footer">
	<?php echo $data['renderer']->block($data['form'], 'form_rest'); ?>
</div>
<?php echo $data['renderer']->block($data['form'], 'form_end'); ?>