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
        org_openpsa_widgets_contact::add_head_elements();
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.invoices/invoices.css");
    }

    public function add_next_previous($object, $toolbar, $urlprefix)
    {
        if ($object->number > 1)
        {
            $qb = org_openpsa_invoices_invoice_dba::new_query_builder();
            $qb->add_constraint('number', '<', $object->number);
            $qb->set_limit(1);
            $qb->add_order('number', 'DESC');
            $results = $qb->execute();

            if (sizeof($results) == 1)
            {
                $toolbar->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => $urlprefix . $results[0]->guid . '/',
                        MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('previous'),
                        MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/back.png',
                        MIDCOM_TOOLBAR_ACCESSKEY => 'p',
                    )
                );
            }
        }
        if (($object->number + 1) < $object->generate_invoice_number())
        {
            $qb = org_openpsa_invoices_invoice_dba::new_query_builder();
            $qb->add_constraint('number', '>', $object->number);
            $qb->set_limit(1);
            $qb->add_order('number', 'ASC');
            $results = $qb->execute();

            $toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => $urlprefix . $results[0]->guid . '/',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('next'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/next.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'n',
                )
            );
        }
    }
}
?>