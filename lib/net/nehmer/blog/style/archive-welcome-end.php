<?php
// Available request keys: total_count, first_post, year_data
//$data =& $_MIDCOM->get_custom_context_data('request_data');
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<p><a href="&(prefix);"><?php $data['l10n_midcom']->show('back'); ?></a></p>