<?php
$diff   = $data['diff'];
$latest = $data['latest_revision'];
$comment= $data['comment'];
?>
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
$changes = false;
foreach ($diff as $attribute => $values) {
    if (!array_key_exists('diff', $values)) {
        continue;
    }

    if (!midcom_services_rcs::is_field_showable($attribute)) {
        continue;
    }

    if (is_array($values['diff'])) {
        continue;
    }

    $changes = true;

    echo "<dt>". $data['handler']->translate($attribute) ."</dt>\n";
    echo "    <dd>\n";
    echo $values['diff'];
    echo "    </dd>\n";
}

if (!$changes) {
    echo "<dt>". $data['l10n']->get('no changes in content') ."</dt>\n";
}
?>
</dl>