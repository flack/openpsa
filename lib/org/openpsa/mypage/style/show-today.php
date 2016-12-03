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
    <div class="journal normal">
        <?php
        midcom::get()->dynamic_load($data['journal_url']); ?>
    </div>

    <?php
    if ($data['projects_relative_url']) {
        ?>
        <div class="tasks normal">
            <?php
            midcom::get()->dynamic_load($data['projects_relative_url'] . 'task/list/'); ?>
        </div>
        <?php
    }

    if ($data['wiki_url']) {
        ?>
        <div class="wiki">
            <?php
            midcom::get()->dynamic_load($data['wiki_url'] . 'latest/'); ?>
        </div>
        <?php
    }
    ?>
</div>
