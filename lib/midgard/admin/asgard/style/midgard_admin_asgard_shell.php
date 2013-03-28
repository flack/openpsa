<div class="object_edit">
<?php
$data['controller']->display_form();
?>
</div>
<?php
if (!empty($data['code']))
{ ?>
    <h3><?php echo $data['l10n']->get('script output'); ?></h3>
    <pre id="shell-output">&(data['code']:p);<pre>
<?php }
?>
