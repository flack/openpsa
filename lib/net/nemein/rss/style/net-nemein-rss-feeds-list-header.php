<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<h1><?php echo sprintf(midcom::get('i18n')->get_string('manage feeds of %s', 'net.nemein.rss'), $data['folder']->extra); ?></h1>

<ul class="net_nemein_rss_feeds">
