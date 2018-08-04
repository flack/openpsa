<?php
/**
 * @package org.openpsa.sales
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Sales viewer interface class.
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_viewer extends midcom_baseclasses_components_request
{
    /**
     * Generic request startup work:
     *
     * - Add the LINK HTML HEAD elements
     */
    public function _on_handle($handler, array $args)
    {
        org_openpsa_widgets_contact::add_head_elements();

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.projects/projects.css");
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.invoices/invoices.css");
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.sales/sales.css");

        midcom::get()->auth->require_valid_user();
    }
}
