<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
echo "<h2>";
echo $_MIDCOM->i18n->get_string('trash', 'midgard.admin.asgard');
echo "</h2>";
?>

<table class="deleted table_widget" id="deleted">
    <thead>
        <tr>
            <th><?php echo $_MIDCOM->i18n->get_string('type', 'midgard.admin.asgard'); ?></th>
            <th><?php echo $_MIDCOM->i18n->get_string('items in trash', 'midgard.admin.asgard'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($data['types'] as $type => $items)
        {
            if ($items == 0)
            {
                continue;
            }
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