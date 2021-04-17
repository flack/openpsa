<?php
$url = $data['router']->generate('user_passwords');
?>
<h1><?php echo $data['view_title']; ?></h1>
<div id="midgard_admin_user_passwords">
    <a href="&(url);" target="_blank"><?php echo $data['l10n']->get('generate passwords'); ?></a>
</div>
<script type="text/javascript">
    $('#midgard_admin_user_passwords a')
        .attr('href', '#')
        .attr('target', '_self')
        .click(function() {
            $(this.parentNode).load('&(url);?timestamp=<?php echo time(); ?>');
        });
</script>

<?php
$data['controller']->display_form();
?>