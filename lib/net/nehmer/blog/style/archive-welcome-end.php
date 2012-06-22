<?php
// Available request keys: total_count, first_post, year_data

$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<p><a href="&(prefix);"><?php $data['l10n_midcom']->show('back'); ?></a></p>