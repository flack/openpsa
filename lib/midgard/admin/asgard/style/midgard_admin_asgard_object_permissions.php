<?php echo $data['editor_header_form_start'];?>
<table width="100%" border="0">
    <tr class="maa_permissions_assignees">
        <td>
        <?php echo $data['editor_header_assignees'];?>
        </td>
    </tr>
    <tr class="maa_permissions_items_row">
        <td>
            <table width="100%" border="0" class="maa_permissions_items">
                <thead>
                    <tr class="maa_permissions_rows_header">
                <?php echo $data['editor_header_titles'];?>
                    </tr>
                </thead>
                <tbody>
                    <?php echo $data['editor_rows']; ?>
                </tbody>
            </table>
        </td>
    </tr>
    <tr class="maa_permissions_footer">
        <td>
            <?php echo $data['editor_footer']; ?>
        </td>
    </tr>
</table>
<?php echo $data['editor_header_form_end'];?>