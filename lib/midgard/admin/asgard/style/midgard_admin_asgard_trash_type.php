<?php
echo "<h2>";
printf($data['l10n']->get('%s trash'), midgard_admin_asgard_plugin::get_type_label($data['type']));
echo "</h2>";

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
        $data['handler']->show_type($object, 0, '        ');
    }

    echo "    </tbody>\n";
    echo "</table>\n";
    echo "</form>\n";
    echo "<script type=\"text/javascript\">\n";
    echo "jQuery('#batch_process').tablesorter({\n";
    echo "    sortList: [[1,0]]\n";
    echo "});\n";
    echo "</script>\n";
    echo $data['qb']->show_pages();
} else {
    echo "<p>" . $data['l10n']->get('trash is empty') . "</p>\n";
}
