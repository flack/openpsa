<?php
$diff   = $data['diff'];
$latest = $data['latest_revision'];
$comment= $data['comment'];
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<h1>&(data['view_title']);</h1>
<p>&(data['comment']['message']);</p>
<div class="rcs_navigation">
<?php
if ($data['earlier_revision']) {
    echo "<a href=\"{$prefix}__ais/rcs/diff/{$data['guid']}/{$data['earlier_revision']}/{$data['previous_revision']}/\">&lt;&lt; ". sprintf($data['l10n']->get('differences between versions %s and %s'), $data['earlier_revision'], $data['previous_revision']) ."</a>\n";
}

if (   $data['earlier_revision']
    && $data['next_revision']) {
    echo " | ";
}

if ($data['next_revision']) {
    echo "<a href=\"{$prefix}__ais/rcs/diff/{$data['guid']}/{$data['latest_revision']}/{$data['next_revision']}/\">". sprintf($data['l10n']->get('differences between versions %s and %s'), $data['latest_revision'], $data['next_revision']) ." &gt;&gt;</a>\n";
}
?>
</div>
<dl class="midcom_admin_rcs_diff">
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