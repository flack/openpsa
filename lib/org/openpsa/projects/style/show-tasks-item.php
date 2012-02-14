<?php
$view = $data['task_dm'];
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
$task =& $data['task'];
echo "<dt><a href=\"{$prefix}task/{$task->guid}/\">{$view['title']}</a></dt>\n";
?>
<ul>
  <li><?php echo $data['l10n']->get('deadline') . ": {$view['end']['local_strdate']}"; ?></li>
  <li><?php echo sprintf($data['l10n']->get('%d hours reported'), $data['task_hours']); ?></li>
</ul>