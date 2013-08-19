<?php
if (isset($_POST['midcom_helper_datamanager_dummy_field_rules']))
{
    $editor_content = $_POST['midcom_helper_datamanager_dummy_field_rules'];
}
else
{
    $editor_content = org_openpsa_helpers::array2code($data['campaign']->rules);
}
?>
<div class="main">
    <form name="org_openpsa_directmarketing_rules_advanced_editor" enctype="multipart/form-data" method="post" class="datamanager2">
        <fieldset class="area">
            <legend><?php echo $data['l10n']->get('edit rules'); ?></legend>
            <label for="midcom_helper_datamanager2_dummy_field_rules" id="midcom_helper_datamanager2_dummy_field_rules_label">
                <textarea id="midcom_helper_datamanager2_dummy_field_rules" name="midcom_helper_datamanager2_dummy_field_rules" rows="25" cols="50" class="longtext" >&(editor_content);</textarea>
            </label>
        </fieldset>
        <div class="form_toolbar">
            <input name="midcom_helper_datamanager2_save[0]" accesskey="s" class="save" value="<?php echo $data['l10n_midcom']->get('save'); ?>" type="submit" />
            <input name="midcom_helper_datamanager2_cancel[0]" class="cancel" value="<?php echo $data['l10n_midcom']->get('cancel'); ?>" type="submit" />
        </div>
    </form>
</div>
