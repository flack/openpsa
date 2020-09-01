<h1>&(data['view_title']);</h1>
<div class="rcs_navigation">
<?php
echo $data['rcs_toolbar']->render();
echo $data['rcs_toolbar_2']->render();
?>
</div>
<p>&(data['comment']['message']);</p>
<dl class="midcom_services_rcs_diff">
<?php
foreach ($data['diff'] as $attribute => $values) {
    echo "<dt>". $data['handler']->translate($attribute) ."</dt>\n";
    echo "    <dd>\n";
    echo $values['diff'];
    echo "    </dd>\n";
}

if (!$data['diff']) {
    echo "<dt>". $data['l10n']->get('no changes in content') ."</dt>\n";
}
?>
</dl>