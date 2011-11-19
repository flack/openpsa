<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);

$type_choices = array();
foreach (midcom_connection::get_schema_types() as $schema_type)
{
    if (!isset($data['reflectors'][$schema_type]))
    {
        $data['reflectors'][$schema_type] = new midcom_helper_reflector($schema_type);
    }

    $type_choices[$schema_type] = $data['reflectors'][$schema_type]->get_class_label();
    asort($type_choices);
}
$type_choices = Array('any' => $_MIDCOM->i18n->get_string('any', 'midgard.admin.asgard')) + $type_choices;

$revised_after_choices = array();
if ($data['config']->get('enable_review_dates'))
{
    $review_by_choices = array();
    $revised_after_choices['any'] = $_MIDCOM->i18n->get_string('any', 'midgard.admin.asgard');
    $review_by_choices['any'] = $_MIDCOM->i18n->get_string('any', 'midgard.admin.asgard');
    // 1 week
    $date = mktime(0, 0, 0, date('m'), date('d') + 6, date('Y'));
    $review_by_choices[$date] = $_MIDCOM->i18n->get_string('1 week', 'midgard.admin.asgard');
    // 2 weeks
    $date = mktime(0, 0, 0, date('m'), date('d') + 13, date('Y'));
    $review_by_choices[$date] = $_MIDCOM->i18n->get_string('2 weeks', 'midgard.admin.asgard');
    // 1 month
    $date = mktime(0, 0, 0, date('m') + 1, date('d'), date('Y'));
    $review_by_choices[$date] = $_MIDCOM->i18n->get_string('1 month', 'midgard.admin.asgard');
}

// 1 day
$date = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
$revised_after_choices[$date] = $_MIDCOM->i18n->get_string('1 day', 'midgard.admin.asgard');
// 1 week
$date = mktime(0, 0, 0, date('m'), date('d') - 6, date('Y'));
$revised_after_choices[$date] = $_MIDCOM->i18n->get_string('1 week', 'midgard.admin.asgard');
// 1 month
$date = mktime(0, 0, 0, date('m') - 1, date('d'), date('Y'));
$revised_after_choices[$date] = $_MIDCOM->i18n->get_string('1 month', 'midgard.admin.asgard');
?>

<div id="latest_objects">

    <div class="filter">
        <form name="latest_objects_filter" method="get">
            <div class="type_filter">
                <label for="type_filter"><?php echo $_MIDCOM->i18n->get_string('type', 'midgard.admin.asgard'); ?></label>
                <select name="type_filter" id="type_filter">
                    <?php
                    foreach ($type_choices as $value => $label)
                    {
                        $selected = '';
                        if (   isset($data['type_filter'])
                            && $data['type_filter'] == $value)
                        {
                            $selected = ' selected="selected"';
                        }
                        echo "<option value=\"{$value}\"{$selected}>{$label}</option>\n";
                    }
                    ?>
                </select>
            </div>
            <div class="revised_after">
                <label for="revised_after"><?php echo $_MIDCOM->i18n->get_string('objects revised within', 'midgard.admin.asgard'); ?></label>
                <select name="revised_after" id="revised_after">
                    <?php
                    foreach ($revised_after_choices as $value => $label)
                    {
                        $selected = '';
                        if (   isset($data['revised_after'])
                            && $data['revised_after'] == date('Y-m-d H:i:s\Z', $value))
                        {
                            $selected = ' selected="selected"';
                        }
                        echo "<option value=\"{$value}\"{$selected}>{$label}</option>\n";
                    }
                    ?>
                </select>
            </div>
            <?php
            if ($data['config']->get('enable_review_dates'))
            {
                ?>
            <div class="review_by">
                <label for="review_by"><?php echo $_MIDCOM->i18n->get_string('objects expiring within', 'midgard.admin.asgard'); ?></label>
                <select name="review_by" id="review_by">
                    <?php
                    foreach ($review_by_choices as $value => $label)
                    {
                        $selected = '';
                        if (   isset($data['revised_after'])
                            && $data['review_by'] == $value)
                        {
                            $selected = ' selected="selected"';
                        }
                        echo "<option value=\"{$value}\"{$selected}>{$label}</option>\n";
                    }
                    ?>
                </select>
            </div>
                <?php
            }
            ?>
            <input type="checkbox" id="only_mine" name="only_mine" value="1" <?php if (isset($data['only_mine']) && $data['only_mine'] == 1) { echo ' checked="checked"'; } ?> />
            <label for="only_mine">
                <?php echo $_MIDCOM->i18n->get_string('only mine', 'midgard.admin.asgard'); ?>
            </label>
            <input type="submit" name="filter" value="<?php echo $_MIDCOM->i18n->get_string('filter', 'midgard.admin.asgard'); ?>" />
        </form>
    </div>

<?php
if (count($data['revised']) > 0)
{
    $revisors = array();
    echo "    <form name=\"latest_objects_mass_action\" method=\"post\">";
    echo "<table class=\"results table_widget\" id =\"batch_process\">\n";
    echo "    <thead>\n";
    echo "        <tr>\n";
    echo "            <th class=\"selection\">&nbsp;</th>\n";
    echo "            <th class=\"icon\">&nbsp;</th>\n";
    echo "            <th class=\"title\">" . $_MIDCOM->i18n->get_string('title', 'midcom') . "</th>\n";

    if ($data['config']->get('enable_review_dates'))
    {
        echo "            <th class=\"review_by\">" . $_MIDCOM->i18n->get_string('review date', 'midgard.admin.asgard') . "</th>\n";
    }

    echo "            <th class=\"revised\">" . $_MIDCOM->i18n->get_string('revised', 'midcom.admin.folder') . "</th>\n";
    echo "            <th class=\"revisor\">" . $_MIDCOM->i18n->get_string('revisor', 'midcom.admin.folder') . "</th>\n";
    echo "            <th class=\"approved\">" . $_MIDCOM->i18n->get_string('approved', 'midcom') . "</th>\n";
    echo "            <th class=\"revision\">" . $_MIDCOM->i18n->get_string('revision', 'midcom.admin.folder') . "</th>\n";
    echo "        </tr>\n";
    echo "    </thead>\n";
    echo "    <tfoot>\n";
    echo "            <tr>\n";
    echo "            <td colspan=\"5\">\n";
    echo "                <label for=\"select_all\">\n";
    echo "                    <input type=\"checkbox\" name=\"select_all\" id=\"select_all\" value=\"\" onclick=\"jQuery(this).check_all('#batch_process tbody');\" />" . $_MIDCOM->i18n->get_string('select all', 'midgard.admin.asgard');
    echo "                </label>\n";
    echo "                <label for=\"invert_selection\">\n";
    echo "                    <input type=\"checkbox\" name=\"invert_selection\" id=\"invert_selection\" value=\"\" onclick=\"jQuery(this).invert_selection('#batch_process tbody');\" />" . $_MIDCOM->i18n->get_string('invert selection', 'midgard.admin.asgard');
    echo "                </label>\n";
    echo "            </td>\n";
    echo "        </tr>\n";
    echo "    </tfoot>\n";
    echo "    <tbody>\n";

    foreach ($data['revised'] as $object)
    {
        $class = get_class($object);
        $approved = $object->metadata->approved;
        $approved_str = strftime('%x %X', $approved);
        if ($approved == 0  || $approved < $object->metadata->revised)
        {
            $approved_str = $_MIDCOM->i18n->get_string('not approved', 'midgard.admin.asgard');
        }
        $title = substr($data['reflectors'][$class]->get_object_label($object), 0, 60);
        if (empty($title))
        {
            $title = '[' . $_MIDCOM->i18n->get_string('no title', 'midgard.admin.asgard') . ']';
        }

        if (!isset($revisors[$object->metadata->revisor]))
        {
            $revisors[$object->metadata->revisor] = $_MIDCOM->auth->get_user($object->metadata->revisor);
        }

        echo "        <tr>\n";
        echo "            <td class=\"selection\"><input type=\"checkbox\" name=\"selections[]\" value=\"{$object->guid}\" /></td>\n";
        echo "            <td class=\"icon\">" . $data['reflectors'][$class]->get_object_icon($object) . "</td>\n";
        echo "            <td class=\"title\"><a href=\"{$prefix}__mfa/asgard/object/{$data['default_mode']}/{$object->guid}/\" title=\"{$class}\">" . $title . "</a></td>\n";

        if ($data['config']->get('enable_review_dates'))
        {
            $review_date = $object->get_parameter('midcom.helper.metadata', 'review_date');
            if (!$review_date)
            {
                echo "            <td class=\"review_by\">N/A</td>\n";
            }
            else
            {
                echo "            <td class=\"review_by\">" . strftime('%x', $review_date) . "</td>\n";
            }
        }

        echo "            <td class=\"revised\">" . strftime('%x %X', $object->metadata->revised) . "</td>\n";
        echo "            <td class=\"revisor\">{$revisors[$object->metadata->revisor]->name}</td>\n";
        echo "            <td class=\"approved\">{$approved_str}</td>\n";
        echo "            <td class=\"revision\">{$object->metadata->revision}</td>\n";
        echo "        </tr>\n";
    }

    echo "    </tbody>\n";
    echo "</table>\n";
    echo "<script type=\"text/javascript\">\n";
    echo "        // <![CDATA[\n";
    echo "            jQuery('#batch_process').tablesorter(\n";
    echo "            {\n ";
    echo "                widgets: ['zebra'],\n";
    echo "                sortList: [[2,0]]\n";
    echo "            });\n";
    echo "        // ]]>\n";
    echo "    </script>\n";
?>
        <div class="actions">
            <div class="action">
                <select name="mass_action" id="mass_action">
                    <option value=""><?php echo $_MIDCOM->i18n->get_string('choose action', 'midgard.admin.asgard'); ?></option>
                    <option value="delete"><?php echo $_MIDCOM->i18n->get_string('delete', 'midcom'); ?></option>
                    <option value="approve"><?php echo $_MIDCOM->i18n->get_string('approve', 'midcom'); ?></option>
                </select>
            </div>
            <input type="submit" name="execute_mass_action" value="<?php echo $_MIDCOM->i18n->get_string('apply to selected', 'midgard.admin.asgard'); ?>" />
        </div>
    </form>

<?php
}
else
{
    $activities = midcom_helper_activitystream_activity_dba::get($data['config']->get('last_visited_size'));
    if (count($activities) > 0)
    {
        $reflectors = Array();

        echo "<h2>" . $_MIDCOM->i18n->get_string('latest activities', 'midcom.helper.activitystream') . "</h2>\n";
        echo "<table class=\"results table_widget\" id =\"last_visited\">\n";
        echo "    <thead>\n";
        echo "        <tr>\n";
        echo "            <th class=\"icon\">&nbsp;</th>\n";
        echo "            <th class=\"title\">" . $_MIDCOM->i18n->get_string('title', 'midcom') . "</th>\n";
        echo "            <th class=\"revised\">" . $_MIDCOM->i18n->get_string('date', 'midcom') . "</th>\n";
        echo "            <th class=\"revisor\">" . $_MIDCOM->i18n->get_string('actor', 'midcom.helper.activitystream') . "</th>\n";
        echo "            <th class=\"action\">" . $_MIDCOM->i18n->get_string('action', 'midcom.helper.activitystream') . "</th>\n";
        echo "        </tr>\n";
        echo "    </thead>\n";
        echo "    <tbody>\n";
        foreach ($activities as $activity)
        {
            try
            {
                $object = $_MIDCOM->dbfactory->get_object_by_guid($activity->target);
            }
            catch (midcom_error $e)
            {
                if (midcom_connection::get_error() == MGD_ERR_OBJECT_DELETED)
                {
                    // TODO: Visualize deleted objects somehow
                }
                continue;
            }

            if (!isset($actors))
            {
                $actors = array();
            }
            if (!isset($actors[$activity->actor]))
            {
                try
                {
                    $actors[$activity->actor] = new midcom_db_person($activity->actor);
                }
                catch (midcom_error $e)
                {
                    $actors[$activity->actor] = new midcom_db_person();
                }
            }

            $class = get_class($object);
            if (!array_key_exists($class, $reflectors))
            {
                $reflectors[$class] = new midcom_helper_reflector($object);
            }

            $title = htmlspecialchars($reflectors[$class]->get_object_label($object));
            if (empty($title))
            {
                $title = $object->guid;
            }

            echo "        <tr>\n";
            echo "            <td class=\"icon\">" . $reflectors[$class]->get_object_icon($object) . "</td>\n";
            echo "            <td class=\"title\"><a href=\"{$prefix}__mfa/asgard/object/{$data['default_mode']}/{$object->guid}/\" title=\"{$class}\">" . $title . "</a></td>\n";
            echo "            <td class=\"revised\">" . strftime('%x %X', $activity->metadata->published) . "</td>\n";
            echo "            <td class=\"revisor\">{$actors[$activity->actor]->name}</td>\n";
            echo "            <td class=\"revision\">{$activity->summary}</td>\n";
            echo "        </tr>\n";
        }
        echo "    </tbody>\n";
        echo "</table>\n";
        echo "<script type=\"text/javascript\">\n";
        echo "        // <![CDATA[\n";
        echo "            jQuery('#last_visited').tablesorter(\n";
        echo "            {\n ";
        echo "                widgets: ['zebra']\n";
        echo "            });\n";
        echo "        // ]]>\n";
        echo "    </script>\n";
      }
  }
?>

</div>
