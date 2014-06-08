<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<div class="components">
<?php
        if (count($data['dependencies']) > 0)
        {
            echo "<h2>" . $data['l10n_midcom']->get('component depends on') . "</h2>\n";
            echo "<ul>\n";
            foreach ($data['dependencies'] as $dependency)
            {
                if ($component_icon = midcom::get()->componentloader->get_component_icon($dependency))
                {
                    echo "<li><a href=\"{$prefix}__ais/help/{$dependency}/\"><img src=\"" . MIDCOM_STATIC_URL . "/" . $component_icon . "\" alt=\"\" /> {$dependency}</a></li>\n";
                }
                else
                {
                    echo "<li><img src=\"" . MIDCOM_STATIC_URL . "/stock-icons/16x16/cancel.png\" alt=\"\" /> {$dependency} <span class='alert'>Error: This component is not installed!</span></li>\n";
                }
            }
            echo "</ul>\n";
        }
?>
</div>