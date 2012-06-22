<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<h1><?php echo $data['view_title']; ?></h1>
<?php
if ($data['handler_id'] === '____mfa-asgard_midcom.admin.user-user_edit_password')
{
?>
<div id="midcom_admin_user_passwords">
    <a href="&(prefix);__mfa/asgard_midcom.admin.user/password/" target="_blank"><?php echo $data['l10n']->get('generate passwords'); ?></a>
</div>
<script type="text/javascript">
    // <![CDATA[
        jQuery('#midcom_admin_user_passwords a')
            .attr('href', '#')
            .attr('target', '_self')
            .click(function()
            {
                jQuery(this.parentNode).load('&(prefix);__mfa/asgard_midcom.admin.user/password/?ajax&timestamp=<?php echo time(); ?>');
            });
    // ]]>
</script>

<?php
}

$data['controller']->display_form();
?>