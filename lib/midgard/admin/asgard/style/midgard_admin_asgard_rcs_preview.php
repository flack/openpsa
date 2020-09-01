<div class="rcs_navigation">
<?php
echo $data['rcs_toolbar']->render();
echo $data['rcs_toolbar_2']->render();
?>
</div>
<dl class="midgard_admin_asgard_rcs_diff">
<?php
foreach ($data['preview'] as $attribute => $value) {
    // Three fold fallback in localization
    echo "<dt>" . $data['handler']->translate($attribute) . "</dt>\n";
    echo "    <dd>" . htmlentities($value) . "</dd>\n";
}
?>
</dl>
