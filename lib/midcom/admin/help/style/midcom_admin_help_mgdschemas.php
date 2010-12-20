<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
echo "<h1>" . sprintf($_MIDCOM->i18n->get_string('mgdschemas in %s', 'midcom.admin.help'),
                $_MIDCOM->i18n->get_string($data['component'], $data['component'])) . "</h1>\n";

if (count($data['mgdschemas']) > 0)
{
    $marker = new net_nehmer_markdown_markdown();
    echo "<dl>\n";
    foreach ($data['properties'] as $schema => $properties)
    {
        echo "<dt id=\"{$schema}\">{$schema}</dt>\n";
        echo "<dd>\n";
        echo "    <table>\n";
        echo "        <tbody>\n";
        echo "            <tr>\n";
        echo "                <th class='property'>" . $_MIDCOM->i18n->get_string('property', 'midcom.admin.help') . "</th>\n";
        echo "                <th>" . $_MIDCOM->i18n->get_string('description', 'midcom.admin.help') . "</th>\n";
        echo "            </tr>\n";

        $i = 1;
        foreach ($properties as $key=>$val)
        {
            $propname = $key;

            $proplink = "";
            $proplink_description = '';
            if ($val['link'])
            {
                $linked_component = '';
                if(substr($val['link_name'], 0, 8) == 'midgard_')
                {
                    $linked_component = 'midcom';
                }
                else
                {
                    $linked_component = $_MIDCOM->dbclassloader->get_component_for_class($val['link_name']);
                }
                if ($linked_component)
                {
                    $proplink = "<a href='{$prefix}__ais/help/{$linked_component}/mgdschemas/#{$val['link_name']}' title='{$linked_component}/{$val['link_name']}::{$val['link_target']}'>{$val['link_name']}:{$val['link_target']}</a>";
                    $proplink_description = "\n\n**This property links to {$val['link_name']}:{$val['link_target']}**";
                }
            }

            $mod = ($i/2 == round($i/2))?" even":" odd";
            $i++;

            echo "            <tr>\n";
            echo "                <td class='property{$mod}'><span class='mgdtype'>{$val['midgard_type']}</span> {$propname}<br/>{$proplink}</td>\n";
            echo "                <td class='{$mod}'>" . $marker->render($val['value'].$proplink_description) . "</td>\n";
            echo "            </tr>\n";
        }
        echo "        </tbody>\n";
        echo "    </table>\n";
        echo "</dd>\n";

        // Reflect the methods too
        $reflectionclass = new ReflectionClass($schema);
        $reflectionmethods = $reflectionclass->getMethods();
        if ($reflectionmethods)
        {
            echo "<dd>\n";
            echo "    <table>\n";
            echo "        <tbody>\n";
            echo "            <tr>\n";
            echo "                <th class='property'>" . $_MIDCOM->i18n->get_string('signature', 'midcom.admin.help') . "</th>\n";
            echo "                <th>" . $_MIDCOM->i18n->get_string('description', 'midcom.admin.help') . "</th>\n";
            echo "            </tr>\n";

            foreach ($reflectionmethods as $reflectionmethod)
            {
                // Generate method signature
                $signature  = '';
                $signature .= '<span class="method_modifiers">' . implode(' ', Reflection::getModifierNames($reflectionmethod->getModifiers())) . '</span> ';
                if ($reflectionmethod->returnsReference())
                {
                    $signature .= ' & ';
                }

                $method_url = 'http://www.midgard-project.org/documentation/' . rawurlencode('MgdSchema method ' . $reflectionmethod->getName());
                $signature .= '<span class="method_name"><a href="' . $method_url . '">' . $reflectionmethod->getName() . '</a></span>';

                $signature .= '(';
                $parametersdata = array();
                $parameters = $reflectionmethod->getParameters();
                foreach ($parameters as $reflectionparameter)
                {
                    $parametersignature = '';

                    if ($reflectionparameter->isPassedByReference())
                    {
                        $parametersignature .= ' &';
                    }

                    $parametersignature .= '$' . str_replace(' ', '_', $reflectionparameter->getName());

                    if ($reflectionparameter->isDefaultValueAvailable())
                    {
                        $parametersignature .= ' = ' . $reflectionparameter->getDefaultValue();
                    }

                    if ($reflectionparameter->isOptional())
                    {
                        $parametersignature = "[{$parametersignature}]";
                    }

                    $parametersdata[] = $parametersignature;
                }
                $signature .= implode(', ', $parametersdata) . ')';

                echo "            <tr>\n";
                echo "                <td>{$signature}</td>\n";
                echo "                <td>" . $reflectionmethod->getDocComment();

                echo "</td>\n";
                echo "            </tr>\n";
            }

            echo "        </tbody>\n";
            echo "    </table>\n";
            echo "</dd>\n";
        }
    }
    echo "</dl>\n";
}
else
{
    echo "<p>" . $_MIDCOM->i18n->get_string('no mgdschema found', 'midcom.admin.help') . "</p>";
}
?>