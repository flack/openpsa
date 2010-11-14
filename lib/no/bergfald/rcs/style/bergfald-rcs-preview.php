<?php
$preview = $data['preview'];
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<h1>&(data['view_title']:h);</h1>
<div class="rcs_navigation">
<?php
if ($data['previous_revision'])
{
    echo "&lt;&lt;\n";
    echo "<a href=\"{$prefix}__ais/rcs/preview/{$data['guid']}/{$data['previous_revision']}\">". sprintf($_MIDCOM->i18n->get_string('version %s', 'no.bergfald.rcs'), $data['previous_revision']) ."</a>\n";
    echo "(<em><a href=\"{$prefix}__ais/rcs/diff/{$data['guid']}/{$data['previous_revision']}/{$data['latest_revision']}/\">{$_MIDCOM->i18n->get_string('show differences', 'no.bergfald.rcs')}</a></em>)\n";
}

if (   $data['previous_revision']
    && $data['next_revision'])
{
    echo " | ";
}

if ($data['next_revision'])
{
    echo "<a href=\"{$prefix}__ais/rcs/preview/{$data['guid']}/{$data['latest_revision']}\">". sprintf($_MIDCOM->i18n->get_string('version %s', 'no.bergfald.rcs'), $data['latest_revision']) ."</a>\n";
    echo "(<em><a href=\"{$prefix}__ais/rcs/diff/{$data['guid']}/{$data['latest_revision']}/{$data['next_revision']}/\">{$_MIDCOM->i18n->get_string('show differences', 'no.bergfald.rcs')}</a></em>)\n";
    echo "&gt;&gt;\n";
}
?>
</div>
<dl>
<?php
foreach ($preview as $attribute => $value)
{
    if ($value == '')
    {
        continue;
    }

    if ($value == '0000-00-00')
    {
        continue;
    }

    if (!no_bergfald_rcs_handler::is_field_showable($attribute))
    {
        continue;
    }

    if (is_array($value))
    {
        continue;
    }

    // Three fold fallback in localization
    echo "<dt>". $data['l10n_midcom']->get($data['l10n']->get($_MIDCOM->i18n->get_string($attribute, 'no.bergfald.rcs'))) ."</dt>\n";
    echo "    <dd>" . nl2br($value) . "</dd>\n";
}
?>
</dl>
