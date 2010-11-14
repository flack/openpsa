<?php
$view = $data['datamanager']->get_content_html();
$return_url = $GLOBALS['midcom_config']['midcom_site_url'];
?>

<h2><?php $data['l10n']->show('membership cancelled'); ?></h2>

<p><a href="&(return_url);"><?php $data['l10n_midcom']->show('back'); ?></a></p>

<p>&(view['firstname']); &(view['lastname']);</p>