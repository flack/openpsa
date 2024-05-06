<?php
use midcom\grid\grid;
$l10n = midcom::get()->i18n->get_l10n('org.openpsa.relatedto');

$grid = new grid('journalgrid', 'xml');
$grid->set_column('name', $l10n->get('entry title'), separate_index: 'string')
    ->set_column('description', $l10n->get('entry text'))
    ->set_column('remind_date', $l10n->get('followup'), 'fixed: true, align: "right", formatter: "date", width:140')
    ->set_column('object', $l10n->get('linked object'), 'width: 120', 'string')

    ->set_option('scroll', 1)
    ->set_option('url', $data['router']->generate('journal_entry_xml'))
    ->set_option('mtype', 'POST')
    ->set_option('height', 150)
    ->set_option('postData', json_encode($data['journal_constraints']), false)
    ->set_option('loadonce', true)
    ->set_option('sortname', 'remind_date');
?>
<div class="full-width">
    <?php $grid->render(); ?>
</div>
