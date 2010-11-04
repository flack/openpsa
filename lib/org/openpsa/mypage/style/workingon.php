<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
$view_today =& $data['view_today'];
$tasks = org_openpsa_projects_task_resource_dba::get_resource_tasks('guid');

if (count($tasks) > 0)
{
    $workingon = new org_openpsa_mypage_workingon();
    $checked = "";
    if(!empty($workingon->invoiceable))
    {
        $checked = "checked = 'checked'";
    }
    ?>
    <div class="org_openpsa_mypage_workingon">
        <h2><?php echo $data['l10n']->get('now working on'); ?></h2>
        <form method="post" action="workingon/set/">
            <input type="hidden" name="url" value="&(prefix);" />
            <?php
                if(is_null($workingon->task))
                {
                    echo "<input type=\"hidden\" id=\"task_before\" name=\"task_before\" value=\"none\" />";
                }
                else
                {
                    echo "<input type=\"hidden\" id=\"task_before\" name=\"task_before\" value=\"".$workingon->task->guid."\" />";
                }
            ?>
            <textarea id="working_description" name="description">&(workingon.description);</textarea>
            <p>
            <div id="working_invoiceable_div" >
            <label for="working_invoiceable">
                <input type="checkbox" name="working_invoiceable" <?php echo $checked;?> id="working_invoiceable"/><?php echo $data['l10n']->get('invoiceable'); ?>
            </label>
            </div>
            </p>
            <select id="working_task" name="task" onchange="send_working_on();">
                <option value="none"><?php echo $data['l10n']->get('not working on a task'); ?></option>
                <?php
                foreach ($tasks as $guid => $label)
                {
                    $selected = '';
                    if (   !is_null($workingon->task)
                        && $workingon->task->guid == $guid)
                    {
                        $selected = ' selected="selected"';
                    }
                    echo "<option value=\"{$guid}\"{$selected}>{$label}</option>\n";
                }
                ?>
            </select>
            <div id="loading" style="text-align:center;">
            </div>
            <div class="calculator" id="org_openpsa_mypage_workingon_time">
            </div>
            <?php
            if ($workingon->task)
            {
                ?>
                <script type="text/javascript">
                jQuery('#org_openpsa_mypage_workingon_time').epiclock({
                      mode: EC_COUNTUP,
                      target: <?php echo $workingon->start * 1000; ?>,
                      format: 'x:i:s'
                });
                jQuery.epiclock(EC_RUN);
                </script>
                <?php
            }
            ?>

        </form>
    </div>
<?php
}
?>