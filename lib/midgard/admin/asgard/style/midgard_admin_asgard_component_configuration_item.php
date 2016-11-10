<tr class="odd">
<td class="key" colspan="2"><span><?php echo midcom::get()->i18n->get_string($data['key'], $data['name']); ?></span></td>
</tr>
<tr class="even">
<?php if ($data['local'] == '<strong>N/A</strong>') {
    ?>
    <td class="global" colspan="2">&(data['global']:h);</td>
<?php 
} else {
    ?>
    <td class="global">&(data['global']:h);</td>
    <td class="local">&(data['local']:h);</td>
<?php 
} ?>
</tr>