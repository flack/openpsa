<?php
// Available request keys: start, end

$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<p style="clear: left;"><a href="&(prefix);archive/"><?php $data['l10n_midcom']->show('back'); ?></a></p>