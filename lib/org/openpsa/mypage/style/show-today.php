<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>

<div class="sidebar">
    <?php
    if ($data['projects_relative_url']) {
        midcom::get()->dynamic_load('workingon/');
    }
    if ($data['calendar_url']) {
        ?>
            <div class="agenda">
                <?php
                midcom::get()->dynamic_load($data['calendar_url'] . 'agenda/day/' . $data['requested_time']->format('Y-m-d')); ?>
            </div>
        <?php

    }
    ?>
</div>

<div class="org_openpsa_mypage main">
    <?php
    if ($data['projects_relative_url']) {
        ?>
        <div class="tasks normal">
            <?php
            midcom::get()->dynamic_load($data['projects_relative_url'] . 'task/list/'); ?>
        </div>
        <?php
    }

    $tabs = array(array(
        'url' => $data['journal_url'],
        'title' => midcom::get()->i18n->get_string('journal entries', 'org.openpsa.relatedto'),
    ));

    if ($data['wiki_url']) {
        $nap = new midcom_helper_nav;
        $node = $nap->resolve_guid($data['wiki_guid']);
        $tabs[] = array(
            'url' => $data['wiki_url'] . "latest/",
            'title' => sprintf(midcom::get()->i18n->get_string('latest updates in %s', 'net.nemein.wiki'), $node[MIDCOM_NAV_NAME]),
        );
    }
    org_openpsa_widgets_ui::render_tabs(null, $tabs);
    ?>
</div>
