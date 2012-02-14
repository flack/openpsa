<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);

$grid = $data['grid'];
$grid->set_option('scroll', 1)
    ->set_option('rowNum', 30)
    ->set_option('height', 600)
    ->set_option('viewrecords', true)
    ->set_option('url', $prefix . 'json/')
    ->set_option('sortname', 'index_lastname');

$grid->set_column('lastname', $data['l10n']->get('lastname'), 'classes: "title ui-ellipsis"', 'string')
    ->set_column('firstname', $data['l10n']->get('firstname'), 'width: 100, classes: "ui-ellipsis"', 'string')
    ->set_column('username', $data['l10n']->get('username'), 'width: 100, classes: "ui-ellipsis"')
    ->set_column('groups', $data['l10n']->get('groups'), 'sortable: false, classes: "ui-ellipsis"');
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
<div class="org_openpsa_user full-width fill-height">
<?php $grid->render(); ?>
</div>
</div>
