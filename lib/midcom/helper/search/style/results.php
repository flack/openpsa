<h1><?php echo $data['topic']->extra;?></h1>

<div class="midcom_helper_search_form">
  <?php midcom_show_style("{$data['type']}_form"); ?>
</div>

<h2><?php echo $data['l10n']->get('search results');?>:</h2>
<?php
midcom_show_style('result_summary');
midcom_show_style('result_nav');

midcom_show_style('result_start');
foreach ($data['result'] as $document) {
    $data['document'] = $document;
    midcom_show_style('result_item');
}
midcom_show_style('result_end');

midcom_show_style('result_nav');
?>

<h2><?php echo $data['l10n']->get('search hints');?>:</h2>
<p><?php
    $string = 'search hints ' . $data['config']->get('search_help_message');
    echo $data['l10n']->get($string);
?></p>
