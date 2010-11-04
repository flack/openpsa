<h1><?php echo $data['topic']->extra;?></h1>

<?php midcom_show_style("{$data['type']}_form"); ?>

<h2><?php echo $data['l10n']->get('search hints');?>:</h2>
<p><?php
    $string = 'search hints ' . $data['config']->get('search_help_message');
    echo $data['l10n']->get($string);
?></p>
