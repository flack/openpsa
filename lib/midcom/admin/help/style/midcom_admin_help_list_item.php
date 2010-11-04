<?php
$component =& $data['component_data'];
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<li><a href="&(prefix);__ais/help/&(component['name']);/"><img src="<?php echo MIDCOM_STATIC_URL; ?>/&(component['icon']);" alt="" /> &(component['name']);</a>
    <span class="description">&(component['title']);</span>
</li>
