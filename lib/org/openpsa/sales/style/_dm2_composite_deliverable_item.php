<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
$view_data =& $_MIDCOM->get_custom_context_data('midcom_helper_datamanager2_widget_composite');
$view = $view_data['item_html'];
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
            <td>&(view['title']:h);</td>
            <td>&(view['supplier']:h);</td>
            <td>&(view['pricePerUnit']:h); / &(view['unit']:h);</td>
            <td>&(view['costPerUnit']:h); &(view['costType']:h);</td>
            <td>&(view['units']:h);</td>
            <td>&(view['price']:h);</td>
            <td>&(view['cost']:h);</td>
            <td><?php
                if (array_key_exists('purchase_ok', $view))
                {
                    echo $view['purchase_ok'];
                }
            ?></td>