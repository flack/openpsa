<?php
$component = $data['component_data'];
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<li><a href="&(prefix);__ais/help/&(component['name']);/"><i class="fa fa-&(component['icon']);"></i> &(component['name']);</a>
    <span class="description">&(component['title']);</span>
</li>
