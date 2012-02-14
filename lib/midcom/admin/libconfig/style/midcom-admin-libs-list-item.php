<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
$class = ($data['even'])?' class="even"':'';
$label = midcom::get('i18n')->get_string($data['name'], $data['name']);
?>
<tr&(class);>
<td><a href="&(prefix);__mfa/asgard_midcom.admin.libconfig/view/&(data['name']);">&(label); (&(data['name']);)</a>
</td>
</tr>