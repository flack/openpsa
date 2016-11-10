<?php
$preview = $data['preview'];
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
$l10n = $data['l10n'];
?>
<h1>&(data['view_title']:h);</h1>
<div class="rcs_navigation">
<?php
if ($data['previous_revision']) {
    echo "&lt;&lt;\n";
    echo "<a href=\"{$prefix}__ais/rcs/preview/{$data['guid']}/{$data['previous_revision']}\">". sprintf($l10n->get('version %s'), $data['previous_revision']) ."</a>\n";
    echo "(<em><a href=\"{$prefix}__ais/rcs/diff/{$data['guid']}/{$data['previous_revision']}/{$data['latest_revision']}/\">{$l10n->get('show differences')}</a></em>)\n";
}

if (   $data['previous_revision']
    && $data['next_revision']) {
    echo " | ";
}

if ($data['next_revision']) {
    echo "<a href=\"{$prefix}__ais/rcs/preview/{$data['guid']}/{$data['latest_revision']}\">". sprintf($l10n->get('version %s'), $data['latest_revision']) ."</a>\n";
    echo "(<em><a href=\"{$prefix}__ais/rcs/diff/{$data['guid']}/{$data['latest_revision']}/{$data['next_revision']}/\">{$l10n->get('show differences')}</a></em>)\n";
    echo "&gt;&gt;\n";
}
?>
</div>
<dl>
<?php
foreach ($preview as $attribute => $value) {
    if ($value == '') {
        continue;
    }

    if ($value == '0000-00-00') {
        continue;
    }

    if (!midcom_services_rcs::is_field_showable($attribute)) {
        continue;
    }

    if (is_array($value)) {
        continue;
    }

    // Three fold fallback in localization
    echo "<dt>". $data['handler']->translate($attribute) ."</dt>\n";
    echo "    <dd>" . nl2br($value) . "</dd>\n";
}
?>
</dl>
