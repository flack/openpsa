<div class="content-with-sidebar">
    <div class="main">
        <div class="org_openpsa_directmarketing full-width crop-height">
        <?php
        $grid = $data['grid'];

        $grid->set_option('loadonce', true);
        $grid->set_column('title', $data['l10n_midcom']->get('title'), 'classes: "title", width: 100', 'string');
        $grid->set_column('description', $data['l10n_midcom']->get('description'), 'classes: "ui-ellipsis", width: 200');
        $grid->set_column('messages', $data['l10n']->get('messages'), 'template: "integer", width: 80, fixed: true');
        $grid->set_column('subscribers', $data['l10n']->get('recipients'), 'template: "integer", width: 80, fixed: true');
        $grid->set_column('smart_campaign', $data['l10n']->get('smart campaign'), 'template: "booleanCheckbox", width: 30, fixed: true');
        $grid->render();
        ?>
        </div>
    </div>
    <aside>
        <div class="area">
            <!-- TODO: List latest messages -->
        </div>
    </aside>
</div>