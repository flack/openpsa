<?php
// Available request keys: start, end

//$data =& $_MIDCOM->get_custom_context_data('request_data');
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<p style="clear: left;"><a href="&(prefix);archive/"><?php $data['l10n_midcom']->show('back'); ?></a></p>