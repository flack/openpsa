<div class="grid-controls">
<?php
if (array_key_exists('qf', $data)) {
    $data['qf']->render();
}

$grid_id = $data['view_identifier'] . '_tasks_grid';

echo ' ' . midcom::get()->i18n->get_string('group by', 'org.openpsa.core') . ': ';
echo '<select id="chgrouping_' . $grid_id . '">';
echo '<option value="status">' . $data['l10n']->get('status') . "</option>\n";
if (!$data['is_single_project']) {
    echo '<option value="project">' . $data['l10n']->get('project') . "</option>\n";
    echo '<option value="customer">' . $data['l10n']->get('customer') . "</option>\n";
}

echo '<option value="manager">' . $data['l10n']->get('manager') . "</option>\n";
echo '<option value="clear">' . midcom::get()->i18n->get_string('no grouping', 'org.openpsa.core') . "</option>\n";
echo '</select>';
?>
</div>