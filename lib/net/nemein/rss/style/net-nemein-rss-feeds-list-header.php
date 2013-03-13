<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<h1><?php echo sprintf($data['l10n']->get('manage feeds of %s'), $data['folder']->extra); ?></h1>

<ul class="net_nemein_rss_feeds">
