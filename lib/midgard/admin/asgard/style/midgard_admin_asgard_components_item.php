<?php
$component =& $data['component_data'];
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<li><h3><a href="&(prefix);__mfa/asgard/components/&(component['name']);/"><img src="<?php echo MIDCOM_STATIC_URL; ?>/&(component['icon']);" alt="" /> &(component['name']);</a></h3>
    <span class="version">&(component['version']);</span>
    <span class="description">&(component['title']);</span>
    <?php echo $component['toolbar']->render(); ?>
</li>
