<?php
$label = $data['label_property'];
echo "<h2>";
echo sprintf($_MIDCOM->i18n->get_string('%s trash', 'midgard.admin.asgard'), midgard_admin_asgard_plugin::get_type_label($data['type']));
echo "</h2>";

$shown = array();

function midgard_admin_asgard_trash_type_show($object, $indent = 0, $prefix = '', $enable_undelete = true)
{
    static $persons = array();
    static $shown = array();
    static $url_prefix = '';
    if (!$url_prefix)
    {
        $url_prefix =$_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
    }

    if (isset($shown[$object->guid]))
    {
        return;
    }

    if (!isset($persons[$object->metadata->revisor]))
    {
        $persons[$object->metadata->revisor] = $_MIDCOM->auth->get_user($object->metadata->revisor);
    }

    $reflector = midcom_helper_reflector_tree::get($object);
    $icon = $reflector->get_object_icon($object);

    echo "{$prefix}<tr>\n";

    $disabled = '';
    if (!$enable_undelete)
    {
        $disabled = ' disabled="disabled"';
    }

    echo "{$prefix}    <td class=\"checkbox\"><input type=\"checkbox\" name=\"undelete[]\"{$disabled} value=\"{$object->guid}\" id=\"guid_{$object->guid}\" /></td>\n";
    echo "{$prefix}    <td class=\"label\" style=\"padding-left: {$indent}px\"><label for=\"guid_{$object->guid}\">{$icon}" . $reflector->get_object_label($object) . "</label></td>\n";
    echo "{$prefix}    <td class=\"nowrap\">" . strftime('%x %X', strtotime($object->metadata->revised)) . "</td>\n";

    if (isset($persons[$object->metadata->revisor]->guid))
    {
        echo "{$prefix}    <td><a href=\"{$url_prefix}__mfa/asgard/object/view/{$persons[$object->metadata->revisor]->guid}/\">{$persons[$object->metadata->revisor]->name}</a></td>\n";
    }
    else
    {
        echo "{$prefix}    <td>&nbsp;</td>\n";
    }
    echo "{$prefix}    <td>" . midcom_helper_misc::filesize_to_string($object->metadata->size) . "</td>\n";
    echo "{$prefix}</tr>\n";

    $child_types = $reflector->get_child_objects($object, true);
    if (   is_array($child_types)
        && count($child_types) > 0)
    {
        $child_indent = $indent + 20;
        echo "{$prefix}<tbody class=\"children\">\n";
        foreach ($child_types as $type => $children)
        {
            if (   count($children) < 10
                || isset($_GET['show_children'][$object->guid][$type]))
            {
                foreach ($children as $child)
                {
                    midgard_admin_asgard_trash_type_show($child, $child_indent, "{$prefix}    ", false);
                }
            }
            else
            {
                echo "{$prefix}    <tr>\n";
                echo "{$prefix}        <td class=\"label\" style=\"padding-left: {$child_indent}px\" colspan=\"5\"><a href=\"?show_children[{$object->guid}][{$type}]=1\">" . sprintf($_MIDCOM->i18n->get_string('show %s %s children', 'midgard.admin.asgard'), count($children), midgard_admin_asgard_plugin::get_type_label($type)) . "</a></td>\n";
                echo "{$prefix}    </tr>\n";
            }
        }

        echo "{$prefix}</tbody>\n";
    }
    $shown[$object->guid] = true;
}

if ($data['trash'])
{
    echo "<form method=\"post\">\n";
    echo "<table class=\"trash table_widget\" id=\"batch_process\">\n";
    echo "    <thead>\n";
    echo "        <tr>\n";
    echo "            <th>&nbsp;</th>\n";
    echo "            <th>" . $_MIDCOM->i18n->get_string('title', 'midcom') . "</th>\n";
    echo "            <th>" . $_MIDCOM->i18n->get_string('deleted on', 'midgard.admin.asgard') . "</th>\n";
    echo "            <th>" . $_MIDCOM->i18n->get_string('deleted by', 'midgard.admin.asgard') . "</th>\n";
    echo "            <th>" . $_MIDCOM->i18n->get_string('size', 'midgard.admin.asgard') . "</th>\n";
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
    echo "        <tr>\n";
    echo "            <td colspan=\"5\">\n";
    echo "                <input type=\"submit\" value=\"" . $_MIDCOM->i18n->get_string('undelete', 'midgard.admin.asgard') . "\" />\n";
    echo "                <input type=\"submit\" name=\"purge\" value=\"" . $_MIDCOM->i18n->get_string('purge', 'midgard.admin.asgard') . "\" />\n";
    echo "            </td>\n";
    echo "        </tr>\n";
    echo "    </tfoot>\n";
    echo "    <tbody>\n";

    foreach ($data['trash'] as $object)
    {
        midgard_admin_asgard_trash_type_show($object, 0, '        ');
    }

    echo "    </tbody>\n";
    echo "</table>\n";
    echo "</form>\n";
    echo "<script type=\"text/javascript\">\n";
    echo "// <![CDATA[\n";
    echo "jQuery('#batch_process').tablesorter(\n";
    echo "  {\n";
    echo "      widgets: ['zebra'],\n";
    echo "      sortList: [[1,0]]\n";
    echo "  });\n";
    echo "// ]]>\n";
    echo "</script>\n";
    echo $data['qb']->show_pages();
}
else
{
    echo "<p>" . $_MIDCOM->i18n->get_string('trash is empty', 'midgard.admin.asgard') . "</p>\n";
}
?>