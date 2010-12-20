<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
$class = ($data['even'])?' class="even"':'';
$label = $_MIDCOM->i18n->get_string($data['name'], $data['name']);
?>
<tr&(class);>
<td><a href="&(prefix);__mfa/asgard_midcom.admin.libconfig/view/&(data['name']);">&(label); (&(data['name']);)</a>
</td>
</tr>