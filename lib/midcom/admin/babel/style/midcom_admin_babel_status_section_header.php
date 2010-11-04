<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<tr class="subheader">
    <th colspan="4">
        <?php 
        echo $data['l10n']->get("{$data['section']} components");
        ?>
    </th>
</tr>