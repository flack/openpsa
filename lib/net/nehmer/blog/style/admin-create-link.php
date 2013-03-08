<?php
// Available request keys: controller, schema, schemadb

$title = sprintf($data['l10n_midcom']->get('create %s'), $data['l10n']->get('article link'));
?>
<h2>&(title:h);</h2>
<?php $data['controller']->display_form (); ?>