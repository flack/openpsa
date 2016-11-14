<?php
$label = $data['label_property'];
echo "<h2>";
printf($data['l10n']->get('%s trash'), midgard_admin_asgard_plugin::get_type_label($data['type']));
echo "</h2>";

$shown = array();

function midgard_admin_asgard_trash_type_show($object, $indent = 0, $prefix = '', $enable_undelete = true)
{
    static $persons = array();
    static $shown = array();
    static $url_prefix = '';
    if (!$url_prefix) {
        $url_prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
    }

    if (isset($shown[$object->guid])) {
        return;
    }

    if (!isset($persons[$object->metadata->revisor])) {
        $persons[$object->metadata->revisor] = midcom::get()->auth->get_user($object->metadata->revisor);
    }

    $reflector = midcom_helper_reflector_tree::get($object);
    $icon = $reflector->get_object_icon($object);

    echo "{$prefix}<tr>\n";

    $disabled = '';
    if (!$enable_undelete) {
        $disabled = ' disabled="disabled"';
    }

    $object_label = $reflector->get_object_label($object);
    if (empty($object_label)) {
        $object_label = $object->guid;
    }
    echo "{$prefix}    <td class=\"checkbox\"><input type=\"checkbox\" name=\"undelete[]\"{$disabled} value=\"{$object->guid}\" id=\"guid_{$object->guid}\" /></td>\n";
    echo "{$prefix}    <td class=\"label\" style=\"padding-left: {$indent}px\"><label for=\"guid_{$object->guid}\">{$icon}" . $object_label . "</label></td>\n";
    echo "{$prefix}    <td class=\"nowrap\">" . strftime('%x %X', strtotime($object->metadata->revised)) . "</td>\n";

    if (!empty($persons[$object->metadata->revisor]->guid)) {
        echo "{$prefix}    <td><a href=\"{$url_prefix}__mfa/asgard/object/view/{$persons[$object->metadata->revisor]->guid}/\">{$persons[$object->metadata->revisor]->name}</a></td>\n";
    } else {
        echo "{$prefix}    <td>&nbsp;</td>\n";
    }
    echo "{$prefix}    <td>" . midcom_helper_misc::filesize_to_string($object->metadata->size) . "</td>\n";
    echo "{$prefix}</tr>\n";

    $child_types = midcom_helper_reflector_tree::get_child_objects($object, true);
    if (!empty($child_types)) {
        $child_indent = $indent + 20;
        echo "{$prefix}<tbody class=\"children\">\n";
        foreach ($child_types as $type => $children) {
            if (   count($children) < 10
                || isset($_GET['show_children'][$object->guid][$type])) {
                foreach ($children as $child) {
                    midgard_admin_asgard_trash_type_show($child, $child_indent, "{$prefix}    ", false);
                }
            } else {
                echo "{$prefix}    <tr>\n";
                echo "{$prefix}        <td class=\"label\" style=\"padding-left: {$child_indent}px\" colspan=\"5\"><a href=\"?show_children[{$object->guid}][{$type}]=1\">" . sprintf(midcom::get()->i18n->get_string('show %s %s children', 'midgard.admin.asgard'), count($children), midgard_admin_asgard_plugin::get_type_label($type)) . "</a></td>\n";
                echo "{$prefix}    </tr>\n";
            }
        }

        echo "{$prefix}</tbody>\n";
    }
    $shown[$object->guid] = true;
}

if ($data['trash']) {
    echo "<form method=\"post\">\n";
    echo "<table class=\"trash table_widget\" id=\"batch_process\">\n";
    echo "    <thead>\n";
    echo "        <tr>\n";
    echo "            <th>&nbsp;</th>\n";
    echo "            <th>" . $data['l10n_midcom']->get('title') . "</th>\n";
    echo "            <th>" . $data['l10n']->get('deleted on') . "</th>\n";
    echo "            <th>" . $data['l10n']->get('deleted by') . "</th>\n";
    echo "            <th>" . $data['l10n']->get('size') . "</th>\n";
    echo "        </tr>\n";
    echo "    </thead>\n";
    echo "    <tfoot>\n";
    echo "            <tr>\n";
    echo "            <td colspan=\"5\">\n";
    echo "                <label for=\"select_all\">\n";
    echo "                    <input type=\"checkbox\" name=\"select_all\" id=\"select_all\" value=\"\" onclick=\"jQuery(this).check_all('#batch_process tbody');\" />" . $data['l10n']->get('select all');
    echo "                </label>\n";
    echo "                <label for=\"invert_selection\">\n";
    echo "                    <input type=\"checkbox\" name=\"invert_selection\" id=\"invert_selection\" value=\"\" onclick=\"jQuery(this).invert_selection('#batch_process tbody');\" />" . $data['l10n']->get('invert selection');
    echo "                </label>\n";
    echo "            </td>\n";
    echo "        </tr>\n";
    echo "        <tr>\n";
    echo "            <td colspan=\"5\">\n";
    echo "                <input type=\"submit\" value=\"" . $data['l10n']->get('undelete') . "\" />\n";
    echo "                <input type=\"submit\" name=\"purge\" value=\"" . $data['l10n']->get('purge') . "\" />\n";
    echo "            </td>\n";
    echo "        </tr>\n";
    echo "    </tfoot>\n";
    echo "    <tbody>\n";

    foreach ($data['trash'] as $object) {
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
} else {
    echo "<p>" . $data['l10n']->get('trash is empty') . "</p>\n";
}