<?php
$url = $data['router']->generate('today');
$workingon = $data['workingon'];

$checked = "";
if (!empty($workingon->invoiceable)) {
    $checked = "checked = 'checked'";
}
$current_task = '';
$default_text = '';
if ($workingon->task !== null) {
    $current_task = '[' . $workingon->task->guid . ']';
    $default_text = $workingon->task->get_label();
}
midcom::get()->uimessages->show();
?>

<div id="org_openpsa_mypage_workingon_widget">
<div class="org_openpsa_mypage_workingon">
    <h3><?php echo $data['l10n']->get('now working on'); ?></h3>
    <input type="hidden" name="url" value="&(url);" />
    <div id="working_task" >
      <input type="text" id="working_task_search_input">
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
	         countup(<?php echo $workingon->start; ?>000);
         </script>
    <?php
    } ?>
      <input type="button" id="org_openpsa_mypage_workingon_stop" value="<?php echo $data['l10n']->get('stop'); ?>"/>
      <input type="button" id="org_openpsa_mypage_workingon_start" value="<?php echo $data['l10n']->get('start'); ?>"/>
    </div>
</div>
<div id="org_openpsa_mypage_workingon_loading">
<i class="fa fa-pulse fa-spinner"></i>
</div>

<script type="text/javascript">
    midcom_helper_datamanager2_autocomplete.create_widget({
        id: 'working_task',
        widget_config: <?php echo json_encode($data['widget_config']); ?>,
        placeholder: '<?php echo midcom::get()->i18n->get_string('task', 'org.openpsa.projects') ?>',
        default_value: '<?php echo $current_task; ?>',
        default_text: '<?php echo $default_text; ?>',
        input: $('#working_task_search_input')
    },
    {
        select: org_openpsa_workingon.select,
        open: midcom_helper_datamanager2_autocomplete.open,
        position: {collision: 'fit none'}
    });
    org_openpsa_workingon.setup_widget();
</script>

<?php
if ($data['expenses_url']) {
    midcom_show_style('workingon_expenses');
}
?>

</div>