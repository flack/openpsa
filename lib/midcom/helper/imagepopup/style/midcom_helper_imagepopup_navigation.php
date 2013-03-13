<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . "__ais/imagepopup/";
?>
<div id="top_navigation">
    <ul>
    <?php
    if ($data['list_type'] === 'folder')
    {
        if ($data['object'])
        {
            echo "<li><a href=\"{$prefix}{$data['schema_name']}/{$data['object']->guid}/\">" . $data['l10n_midcom']->get('page') . "</a></li>";
            echo "<li class=\"selected\"><a href=\"{$prefix}folder/{$data['schema_name']}/{$data['object']->guid}\">" . $data['l10n_midcom']->get('folder') . "</a></li>";
            echo "<li><a href=\"{$prefix}unified/{$data['schema_name']}/{$data['object']->guid}\">" . $data['l10n']->get('unified search') . "</a></li>";
        }
        else
        {
            echo "<li class=\"selected\"><a href=\"{$prefix}folder/{$data['schema_name']}/\">" . $data['l10n_midcom']->get('folder') . "</a></li>";
            echo "<li><a href=\"{$prefix}unified/{$data['schema_name']}/\">" . $data['l10n']->get('unified search') . "</a></li>";
        }
    }
    else if ($data['list_type'] === 'page')
    {
        echo "<li class=\"selected\"><a href=\"{$prefix}{$data['schema_name']}/{$data['object']->guid}/\">" . $data['l10n_midcom']->get('page') . "</a></li>";
        echo "<li><a href=\"{$prefix}folder/{$data['schema_name']}/{$data['object']->guid}\">" . $data['l10n_midcom']->get('folder') . "</a></li>";
        echo "<li><a href=\"{$prefix}unified/{$data['schema_name']}/{$data['object']->guid}\">" . $data['l10n']->get('unified search') . "</a></li>";
    }
    else if ($data['list_type'] === 'unified')
    {
        if ($data['object'])
        {
            echo "<li><a href=\"{$prefix}{$data['schema_name']}/{$data['object']->guid}/\">" . $data['l10n_midcom']->get('page') . "</a></li>";
            echo "<li><a href=\"{$prefix}folder/{$data['schema_name']}/{$data['object']->guid}\">" . $data['l10n_midcom']->get('folder') . "</a></li>";
            echo "<li class=\"selected\"><a href=\"{$prefix}unified/{$data['schema_name']}/{$data['object']->guid}\">" . $data['l10n']->get('unified search') . "</a></li>";
        }
        else
        {
            echo "<li><a href=\"{$prefix}folder/{$data['schema_name']}/\">" . $data['l10n_midcom']->get('folder') . "</a></li>";
            echo "<li class=\"selected\"><a href=\"{$prefix}unified/{$data['schema_name']}/\">" . $data['l10n']->get('unified search') . "</a></li>";
        }
    }
   ?>
   </ul>
</div>