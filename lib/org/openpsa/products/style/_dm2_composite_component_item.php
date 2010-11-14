<?php
$view_data =& $_MIDCOM->get_custom_context_data('midcom_helper_datamanager2_widget_composite');
$view = $view_data['item_html'];
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
            <td>&(view['component']:h);</td>
            <td>&(view['pieces']:h);</td>
            <td>&(view['description']:h);</td>
            <!-- TODO: Show supplier, etc -->