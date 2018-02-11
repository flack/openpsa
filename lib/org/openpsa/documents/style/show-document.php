<div class="content-with-sidebar">
    <div class="main">
        <?php
        $data['document_dm']->display_view(true);

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
    <aside>
        <?php midcom_show_style('show-directory-navigation'); ?>
    </aside>
</div>
