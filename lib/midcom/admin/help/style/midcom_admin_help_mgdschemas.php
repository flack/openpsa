<?php
echo "<h1>" . sprintf($data['l10n']->get('mgdschemas in %s'), midcom::get()->i18n->get_string($data['component'], $data['component'])) . "</h1>\n";

if (!empty($data['mgdschemas'])) {
    echo "<dl>\n";
    foreach ($data['properties'] as $schema => $properties) {
        echo "<dt id=\"{$schema}\">{$schema}</dt>\n";
        echo "<dd>\n";
        echo "    <table>\n";
        echo "        <tbody>\n";
        echo "            <tr>\n";
        echo "                <th class='property'>" . $data['l10n']->get('property') . "</th>\n";
        echo "                <th class='mgdtype'>" . midcom::get()->i18n->get_string('type', 'midgard.admin.asgard') . "</th>\n";
        echo "                <th>" . $data['l10n']->get('description') . "</th>\n";
        echo "            </tr>\n";

        foreach ($properties as $propname => $val) {
            $description = preg_replace('/ *\n */', "\n", $val['value']);
            if (   $val['link']
                && $linked_component = midcom::get()->dbclassloader->get_component_for_class($val['link_name'])) {
                $proplink = $data['router']->generate('help', ['component' => $linked_component, 'help_id' => 'mgdschemas']);
                $proplink = "<a href='{$proplink}#{$val['link_name']}' title='{$linked_component}/{$val['link_name']}::{$val['link_target']}'>{$val['link_name']}:{$val['link_target']}</a>";
                $description .= "<div class='proplink'>This property links to {$proplink}</div>";
            }

            echo "            <tr>\n";
            echo "                <td class='property'>{$propname}</td>\n";
            echo "                <td class='mgdtype'>{$val['midgard_type']}</td>\n";
            echo "                <td class='description'><p>" . trim($description) . "<p></td>\n";
            echo "            </tr>\n";
        }
        echo "        </tbody>\n";
        echo "    </table>\n";
        echo "</dd>\n";
    }
    echo "</dl>\n";
} else {
    echo "<p>" . $data['l10n']->get('no mgdschema found') . "</p>";
}
