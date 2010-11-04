<?php
// The available request keys can be found in the components' API documentation
// of net_nehmer_account_handler_socialweb
//
// Bind the view data, remember the reference assignment:
//$data =& $_MIDCOM->get_custom_context_data('request_data');
$account =& $data['account'];
$schema =& $data['datamanager']->datamanager->schema;
?>

<h2>&(account.name); (&(schema.description);)</h2>

<p>
    <?php echo $data['l10n']->get('these settings control social web data import'); ?>
</p>

<?php $data['datamanager']->display_form(); ?>

<p><a href="&(data['profile_url']);"><?php $data['l10n_midcom']->show('back'); ?></a></p>