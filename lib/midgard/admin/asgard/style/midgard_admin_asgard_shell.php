<?php
$identifier = $data['controller']->get_datamanager()->get_form()->getName() . '_code';
?>
<script type="text/javascript">
var midgard_admin_asgard_shell_identifier = '&(identifier);';
</script>
<div class="object_edit">
<?php
$data['controller']->display_form();
?>
</div>

<div id="output-wrapper">
<h3><?php echo $data['l10n']->get('script output'); ?></h3>
<iframe name="shell-runner" id="shell-runner" frameborder="0" src="./?ajax"></iframe>
</div>