<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<div class="midgard_admin_asgard_stylehelper_help">
    <h3><?php echo $data['l10n']->get('creating new style element'); ?></h3>
    <div>
        <?php
        echo "<ul>\n";
        foreach ($data['help_style_elementnames']['elements'] as $component => $elements) {
            echo "<li class=\"component\">";
            if ($component == 'midcom') {
                echo "Midgard CMS\n";
            } else {
                echo "<a href=\"{$prefix}__mfa/asgard/components/{$component}/\">" . midcom::get()->i18n->get_string($component, $component) ." </a>\n";
            }

            echo "<ul>\n";
            foreach ($elements as $name => $path) {
                echo "<li>";

                if ($data['handler_id'] == 'object_create') {
                    // We're creating an element, on clicking a name we should input it to the form
                    echo '<a class="namepicker">';
                } else {
                    // Clicking should take us to form creating such an element
                    echo "<a href=\"{$prefix}__mfa/asgard/object/create/midgard_element/{$data['object']->guid}/?defaults[name]={$name}\">";
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
// <![CDATA[
$(document).ready(function(){
        $(".midgard_admin_asgard_stylehelper_help").accordion({ header: 'h3', active: false, collapsible: true });

        $('.midgard_admin_asgard_stylehelper_help a.namepicker').on('click', function()
        {
            var form_id = $('.object_edit form.datamanager2').attr('id');
            $('#' + form_id + '_name').val($(this).text());
        });
});
// ]]>
</script>
