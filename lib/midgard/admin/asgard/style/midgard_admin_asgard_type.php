<?php
if ($data['component'] == 'midgard') {
    $component_label = 'Midgard CMS';
} else {
    $component_label = midcom::get()->i18n->get_string($data['component'], $data['component']);
}
echo "<h2>";
printf($data['l10n_midcom']->get('%s in %s'),
        midgard_admin_asgard_plugin::get_type_label($data['type']),
        $component_label);
echo "</h2>";

echo "<p>";
if ($data['parent_type']) {
    $link = $data['router']->generate('type', ['type' => $data['parent_type']]);
    printf($data['l10n']->get('%s is under type %s'), midgard_admin_asgard_plugin::get_type_label($data['type']), "<a href=\"{$link}\">" . midgard_admin_asgard_plugin::get_type_label($data['parent_type']) . "</a>") . ' ';
}

if ($data['component'] == 'midgard') {
    echo $data['l10n']->get('this is a midgard core type') . "</p>\n";
} else {
    $link = $data['router']->generate('components_component', ['component' => $data['component']]);
    printf($data['l10n']->get('this type belongs to %s component'), "<a href=\"{$link}\">{$data['component']}</a>") . "</p>\n";
}
?>

&(data['help']:h);

<form method="get">
<div id="search_bar">
    <label>
        <?php echo $data['l10n']->get('search'); ?>
        <input id="search_field" type="text" name="search" class="search"<?php if (isset($_GET['search'])) {
    echo " value=\"{$_GET['search']}\"";
} ?> />
    </label>
    <input class="search" type="submit" value="<?php echo $data['l10n']->get('go'); ?>" />
</div>
</form>
<script type="text/javascript">
document.getElementById('search_field').focus();
</script>

<?php
if (isset($data['search_results'])) {
    if (!$data['search_results']) {
        echo "<p>" . $data['l10n']->get('no results') . "</p>\n";
    } else {
        echo "<table class=\"table_widget\" id=\"search_results\">\n";
        echo "    <thead>\n";
        echo "        <tr>\n";
        echo "            <th>" . $data['l10n_midcom']->get('title') . "</th>\n";
        echo "            <th>" . $data['l10n']->get('created on') . "</th>\n";
        echo "            <th>" . $data['l10n']->get('created by') . "</th>\n";
        echo "        </tr>\n";
        echo "    </thead>\n";
        echo "    <tbody>\n";
        $formatter = $data['l10n']->get_formatter();
        foreach ($data['search_results'] as $result) {
            $icon = midcom_helper_reflector::get_object_icon($result);
            $label = midcom_helper_reflector_tree::resolve_path($result, '/');
            $creator = midcom::get()->auth->get_user($result->metadata->creator);
            $link = $data['router']->generate('object_' . $data['default_mode'], ['guid' => $result->guid]);

            echo "        <tr>\n";
            echo "            <td><a href=\"{$link}/\">{$icon} {$label}</a></td>\n";
            echo "            <td>" . $formatter->datetime($result->metadata->created, IntlDateFormatter::SHORT, IntlDateFormatter::MEDIUM) . "</td>\n";

            if (!empty($creator->guid)) {
                $link = $data['router']->generate('object_view', ['guid' => $creator->guid]);
                echo "            <td><a href=\"{$link}/\">{$creator->name}</a></td>\n";
            } else {
                echo "            <td>&nbsp;</td>\n";
            }

            echo "        </tr>\n";
        }
        echo "    </tbody>\n";
        echo "</table>\n";
        echo "<script type=\"text/javascript\">\n";
        echo "    jQuery('#search_results').tablesorter({\n ";
        echo "        sortList: [[0,0]]\n";
        echo "    });\n";
        echo "</script>\n";
    }
}
?>
