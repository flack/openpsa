<form method="post" action="<?php echo midcom_connection::get_url('uri'); ?>" id="midgard_admin_asgard_navigation_form">
    <p>
    <select name="midgard_type" id="midgard_admin_asgard_navigation_chooser">
        <option value=""><?php echo $_MIDCOM->i18n->get_string('midgard.admin.asgard', 'midgard.admin.asgard'); ?></option>
<?php
foreach ($data['label_mapping'] as $type => $label)
{
    if ($type === $data['navigation_type'])
    {
        $selected = ' selected="selected"';
    }
    else
    {
        $selected = '';
    }
    
    echo "        <option value=\"{$type}\"{$selected}>{$label}</option>\n";
}
?>
    </select>
    <input type="submit" name="midgard_type_change" class="submit" value="<?php echo $_MIDCOM->i18n->get_string('go', 'midgard.admin.asgard'); ?>" />
    </p>
</form>
<script type="text/javascript">
    // <![CDATA[
        jQuery('#midgard_admin_asgard_navigation_form input[type="submit"]').css({display:'none'});
        
        jQuery('#midgard_admin_asgard_navigation_chooser').change(function()
        {
            if (!this.value)
            {
                window.location = '<?php echo midcom_connection::get_url('self') . '__mfa/asgard/'; ?>';
            }
            else
            {
                window.location = '<?php echo midcom_connection::get_url('self') . '__mfa/asgard/'; ?>' + jQuery(this).attr('value') + '/';
            }
        });
    // ]]>
</script>
