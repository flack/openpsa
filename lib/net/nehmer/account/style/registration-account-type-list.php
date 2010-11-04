<?php
// The available request keys can be found in the components' API documentation
// of net_nehmer_account_handler_register
//
// Bind the view data, remember the reference assignment:
//$data =& $_MIDCOM->get_custom_context_data('request_data');
?>

<h2><?php $data['l10n']->show('select an account type'); ?></h2>

<ul>
<?php foreach ($data['types'] as $url => $name) { ?>
    <li><a href="&(url);">&(name);</a></li>
<?php } ?>
</ul>