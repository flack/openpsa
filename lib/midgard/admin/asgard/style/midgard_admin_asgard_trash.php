<h2><?php echo $data['l10n']->get('trash'); ?></h2>

<table class="deleted table_widget" id="deleted">
    <thead>
        <tr>
            <th><?php echo $data['l10n']->get('type'); ?></th>
            <th><?php echo $data['l10n']->get('items in trash'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach (array_filter($data['types']) as $type => $items) {
            $link = $data['router']->generate('trash_type', ['type' => $type]);
            $icon = midcom_helper_reflector::get_object_icon(new $type);
            ?>
            <tr>
                <td><a href="&(link);">&(icon:h); <?php echo midgard_admin_asgard_plugin::get_type_label($type); ?></a></td>
                <td>&(items);</td>
            </tr>
            <?php
        }
        ?>
    </tbody>
</table>
<script type="text/javascript">
    jQuery('#deleted').tablesorter({
        sortList: [[0,0]],
        textExtraction: function(node) {
            return $(node).text();
        }
    });
</script>