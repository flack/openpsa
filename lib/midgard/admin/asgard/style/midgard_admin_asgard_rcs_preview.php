<?php
$preview = $data['preview'];
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<div class="rcs_navigation">
<?php
echo $data['rcs_toolbar']->render();
echo $data['rcs_toolbar_2']->render();
?>
</div>
<dl class="midgard_admin_asgard_rcs_diff">
<?php
foreach ($preview as $attribute => $value) {
    if (   $value == ''
        || $value == '0000-00-00'
        || !midcom_services_rcs::is_field_showable($attribute)
        || is_array($value)) {
        continue;
    }

    // Three fold fallback in localization
    echo "<dt>". $data['l10n_midcom']->get($data['l10n']->get($attribute)) ."</dt>\n";
    echo "    <dd>" . htmlentities($value) . "</dd>\n";
}
?>
</dl>
