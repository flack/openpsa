<?php
$task = $data['task'];
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX)
?>
<div class="org_openpsa_projects_projectbroker">
    <h1><?php echo $data['task']->title; ?></h1>

    <form method="post">
        <ul class="prospects" id="prospects_list">
        </ul>
        <div class="form_toolbar">
            <input type="submit" accesskey="s" class="save" name="save" value="<?php echo $data['l10n_midcom']->get('save'); ?>" />
            <input type="submit" accesskey="c" class="cancel" name="cancel" value="<?php echo $data['l10n_midcom']->get('cancel'); ?>" />
        </div>
    </form>

    <script type="text/javascript">
        prospects_handler = jQuery('#prospects_list').project_prospects_renderer({base_url: '<?php echo $prefix; ?>', task_guid: '<?php echo $task->guid; ?>'});
    </script>
</div>