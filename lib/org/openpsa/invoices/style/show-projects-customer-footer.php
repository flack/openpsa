<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
?>
        </tbody>
    </table>
    <div class="form_toolbar">
        <input type="hidden"<?php echo $data['disabled']; ?> name="org_openpsa_invoices_invoice_customer" value="<?php echo $data['customer']; ?>" />
        <input type="submit"<?php echo $data['disabled']; ?> name="org_openpsa_invoices_invoice" value="<?php echo $data['l10n']->get('create invoice'); ?>" />
    </div>
</form>