<?php
use Michelf\MarkdownExtra;

$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
echo "<h1>" . sprintf($data['l10n']->get('mgdschemas in %s'), midcom::get()->i18n->get_string($data['component'], $data['component'])) . "</h1>\n";

if (count($data['mgdschemas']) > 0) {
    echo "<dl>\n";
    foreach ($data['properties'] as $schema => $properties) {
        echo "<dt id=\"{$schema}\">{$schema}</dt>\n";
        echo "<dd>\n";
        echo "    <table>\n";
        echo "        <tbody>\n";
        echo "            <tr>\n";
        echo "                <th class='property'>" . $data['l10n']->get('property') . "</th>\n";
        echo "                <th>" . $data['l10n']->get('description') . "</th>\n";
        echo "            </tr>\n";

        foreach ($properties as $propname => $val) {
            $proplink = "";
            $description = preg_replace('/ *\n */', "\n", $val['value']);
            if (   $val['link']
                && $linked_component = midcom::get()->dbclassloader->get_component_for_class($val['link_name'])) {
                $proplink = "<a href='{$prefix}__ais/help/{$linked_component}/mgdschemas/#{$val['link_name']}' title='{$linked_component}/{$val['link_name']}::{$val['link_target']}'>{$val['link_name']}:{$val['link_target']}</a>";
                $classname = str_replace('_', '\\_', $val['link_name']);
                $description .= "\n\n**This property links to {$classname}:{$val['link_target']}**";
            }

            echo "            <tr>\n";
            echo "                <td class='property'><span class='mgdtype'>{$val['midgard_type']}</span> {$propname}<br/>{$proplink}</td>\n";
            echo "                <td>" . MarkdownExtra::defaultTransform($description) . "</td>\n";
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
