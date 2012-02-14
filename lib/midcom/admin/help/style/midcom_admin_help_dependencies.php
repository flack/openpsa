<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<div class="components">
<?php
        if (count($data['dependencies']) > 0)
        {
            echo "<h2>" . $_MIDCOM->i18n->get_string('component depends on', 'midcom') . "</h2>\n";
            echo "<ul>\n";
            foreach ($data['dependencies'] as $dependency)
            {
                $component_icon = midcom::get('componentloader')->get_component_icon($dependency);
                if (!$component_icon)
                {
                    echo "<li><img src=\"" . MIDCOM_STATIC_URL . "/stock-icons/16x16/cancel.png\" alt=\"\" /> {$dependency} <span class='alert'>Error: This component is not installed!</span></li>\n";
                }
                else
                {
                    echo "<li><a href=\"{$prefix}__ais/help/{$dependency}/\"><img src=\"" . MIDCOM_STATIC_URL . "/" . $component_icon . "\" alt=\"\" /> {$dependency}</a></li>\n";
                }
            }
            echo "</ul>\n";
        }
?>
</div>