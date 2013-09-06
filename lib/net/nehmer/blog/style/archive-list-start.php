<?php
$start = $data['start']->format($data['l10n_midcom']->get('short date'));
$end = $data['end']->format($data['l10n_midcom']->get('short date'));
?>

<h1><?php echo $data['topic']->extra; ?>: <?php $data['l10n']->show('archive'); ?></h1>

<h2>&(start); - &(end);</h2>