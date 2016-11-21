<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
$workingon = $data['workingon'];

$checked = "";
if (!empty($workingon->invoiceable)) {
    $checked = "checked = 'checked'";
}
$current_task = '';
$default_text = '';
if (!is_null($workingon->task)) {
    $current_task = '[' . $workingon->task->guid . ']';
    $default_text = $workingon->task->get_label();
}
midcom::get()->uimessages->show();
?>

<div id="org_openpsa_mypage_workingon_widget">
<div class="org_openpsa_mypage_workingon">
    <h3><?php echo $data['l10n']->get('now working on'); ?></h3>
    <input type="hidden" name="url" value="&(prefix);" />
    <div id="working_task" >
    <script type="text/javascript">
    $(document).ready(function()
    {
        var widget_conf =
        {
            id: 'working_task',
            appendTo: '#working_task',
            widget_config: <?php echo json_encode($data['widget_config']); ?>,
            placeholder: '<?php echo midcom::get()->i18n->get_string('task', 'org.openpsa.projects') ?>',
            default_value: '<?php echo $current_task; ?>',
            default_text: '<?php echo $default_text; ?>',
        },
        autocomplete_conf =
        {
            select: org_openpsa_workingon.select,
            open: midcom_helper_datamanager2_autocomplete.open,
            position: {collision: 'fit none'}
        }

        midcom_helper_datamanager2_autocomplete.create_widget(widget_conf, autocomplete_conf);
        $('#working_task_search_input').show();
        org_openpsa_workingon.setup_widget();
    });
    </script>
    </div>

    <textarea id="working_description" name="description" rows="3" cols="40">&(workingon.description);</textarea>
    <div id="working_invoiceable_div" >
    <label for="working_invoiceable">
      <input type="checkbox" name="working_invoiceable" <?php echo $checked;?> id="working_invoiceable"/><?php echo $data['l10n']->get('invoiceable'); ?>
    </label>
    </div>

    <div class="controls">
    <div class="calculator" id="org_openpsa_mypage_workingon_time">
    </div>
    <?php
    if ($workingon->task) {
        ?>
         <script type="text/javascript">
             jQuery('#org_openpsa_mypage_workingon_time').epiclock({
                   mode: $.epiclock.modes.countup,
                   target: <?php echo $workingon->start; ?>000,
                   format: 'x:i:s'
             });
         </script>
    <?php
    } ?>
      <input type="button" id="org_openpsa_mypage_workingon_stop" value="<?php echo $data['l10n']->get('stop'); ?>"/>
      <input type="button" id="org_openpsa_mypage_workingon_start" value="<?php echo $data['l10n']->get('start'); ?>"/>
    </div>
</div>
<div id="org_openpsa_mypage_workingon_loading">
<img src="<?php echo MIDCOM_STATIC_URL; ?>/stock-icons/32x32/ajax-loading.gif" alt="loading" />
</div>

<?php
if ($data['expenses_url']) {
    midcom_show_style('workingon_expenses');
}
?>

</div>