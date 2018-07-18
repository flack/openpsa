<?php
$type_choices = [];
foreach ($data['schema_types'] as $schema_type) {
    if (!isset($data['reflectors'][$schema_type])) {
        $data['reflectors'][$schema_type] = new midcom_helper_reflector($schema_type);
    }

    $type_choices[$schema_type] = $data['reflectors'][$schema_type]->get_class_label();
    asort($type_choices);
}
$type_choices = ['any' => $data['l10n']->get('any')] + $type_choices;

$revised_after_choices = [];
if ($data['config']->get('enable_review_dates')) {
    $review_by_choices = [];
    $revised_after_choices['any'] = $data['l10n']->get('any');
    $review_by_choices['any'] = $data['l10n']->get('any');
    // 1 week
    $date = mktime(0, 0, 0, date('m'), date('d') + 6, date('Y'));
    $review_by_choices[$date] = $data['l10n']->get('1 week');
    // 2 weeks
    $date = mktime(0, 0, 0, date('m'), date('d') + 13, date('Y'));
    $review_by_choices[$date] = $data['l10n']->get('2 weeks');
    // 1 month
    $date = mktime(0, 0, 0, date('m') + 1, date('d'), date('Y'));
    $review_by_choices[$date] = $data['l10n']->get('1 month');
}

// 1 day
$date = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
$revised_after_choices[$date] = $data['l10n']->get('1 day');
// 1 week
$date = mktime(0, 0, 0, date('m'), date('d') - 6, date('Y'));
$revised_after_choices[$date] = $data['l10n']->get('1 week');
// 1 month
$date = mktime(0, 0, 0, date('m') - 1, date('d'), date('Y'));
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
                            && $data['revised_after'] == date('Y-m-d H:i:s\Z', $value)) {
                            $selected = ' selected="selected"';
                        }
                        echo "<option value=\"{$value}\"{$selected}>{$label}</option>\n";
                    }
                    ?>
                </select>
            </div>
            <?php
            if ($data['config']->get('enable_review_dates')) {
                ?>
            <div class="review_by">
                <label for="review_by"><?php echo $data['l10n']->get('objects expiring within'); ?></label>
                <select name="review_by" id="review_by">
                    <?php
                    foreach ($review_by_choices as $value => $label) {
                        $selected = '';
                        if (   isset($data['revised_after'])
                            && $data['review_by'] == $value) {
                            $selected = ' selected="selected"';
                        }
                        echo "<option value=\"{$value}\"{$selected}>{$label}</option>\n";
                    } ?>
                </select>
            </div>
                <?php

            }
            ?>
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
    <form name="latest_objects_mass_action" method="post">
      <table class="results table_widget" id="batch_process">
        <thead>
          <tr>
            <th class="selection">&nbsp;</th>
            <th class="icon">&nbsp;</th>
            <th class="title"><?php echo $data['l10n_midcom']->get('title'); ?></th>
            <?php
            if ($data['config']->get('enable_review_dates')) {
                echo "            <th class=\"review_by\">" . $data['l10n']->get('review date') . "</th>\n";
            } ?>

            <th class="revised"><?php echo midcom::get()->i18n->get_string('revised', 'midcom.admin.folder'); ?></th>
            <th class="revisor"><?php echo midcom::get()->i18n->get_string('revisor', 'midcom.admin.folder'); ?></th>
            <th class="approved"><?php echo $data['l10n_midcom']->get('approved'); ?></th>
            <th class="revision"><?php echo midcom::get()->i18n->get_string('revision', 'midcom.admin.folder'); ?></th>
          </tr>
        </thead>
        <tfoot>
          <tr>
            <td colspan="5">
              <label for="select_all">
                <input type="checkbox" name="select_all" id="select_all" value="" onclick="jQuery(this).check_all('#batch_process tbody');" /><?php echo $data['l10n']->get('select all');?>
              </label>
              <label for="invert_selection">
                <input type="checkbox" name="invert_selection" id="invert_selection" value="" onclick="jQuery(this).invert_selection('#batch_process tbody');" /><?php echo $data['l10n']->get('invert selection'); ?>
              </label>
            </td>
          </tr>
        </tfoot>
    <tbody>
<?php
foreach ($data['revised'] as $row) {
    $link = $data['router']->generate('object_' . $data['default_mode'], ['guid' => $row['guid']]);

    echo "        <tr>\n";
    echo "            <td class=\"selection\"><input type=\"checkbox\" name=\"selections[]\" value=\"{$row['guid']}\" /></td>\n";
    echo "            <td class=\"icon\">" . $row['icon'] . "</td>\n";
    echo "            <td class=\"title\"><a href=\"{$link}\" title=\"{$row['class']}\">" . $row['title'] . "</a></td>\n";

    if (isset($row['review_date'])) {
        echo "            <td class=\"review_by\">" . $row['review_date'] . "</td>\n";
    }

    echo "            <td class=\"revised\">" . strftime('%x %X', $row['revised']) . "</td>\n";
    echo "            <td class=\"revisor\">{$row['revisor']}</td>\n";
    echo "            <td class=\"approved\">{$row['approved']}</td>\n";
    echo "            <td class=\"revision\">{$row['revision']}</td>\n";
    echo "        </tr>\n";
}?>
  </tbody>
</table>
    <script type="text/javascript">
       // <![CDATA[
       jQuery('#batch_process').tablesorter(
       {
           widgets: ['zebra'],
           sortList: [[2,0]]
       });
       // ]]>
    </script>
    <div class="actions">
        <div class="action">
            <select name="mass_action" id="mass_action">
                <option value=""><?php echo $data['l10n']->get('choose action'); ?></option>
                <option value="delete"><?php echo $data['l10n_midcom']->get('delete'); ?></option>
                <option value="approve"><?php echo $data['l10n_midcom']->get('approve'); ?></option>
            </select>
        </div>
        <input type="submit" name="execute_mass_action" value="<?php echo $data['l10n']->get('apply to selected'); ?>" />
    </div>
</form>
</div>
