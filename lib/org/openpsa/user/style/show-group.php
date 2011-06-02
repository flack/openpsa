<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<div class="sidebar">
    <h2><?php echo $data['l10n']->get('groups'); ?></h2>
<?php
    midcom::get()->dynamic_load($prefix . 'groups/');
?>
</div>

<div class="main">
<?php
$data['view']->display_view();
?>
</div>