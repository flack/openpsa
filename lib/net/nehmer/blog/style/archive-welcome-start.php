<?php
// Available request keys: total_count, first_post, year_data

//$data =& $_MIDCOM->get_custom_context_data('request_data');

$summary = sprintf($data['l10n']->get('there is a total of %d posts.'), $data['total_count']);

if ($data['first_post'])
{
    $summary .= ' ' . sprintf($data['l10n']->get('first post was made on %s.'),
        $data['first_post']->format($data['l10n_midcom']->get('short date')));
}
?>

<h1><?php echo $data['topic']->extra; ?>: <?php $data['l10n']->show('archive'); ?></h1>

<p>&(summary);</p>