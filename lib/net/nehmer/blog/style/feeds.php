<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>

<h1><?php echo $data['topic']->extra; ?>: <?php $data['l10n']->show('available feeds'); ?></h1>

<p><?php $data['l10n']->show('available feeds introduction'); ?></p>

<ul>
    <li><a href="&(prefix);rss.xml"><?php $data['l10n']->show('rss 2.0 feed'); ?></a></li>
    <li><a href="&(prefix);rss1.xml"><?php $data['l10n']->show('rss 1.0 feed'); ?></a></li>
    <li><a href="&(prefix);rss091.xml"><?php $data['l10n']->show('rss 0.91 feed'); ?></a></li>
    <li><a href="&(prefix);atom.xml"><?php $data['l10n']->show('atom feed'); ?></a></li>
</ul>

<p><a href="&(prefix);"><?php $data['l10n_midcom']->show('back'); ?></a></p>