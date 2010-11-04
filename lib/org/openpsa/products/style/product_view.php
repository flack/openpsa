<?php
$view = $data['view_product'];
?>
<h1>&(view['code']:h); &(view['title']:h);</h1>

<table>
    <tbody>
        <tr>
            <td><?php echo $data['l10n_midcom']->get('tags'); ?></td>
            <td>&(view['tags']:h);</td>
        </tr>
        <tr>
            <td><?php echo $data['l10n']->get('product group'); ?></td>
            <td>&(view['productGroup']:h);</td>
        </tr>
        <tr>
            <td><?php echo $data['l10n']->get('supplier'); ?></td>
            <td>&(view['supplier']:h);</td>
        </tr>
        <tr>
            <td><?php echo $data['l10n']->get('delivery type'); ?></td>
            <td>&(view['delivery']:h);</td>
        </tr>
        <tr>
            <td><?php echo $data['l10n']->get('type'); ?></td>
            <td>&(view['orgOpenpsaObtype']:h);</td>
        </tr>
        <tr>
            <td><?php echo $data['l10n']->get('price'); ?></td>
            <td>&(view['price']:h); / &(view['unit']:h);</td>
        </tr>
        <tr>
            <td><?php echo $data['l10n']->get('cost'); ?></td>
            <td>&(view['cost']:h); &(view['costType']:h);</td>
        </tr>
        <!-- TODO: Show supplier, etc -->
    </tbody>
</table>

&(view['description']:h);

<?php
if (   $data['enable_components']
    && array_key_exists('components', $view))
{
    ?>
    &(view['components']:h);
    <?php
}
?>