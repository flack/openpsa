<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<aside>
  <div class="area org_openpsa_helper_box">
    <h3><?php echo $data['l10n']->get('groups'); ?></h3>
    <?php
        midcom::get()->dynamic_load($prefix . 'groups/');
    ?>
  </div>
</aside>