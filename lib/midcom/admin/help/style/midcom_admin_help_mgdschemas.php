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

        $i = 1;
        foreach ($properties as $propname => $val) {
            $proplink = "";
            $description = preg_replace('/ *\n */', "\n", $val['value']);
            if (   $val['link']
                && $linked_component = midcom::get()->dbclassloader->get_component_for_class($val['link_name'])) {
                $proplink = "<a href='{$prefix}__ais/help/{$linked_component}/mgdschemas/#{$val['link_name']}' title='{$linked_component}/{$val['link_name']}::{$val['link_target']}'>{$val['link_name']}:{$val['link_target']}</a>";
                $classname = str_replace('_', '\\_', $val['link_name']);
                $description .= "\n\n**This property links to {$classname}:{$val['link_target']}**";
            }

            $mod = ($i % 2 == 0) ? " even":" odd";
            $i++;

            echo "            <tr>\n";
            echo "                <td class='property{$mod}'><span class='mgdtype'>{$val['midgard_type']}</span> {$propname}<br/>{$proplink}</td>\n";
            echo "                <td class='{$mod}'>" . MarkdownExtra::defaultTransform($description) . "</td>\n";
            echo "            </tr>\n";
        }
        echo "        </tbody>\n";
        echo "    </table>\n";
        echo "</dd>\n";

        // Reflect the methods too
        $reflectionclass = new ReflectionClass($schema);
        if ($reflectionmethods = $reflectionclass->getMethods()) {
            echo "<dd>\n";
            echo "    <table>\n";
            echo "        <tbody>\n";
            echo "            <tr>\n";
            echo "                <th class='property'>" . $data['l10n']->get('signature') . "</th>\n";
            echo "                <th>" . $data['l10n']->get('description') . "</th>\n";
            echo "            </tr>\n";

            foreach ($reflectionmethods as $reflectionmethod) {
                // Generate method signature
                $signature  = '';
                $signature .= '<span class="method_modifiers">' . implode(' ', Reflection::getModifierNames($reflectionmethod->getModifiers())) . '</span> ';
                if ($reflectionmethod->returnsReference()) {
                    $signature .= ' & ';
                }

                $signature .= '<span class="method_name">' . $reflectionmethod->getName() . '</span>';

                $signature .= '(';
                $parametersdata = [];
                $parameters = $reflectionmethod->getParameters();
                foreach ($parameters as $reflectionparameter) {
                    $parametersignature = '';

                    if ($reflectionparameter->isPassedByReference()) {
                        $parametersignature .= ' &';
                    }

                    $parametersignature .= '$' . str_replace(' ', '_', $reflectionparameter->getName());

                    if ($reflectionparameter->isDefaultValueAvailable()) {
                        $default = $reflectionparameter->getDefaultValue();
                        if (is_array($default)) {
                            $default = 'array(' . implode(', ', $default) . ')';
                        }
                        $parametersignature .= ' = ' . $default;
                    }

                    if ($reflectionparameter->isOptional()) {
                        $parametersignature = "[{$parametersignature}]";
                    }

                    $parametersdata[] = $parametersignature;
                }
                $signature .= implode(', ', $parametersdata) . ')';

                echo "            <tr>\n";
                echo "                <td>{$signature}</td>\n";
                echo "                <td>" . $reflectionmethod->getDocComment() . "</td>\n";
                echo "            </tr>\n";
            }

            echo "        </tbody>\n";
            echo "    </table>\n";
            echo "</dd>\n";
        }
    }
    echo "</dl>\n";
} else {
    echo "<p>" . $data['l10n']->get('no mgdschema found') . "</p>";
}
