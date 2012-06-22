<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<h1><?php echo sprintf(midcom::get('i18n')->get_string('manage feeds of %s', 'net.nemein.rss'), $data['folder']->extra); ?></h1>

<ul class="net_nemein_rss_feeds">
