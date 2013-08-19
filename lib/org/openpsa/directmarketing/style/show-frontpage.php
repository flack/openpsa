<?php
$grid = $data['grid'];

$grid->set_option('loadonce', true);
$grid->set_column('title', $data['l10n_midcom']->get('title'), 'classes: "title", width: 100', 'string');
$grid->set_column('description', $data['l10n_midcom']->get('description'), 'classes: "ui-ellipsis", width: 200');
$grid->set_column('subscribers', $data['l10n']->get('recipients'), 'classes: "numeric", width: 80, fixed: true');
$grid->set_column('smart_campaign', $data['l10n']->get('smart campaign'), 'formatter: "checkbox", width: 30, fixed: true, align: "center"');
?>

<div class="org_openpsa_directmarketing full-width crop-height">
<?php $grid->render(); ?>
</div>
<div class="sidebar">
    <div class="area">
        <!-- TODO: List latest messages -->
    </div>
</div>