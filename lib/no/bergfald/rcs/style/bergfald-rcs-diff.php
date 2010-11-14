<?php
$diff   = $data['diff'];
$latest = $data['latest_revision'];
$comment= $data['comment'];
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<h1>&(data['view_title']);</h1>
<div class="rcs_navigation">
<?php
if ($data['earlier_revision'])
{
    echo "<a href=\"{$prefix}__ais/rcs/diff/{$data['guid']}/{$data['earlier_revision']}/{$data['previous_revision']}/\">&lt;&lt; ". sprintf($_MIDCOM->i18n->get_string('differences between versions %s and %s', 'no.bergfald.rcs'), $data['earlier_revision'], $data['previous_revision']) ."</a>\n";
}

if (   $data['earlier_revision']
    && $data['next_revision'])
{
    echo " | ";
}

if ($data['next_revision'])
{
    echo "<a href=\"{$prefix}__ais/rcs/diff/{$data['guid']}/{$data['latest_revision']}/{$data['next_revision']}/\">". sprintf($_MIDCOM->i18n->get_string('differences between versions %s and %s', 'no.bergfald.rcs'), $data['latest_revision'], $data['next_revision']) ." &gt;&gt;</a>\n";
}
?>
</div>
<dl class="no_bergfald_rcs_diff">
<?php
$changes = false;
foreach ($diff as $attribute => $values)
{
    if (!array_key_exists('diff', $values))
    {
        continue;
    }

    if (!no_bergfald_rcs_handler::is_field_showable($attribute))
    {
        continue;
    }

    if (is_array($values['diff']))
    {
        continue;
    }

    $changes = true;

    // Three fold fallback in localization
    echo "<dt>". $data['l10n_midcom']->get($data['l10n']->get($_MIDCOM->i18n->get_string($attribute, 'no.bergfald.rcs'))) ."</dt>\n";
    echo "    <dd>\n";
    echo nl2br($values['diff']);
    echo "    </dd>\n";
}

if (!$changes)
{
    echo "<dt>". $_MIDCOM->i18n->get_string('no changes in content', 'no.bergfald.rcs') ."</dt>\n";
}
?>
</dl>