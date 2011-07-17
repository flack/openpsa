<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<div class="sidebar">
  <div class="area org_openpsa_helper_box">
    <h3><?php echo $data['l10n']->get('groups'); ?></h3>
<?php
    midcom::get()->dynamic_load($prefix . 'groups/');
?>
  </div>
</div>
<div class="main">
<h2><?php echo midcom::get('i18n')->get_string('confirm delete', 'org.openpsa.core'); ?></h2>
<p><?php echo midcom::get('i18n')->get_string('use the buttons below or in toolbar', 'org.openpsa.core'); ?></p>
<form id="org_openpsa_user_deleteform" method="post">
    <input type="hidden" name="org_openpsa_user_deleteok" value="1" />
    <input type="submit" class="button delete" value="<?php echo $data['l10n_midcom']->get('delete'); ?>" />
    <input type="submit" class="button cancel" value="<?php echo $data['l10n_midcom']->get('cancel'); ?>" onclick="window.location='<?php echo $prefix . 'group/' . $data['group']->guid . '/'; ?>';return false;" />
</form>
<?php
    $data['view']->display_view();
?>
</div>