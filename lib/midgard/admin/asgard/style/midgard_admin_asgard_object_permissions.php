<?php
$renderer = $data['controller']->get_datamanager()->get_renderer('form');
$form = $renderer->get_view();

echo $renderer->block($form, 'form_start');
?>
<div class="maa_permissions_assignees">
    <?php
    if (empty($form->children['add_assignee']->vars['value'])) {
        echo $renderer->label($form->children['add_assignee']);
        echo $renderer->widget($form->children['add_assignee']);
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
        foreach ($form->children as $name => $child) {
            if (!str_starts_with($name, $identifier . '__')) {
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
            echo $renderer->widget($child);
            echo '</td>';
        }
    }
    ?>
    </tbody>
</table>
<div class="maa_permissions_footer">
	<?php echo $renderer->block($form, 'form_rest'); ?>
</div>
<?php echo $renderer->block($form, 'form_end'); ?>