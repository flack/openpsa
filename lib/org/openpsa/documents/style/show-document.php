<?php
$view = $data['document_dm'];
?>
<div class="sidebar">
    <?php midcom_show_style('show-directory-navigation'); ?>
</div>

<div class="main">
    <?php
    $view->display_view(true);

    $tabs = [];

    if ($data['document_versions'] > 0) {
        $nap = new midcom_helper_nav();
        $node = $nap->get_node($nap->get_current_node());

        $tabs[] = [
            'url' => "{$node[MIDCOM_NAV_RELATIVEURL]}document/versions/{$data['document']->guid}/",
            'title' => $data['l10n']->get('older versions'),
        ];
    }
    org_openpsa_widgets_ui::render_tabs($data['document']->guid, $tabs);
    ?>
</div>
