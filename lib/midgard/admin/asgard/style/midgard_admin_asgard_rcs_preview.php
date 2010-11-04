<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
$preview = $data['preview'];
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<div class="rcs_navigation">
<?php
echo $data['rcs_toolbar']->render();
echo $data['rcs_toolbar_2']->render();
?>
</div>
<dl class="midgard_admin_asgard_rcs_diff">
<?php
foreach ($preview as $attribute => $value) 
{
    if ($value == '')
    {
        continue;
    }
    
    if ($value == '0000-00-00')
    {
        continue;
    }
    
    if (!midgard_admin_asgard_handler_object_rcs::is_field_showable($attribute))
    {
        continue;
    }
    
    if (is_array($value))
    {
        continue;
    }
    
    // Three fold fallback in localization
    echo "<dt>". $data['l10n_midcom']->get($data['l10n']->get($attribute)) ."</dt>\n";
    echo "    <dd>" . nl2br($value) . "</dd>\n";
}
?>
</dl>
