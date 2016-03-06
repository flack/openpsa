<?php
$timeframe = $data['l10n']->get_formatter()->timeframe($data['start'], $data['end'], 'date');
?>

<h1><?php echo $data['topic']->extra; ?>: <?php $data['l10n']->show('archive'); ?></h1>

<h2>&(timeframe);</h2>