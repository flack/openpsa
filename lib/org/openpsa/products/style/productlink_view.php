<?php
$view = $data['view_productlink'];
?>
<h1><?php echo $data['l10n']->get('productlink'); ?></h1>

<table>
    <tbody>
        <tr>
            <td><?php echo $data['l10n']->get('product group'); ?></td>
            <td>&(view['productGroup']:h);</td>
        </tr>
        <tr>
            <td><?php echo $data['l10n']->get('product'); ?></td>
            <td>&(view['product']:h);</td>
        </tr>
    </tbody>
</table>
