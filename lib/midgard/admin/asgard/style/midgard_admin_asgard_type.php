<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
if ($data['component'] == 'midgard')
{
    $component_label = 'Midgard CMS';
}
else
{
    $component_label = $_MIDCOM->i18n->get_string($data['component'], $data['component']);
}
echo "<h2>";
echo sprintf($_MIDCOM->i18n->get_string('%s in %s', 'midcom'),
        midgard_admin_asgard_plugin::get_type_label($data['type']),
        $component_label);
echo "</h2>";

echo "<p>";
if ($data['parent_type'])
{
    echo sprintf($_MIDCOM->i18n->get_string('%s is under type %s', 'midgard.admin.asgard'), midgard_admin_asgard_plugin::get_type_label($data['type']), "<a href=\"{$prefix}__mfa/asgard/{$data['parent_type']}/\">" . midgard_admin_asgard_plugin::get_type_label($data['parent_type']) . "</a>") . ' ';
}

if ($data['component'] == 'midgard')
{
    echo $_MIDCOM->i18n->get_string('this is a midgard core type', 'midgard.admin.asgard') . "</p>\n";
}
else
{
    echo sprintf($_MIDCOM->i18n->get_string('this type belongs to %s component', 'midgard.admin.asgard'), "<a href=\"{$prefix}__mfa/asgard/components/{$data['component']}/\">{$data['component']}</a>") . "</p>\n";
}
?>

&(data['help']:h);

<form method="get">
<div id="search_bar">
    <label>
        <?php echo $_MIDCOM->i18n->get_string('search', 'midgard.admin.asgard'); ?>
        <input id="search_field" type="text" name="search" class="search"<?php if (isset($_GET['search'])) { echo " value=\"{$_GET['search']}\""; } ?> />
    </label>
    <input class="search" type="submit" value="<?php echo $_MIDCOM->i18n->get_string('go', 'midgard.admin.asgard'); ?>" />
</div>
</form>
<script type="text/javascript">
document.getElementById('search_field').focus();
</script>

<?php
if (isset($data['search_results']))
{
    function resolve_label(&$object)
    {
        if (!isset($object->guid)
            || $object->guid == "")
        {
            return;
        }
        $reflector = midcom_helper_reflector_tree::get($object);
        $label_property = $reflector->get_label_property();
        if (method_exists($object, 'get_label'))
        {
            $label = $object->get_label();
        }
        else
        {
            $label = $object->$label_property;
        }
        $parent = $object->get_parent();
        $label = resolve_label($parent) . "/" . $label;
        return $label;
    }
    if (!$data['search_results'])
    {
        echo "<p>" . $_MIDCOM->i18n->get_string('no results', 'midgard.admin.asgard') . "</p>\n";
    }

    else
    {
        echo "<table class=\"table_widget\" id=\"search_results\">\n";
        echo "    <thead>\n";
        echo "        <tr>\n";
        echo "            <th>" . $_MIDCOM->i18n->get_string('title', 'midcom') . "</th>\n";
        echo "            <th>" . $_MIDCOM->i18n->get_string('created on', 'midgard.admin.asgard') . "</th>\n";
        echo "            <th>" . $_MIDCOM->i18n->get_string('created by', 'midgard.admin.asgard') . "</th>\n";
        echo "        </tr>\n";
        echo "    </thead>\n";
        echo "    <tbody>\n";
        $persons = array();
        foreach ($data['search_results'] as $result)
        {
            $reflector = midcom_helper_reflector_tree::get($result);
            $icon = $reflector->get_object_icon($result);
            $label = resolve_label($result);

            if (!isset($persons[$result->metadata->creator]))
            {
                $persons[$result->metadata->creator] = $_MIDCOM->auth->get_user($result->metadata->creator);
            }

            echo "        <tr>\n";
            echo "            <td><a href=\"{$prefix}__mfa/asgard/object/{$data['default_mode']}/{$result->guid}/\">{$icon} {$label}</a></td>\n";
            echo "            <td>" . strftime('%x %X', $result->metadata->created) . "</td>\n";

            if (isset($persons[$result->metadata->creator]->guid))
            {
                echo "            <td><a href=\"{$prefix}__mfa/asgard/object/view/{$persons[$result->metadata->creator]->guid}/\">{$persons[$result->metadata->creator]->name}</a></td>\n";
            }
            else
            {
                echo "            <td>&nbsp;</td>\n";
            }

            echo "        </tr>\n";
        }
        echo "    </tbody>\n";
        echo "</table>\n";
        echo "<script type=\"text/javascript\">\n";
        echo "        // <![CDATA[\n";
        echo "            jQuery('#search_results').tablesorter(\n";
        echo "            {\n ";
        echo "                widgets: ['zebra'],";
        echo "                sortList: [[0,0]]\n";
        echo "            });\n";
        echo "        // ]]>\n";
        echo "    </script>\n";
    }
}
?>
