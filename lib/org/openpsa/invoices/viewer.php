<?php
/**
 * @package org.openpsa.invoices
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Invoice interface class.
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_viewer extends midcom_baseclasses_components_request
{
    public function _on_handle($handler, $args)
    {
        $_MIDCOM->load_library('org.openpsa.contactwidget');
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.invoices/invoices.css");
    }

    public function add_next_previous($object, $toolbar, $urlprefix)
    {
        if ($object->number > 1)
        {
            $previous = org_openpsa_invoices_invoice_dba::get_by_number($object->number - 1);
            $toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => $urlprefix . $previous->guid . '/',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('previous'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/back.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'p',
                )
             );
        }
        if (($object->number + 1) < $object->generate_invoice_number())
        {
            $next = org_openpsa_invoices_invoice_dba::get_by_number($object->number + 1);
            $toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => $urlprefix . $next->guid . '/',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('next'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/next.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'n',
                )
            );
        }
    }
}
?>