<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);

$grid = $data['grid'];
$grid->set_option('scroll', 1);
$grid->set_option('rowNum', 30);
$grid->set_option('height', 600);
$grid->set_option('viewrecords', true);
$grid->set_option('url', $prefix . 'json/');

$grid->set_column('lastname', $data['l10n']->get('lastname'), 'classes: "title"', 'string')
    ->set_column('firstname', $data['l10n']->get('firstname'), 'width: 100', 'string')
    ->set_column('username', $data['l10n']->get('username'), 'width: 100')
    ->set_column('groups', $data['l10n']->get('groups'), 'sortable: false');
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
