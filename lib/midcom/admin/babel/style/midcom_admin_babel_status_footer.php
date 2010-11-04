<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
$languages = $data['l10n']->_language_db;
?>
    </tbody>
    <tfoot>
        <tr>
            <td></td>
            <td class="footer"><?php echo $data['strings_all']['translated']; ?></td>
            <td class="footer"><?php echo $data['strings_all']['total']; ?></td>
            <td class="footer"><?php echo round(100 / $data['strings_all']['total'] * $data['strings_all']['translated']); ?>%</td>
        </tr>
    </tfoot>
</table>
