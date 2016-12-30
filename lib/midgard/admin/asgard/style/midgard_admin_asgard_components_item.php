<?php
$component = $data['component_data'];
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<li><h3><a href="&(prefix);__mfa/asgard/components/&(component['name']);/"><img src="<?php echo MIDCOM_STATIC_URL; ?>/&(component['icon']);" alt="" /> &(component['name']);</a></h3>
    <div class="details">
        <span class="description">&(component['title']);</span>
        <?php echo $component['toolbar']->render(); ?>
    </div>
</li>
