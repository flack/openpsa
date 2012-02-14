<?php
$view_data =& $_MIDCOM->get_custom_context_data('midcom_helper_datamanager2_widget_composite');
$view = $view_data['item_html'];
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
            <td class="date">&(view['date']:h);</td>
            <td class="hours">&(view['hours']:h);</td>
            <td>&(view['invoiceable']:h);</td>
            <td>&(view['approved']:h);</td>
            <td>&(view['invoiced']:h);</td>
            <td>&(view['person']:h);</td>
            <td class="description">&(view['description']:h);</td>