<?php echo $data['editor_header_form_start'];?>
<div class="maa_permissions_assignees">
<?php
    if (!empty($data['editor_header_assignees']))
    {
        echo $data['editor_header_assignees'];
    } ?>
</div>
<table class="maa_permissions_items">
    <thead>
    <tr class="maa_permissions_rows_header">
        <?php echo $data['editor_header_titles']; ?>
    </tr>
    </thead>
    <tbody>
        <?php echo $data['editor_rows']; ?>
    </tbody>
</table>
<div class="maa_permissions_footer">
    <?php echo $data['editor_footer']; ?>
</div>
<?php echo $data['editor_header_form_end'];?>