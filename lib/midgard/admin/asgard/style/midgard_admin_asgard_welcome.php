<?php
$type_choices = [];
foreach ($data['schema_types'] as $schema_type) {
    $type_choices[$schema_type] = midcom_helper_reflector::get($schema_type)->get_class_label();
}
asort($type_choices);
$type_choices = ['any' => $data['l10n']->get('any')] + $type_choices;

$revised_after_choices = [];

$date = strtotime('1 day ago today');
$revised_after_choices[$date] = $data['l10n']->get('1 day');
$date = strtotime('1 week ago today');
$revised_after_choices[$date] = $data['l10n']->get('1 week');
$date = strtotime('1 month ago today');
$revised_after_choices[$date] = $data['l10n']->get('1 month');
?>

<div id="latest_objects">

    <div class="filter">
        <form name="latest_objects_filter" method="get">
            <div class="type_filter">
                <label for="type_filter"><?php echo $data['l10n']->get('type'); ?></label>
                <select name="type_filter" id="type_filter">
                    <?php
                    foreach ($type_choices as $value => $label) {
                        $selected = '';
                        if (   isset($data['type_filter'])
                            && $data['type_filter'] == $value) {
                            $selected = ' selected="selected"';
                        }
                        echo "<option value=\"{$value}\"{$selected}>{$label}</option>\n";
                    }
                    ?>
                </select>
            </div>
            <div class="revised_after">
                <label for="revised_after"><?php echo $data['l10n']->get('objects revised within'); ?></label>
                <select name="revised_after" id="revised_after">
                    <?php
                    foreach ($revised_after_choices as $value => $label) {
                        $selected = '';
                        if (   isset($data['revised_after'])
                            && $data['revised_after'] == date('Y-m-d', $value)) {
                            $selected = ' selected="selected"';
                        }
                        echo "<option value=\"{$value}\"{$selected}>{$label}</option>\n";
                    }
                    ?>
                </select>
            </div>
            <input type="checkbox" id="only_mine" name="only_mine" value="1" <?php if (isset($data['only_mine']) && $data['only_mine'] == 1) {
                echo ' checked="checked"';
            } ?> />
            <label for="only_mine">
                <?php echo $data['l10n']->get('only mine'); ?>
            </label>
            <input type="submit" name="filter" value="<?php echo $data['l10n']->get('filter'); ?>" />
        </form>
    </div>

    <h2><?php echo $data['l10n']->get('recent changes'); ?></h2>
    <div class="crop-height full-width">
    <?php
    $grid_id = $data['grid']->get_identifier();
    $data['grid']
        ->set_column('title', $data['l10n_midcom']->get('title'), 'width: 150', 'string')
        ->set_column('revised', midcom::get()->i18n->get_string('revised', 'midcom.admin.folder'), 'fixed: true, width: 180, align: "center", formatter: "date", formatoptions: {srcformat: "U", newformat: "ISO8601Long"}')
        ->set_column('revisor', midcom::get()->i18n->get_string('revisor', 'midcom.admin.folder'), 'width: 70')
        ->set_column('approved', $data['l10n_midcom']->get('approved'), 'fixed: true, width: 180, align: "center", formatter: "date", formatoptions: {srcformat: "U", newformat: "ISO8601Long"}')
        ->set_column('revision', midcom::get()->i18n->get_string('revision', 'midcom.admin.folder'), 'fixed: true, width: 95, template: "integer"')

        ->set_option('multiselect', true);

    $data['grid']->render();
    ?>
    <form id="form_&(grid_id);" method="post" action="">
    </form>
    </div>
    <script type="text/javascript">
    midcom_grid_batch_processing.initialize({
        id: '&(grid_id);',
        options: <?php echo json_encode($data['action_options']); ?>,
        submit: "<?php echo $data['l10n_midcom']->get('save') ?>"
    });
    </script>
</div>
