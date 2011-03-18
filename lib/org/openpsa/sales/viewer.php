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
     * - Load the Schema Database
     * - Add the LINK HTML HEAD elements
     */
    public function _on_handle($handler, $args)
    {
        $_MIDCOM->load_library('org.openpsa.contactwidget');

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.projects/projects.css");
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.invoices/invoices.css");
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.sales/sales.css");

        $_MIDCOM->auth->require_valid_user();
    }

    /**
     * Helper, updates the context so that we get a complete breadcrumb line towards the current
     * location.
     *
     * @param mixed $object
     * @param mixed &$handler The current handler
     */
    public static function add_breadcrumb_path($object, &$handler)
    {
        $tmp = array();

        while ($object)
        {
            if ($_MIDCOM->dbfactory->is_a($object, 'org_openpsa_sales_salesproject_deliverable_dba'))
            {
                if ($object->orgOpenpsaObtype == ORG_OPENPSA_PRODUCTS_DELIVERY_SUBSCRIPTION)
                {
                    $prefix = $handler->_l10n->get('subscription');
                }
                else
                {
                    $prefix = $handler->_l10n->get('single delivery');
                }
                $tmp["deliverable/{$object->guid}/"] = $prefix . ': ' . $object->title;
            }
            else
            {
                $tmp["salesproject/{$object->guid}/"] = $object->title;
            }
            $object = $object->get_parent();
        }
        $tmp = array_reverse($tmp);

        foreach ($tmp as $url => $title)
        {
            $handler->add_breadcrumb($url, $title);
        }
    }
}
?>