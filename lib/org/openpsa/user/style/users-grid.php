<?php
if (empty($data['group'])) {
    midcom_show_style('group-sidebar');
}

$grid = $data['grid'];
$grid->set_option('scroll', 1)
    ->set_option('rowNum', 30)
    ->set_option('height', 600)
    ->set_option('viewrecords', true)
    ->set_option('url', $data['provider_url'])
    ->set_option('sortname', 'index_lastname');

if (isset($data['group'])) {
    $grid->set_option('caption', $data['l10n']->get('members'));
}

$grid->set_column('lastname', $data['l10n']->get('lastname'), 'classes: "title ui-ellipsis"', 'string')
    ->set_column('firstname', $data['l10n']->get('firstname'), 'width: 100, classes: "ui-ellipsis"', 'string')
    ->set_column('username', $data['l10n']->get('username'), 'width: 100, classes: "ui-ellipsis"')
    ->set_column('groups', $data['l10n']->get('groups'), 'sortable: false, classes: "ui-ellipsis", search: false');
?>

<div class="main">
<div class="org_openpsa_user full-width fill-height">
<?php $grid->render(); ?>
<script type="text/javascript">
$('#<?php echo $grid->get_identifier(); ?>').jqGrid('filterToolbar');
</script>
</div>
</div>
