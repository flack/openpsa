<div class="org_openpsa_documents full-width">

<?php
$data['grid']->set_column('title', $data['l10n']->get('title'), 'width: 80, classes: "ui-ellipsis"', 'string')
    ->set_column('filesize', midcom::get()->i18n->get_string('size', 'midcom.admin.folder'), 'width: 60, fixed: true, align: "right"', 'number')
    ->set_column('mimetype', midcom::get()->i18n->get_string('mimetype', 'midgard.admin.asgard'), 'width: 60, classes: "ui-ellipsis"')
    ->set_column('created', $data['l10n_midcom']->get('created on'), 'width: 135, formatter: "date", fixed: true, align: "right"')
    ->set_column('author', $data['l10n']->get('author'), 'width: 70, classes: "ui-ellipsis"', 'string');

$data['grid']->render();
?>

</div>