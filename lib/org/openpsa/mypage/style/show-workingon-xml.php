<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
$workingon =& $data['workingon'];
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
if($workingon->start == 0)
{
?>
    <workingon>
        <task>0</task>
        <start>0</start>
    </workingon>

<?php
}
else
{
?>
    <workingon>
        <task><?php echo $workingon->task->guid; ?></task>
        <start><?php echo $workingon->start; ?></start>
    </workingon>
<?php
}
?>