<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<h1><?php echo $data['view_title']; ?></h1>
<div id="midgard_admin_user_passwords">
    <a href="&(prefix);__mfa/asgard_midgard.admin.user/password/" target="_blank"><?php echo $data['l10n']->get('generate passwords'); ?></a>
</div>
<script type="text/javascript">
    // <![CDATA[
        jQuery('#midgard_admin_user_passwords a')
            .attr('href', '#')
            .attr('target', '_self')
            .click(function()
            {
                jQuery(this.parentNode).load('&(prefix);__mfa/asgard_midgard.admin.user/password/?ajax&timestamp=<?php echo time(); ?>');
            });
    // ]]>
</script>

<?php
$data['controller']->display_form();
?>