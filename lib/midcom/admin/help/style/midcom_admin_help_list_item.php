<?php
$component = $data['component_data'];
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<li><a href="&(prefix);__ais/help/&(component['name']);/"><img src="<?php echo MIDCOM_STATIC_URL; ?>/&(component['icon']);" alt="" /> &(component['name']);</a>
    <span class="description">&(component['title']);</span>
</li>
