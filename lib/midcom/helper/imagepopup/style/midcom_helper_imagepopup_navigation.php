<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . "__ais/imagepopup/";
?>
<div id="top_navigation">
    <ul>
    <?php
    if ($data['list_type'] === 'folder')
    {
        if ($data['object'])
        {
            echo "<li><a href=\"{$prefix}{$data['schema_name']}/{$data['object']->guid}/\">" . $_MIDCOM->i18n->get_string('page', 'midcom') . "</a></li>";
            echo "<li class=\"selected\"><a href=\"{$prefix}folder/{$data['schema_name']}/{$data['object']->guid}\">" . $_MIDCOM->i18n->get_string('folder', 'midcom') . "</a></li>";
            echo "<li><a href=\"{$prefix}unified/{$data['schema_name']}/{$data['object']->guid}\">" . $_MIDCOM->i18n->get_string('unified search', 'midcom.helper.imagepopup') . "</a></li>";
        }
        else
        {
            echo "<li class=\"selected\"><a href=\"{$prefix}folder/{$data['schema_name']}/\">" . $_MIDCOM->i18n->get_string('folder', 'midcom') . "</a></li>";
            echo "<li><a href=\"{$prefix}unified/{$data['schema_name']}/\">" . $_MIDCOM->i18n->get_string('unified search', 'midcom.helper.imagepopup') . "</a></li>";
        }
    }
    else if ($data['list_type'] === 'page')
    {
        echo "<li class=\"selected\"><a href=\"{$prefix}{$data['schema_name']}/{$data['object']->guid}/\">" . $_MIDCOM->i18n->get_string('page', 'midcom') . "</a></li>";
        echo "<li><a href=\"{$prefix}folder/{$data['schema_name']}/{$data['object']->guid}\">" . $_MIDCOM->i18n->get_string('folder', 'midcom') . "</a></li>";
        echo "<li><a href=\"{$prefix}unified/{$data['schema_name']}/{$data['object']->guid}\">" . $_MIDCOM->i18n->get_string('unified search', 'midcom.helper.imagepopup') . "</a></li>";
    }
    else if ($data['list_type'] === 'unified')
    {
        if ($data['object'])
        {
            echo "<li><a href=\"{$prefix}{$data['schema_name']}/{$data['object']->guid}/\">" . $_MIDCOM->i18n->get_string('page', 'midcom') . "</a></li>";
            echo "<li><a href=\"{$prefix}folder/{$data['schema_name']}/{$data['object']->guid}\">" . $_MIDCOM->i18n->get_string('folder', 'midcom') . "</a></li>";
            echo "<li class=\"selected\"><a href=\"{$prefix}unified/{$data['schema_name']}/{$data['object']->guid}\">" . $_MIDCOM->i18n->get_string('unified search', 'midcom.helper.imagepopup') . "</a></li>";
        }
        else
        {
            echo "<li><a href=\"{$prefix}folder/{$data['schema_name']}/\">" . $_MIDCOM->i18n->get_string('folder', 'midcom') . "</a></li>";
            echo "<li class=\"selected\"><a href=\"{$prefix}unified/{$data['schema_name']}/\">" . $_MIDCOM->i18n->get_string('unified search', 'midcom.helper.imagepopup') . "</a></li>";
        }
    }
   ?>
   </ul>
</div>