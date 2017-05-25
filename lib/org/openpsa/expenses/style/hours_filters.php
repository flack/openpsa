<div class="area">
    <?php
    $data['qf']->render();
    echo midcom::get()->i18n->get_string('group by', 'org.openpsa.core') . ': ';
    echo '<select id="chgrouping_' . $data['grid']->get_identifier() . '">';
    foreach ($data['group_options'] as $name => $label) {
        echo '<option value="' . $name . '">' . $label . "</option>\n";
    }
    echo '<option value="clear">' . midcom::get()->i18n->get_string('no grouping', 'org.openpsa.core') . "</option>\n";
    echo '</select>';
    ?>
</div>