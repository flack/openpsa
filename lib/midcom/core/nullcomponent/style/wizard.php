<p>&(data['message']);</p>
<p>Choose one of the options below:</p>
<?php $data['controller']->display_form(); ?>
<?php
$runner = new midcom_config_test;
$runner->check();
if ($runner->get_status() === midcom_config_test::ERROR) { ?>
    <script>
	$(document).ready(function() {
		$('[href$="midcom-config-test"]').click();
	});
    </script>
<?php } ?>
