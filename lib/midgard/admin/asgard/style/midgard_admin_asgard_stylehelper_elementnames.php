<div class="midgard_admin_asgard_stylehelper_help">
    <h3><?php echo $data['l10n']->get('creating new style element'); ?></h3>
    <div>
        <?php
        echo "<ul>\n";
        foreach ($data['help_style_elementnames']['elements'] as $component => $elements) {
            $link = $data['router']->generate('components_component', ['component' => $component]);

            echo "<li class=\"component\">";
            echo "<a href=\"{$link}\">" . midcom::get()->i18n->get_string($component, $component) ." </a>\n";
            echo "<ul>\n";
            foreach ($elements as $name => $path) {
                echo "<li>";

                if ($data['handler_id'] == 'object_create') {
                    // We're creating an element, on clicking a name we should input it to the form
                    echo '<a class="namepicker">';
                } else {
                    // Clicking should take us to form creating such an element
                    $link = $data['router']->generate('object_create', ['type' => 'midgard_element', 'parent_guid' => $data['object']->guid]);
                    echo "<a href=\"{$link}?defaults[name]={$name}\">";
                }

                echo "{$name}</a></li>\n";
            }
            echo "</ul>\n</li>\n";
        }
        echo "</ul>\n";
        ?>
    </div>
</div>

<script type="text/javascript">
    $(".midgard_admin_asgard_stylehelper_help").accordion({ header: 'h3', active: false, collapsible: true });

    $('.midgard_admin_asgard_stylehelper_help a.namepicker').on('click', function()
    {
        var form_id = $('.object_edit form.datamanager2').attr('id');
        $('#' + form_id + '_name').val($(this).text());
    });
// ]]>
</script>
