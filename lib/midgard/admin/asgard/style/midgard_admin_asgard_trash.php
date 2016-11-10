<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
echo "<h2>";
echo $data['l10n']->get('trash');
echo "</h2>";
?>

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
            ?>
            <tr>
                <td><a href="&(prefix);__mfa/asgard/trash/&(type);"><img src="<?php echo MIDCOM_STATIC_URL; ?>/stock-icons/16x16/trash-full.png" /> <?php echo midgard_admin_asgard_plugin::get_type_label($type); ?></a></td>
                <td>&(items);</td>
            </tr>
            <?php

        }
        ?>
    </tbody>
</table>
<script type="text/javascript">
     // <![CDATA[
        jQuery('#deleted').tablesorter(
        {
            widgets: ['zebra'],
            sortList: [[0,0]]
        });
    // ]]>
</script>