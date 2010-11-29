<?php
/**
 * @package org.openpsa.sales
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: list.php 26593 2010-08-08 19:10:31Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Sales project list handler
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_handler_list extends midcom_baseclasses_components_handler
{
    /**
     * The list of salesprojects.
     *
     * @var Array
     */
    private $_salesprojects = array();

    /**
     * The map of salesprojects id key relations.
     *
     * @var Array
     */
    private $_salesprojects_map_id_key = array();

    /**
     * The cache of customers.
     *
     * @var Array
     */
    private $_customers = array();

    /**
     * The cache of owners.
     *
     * @var Array
     */
    private $_owners = array();

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_list($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();

        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.core/table2csv.js');

        $data['list_title'] = $args[0];

        $qb = org_openpsa_sales_salesproject_dba::new_query_builder();

        switch ($args[0])
        {
            case 'active':
                $qb->add_constraint('status', '=', ORG_OPENPSA_SALESPROJECTSTATUS_ACTIVE);
                break;
            case 'won':
                $qb->add_constraint('status', '=', ORG_OPENPSA_SALESPROJECTSTATUS_WON);
                break;
            case 'canceled':
                $qb->add_constraint('status', '=', ORG_OPENPSA_SALESPROJECTSTATUS_CANCELED);
                break;
            case 'lost':
                $qb->add_constraint('status', '=', ORG_OPENPSA_SALESPROJECTSTATUS_LOST);
                break;
            case 'delivered':
                $qb->add_constraint('status', '=', ORG_OPENPSA_SALESPROJECTSTATUS_DELIVERED);
                break;
            case 'invoiced':
                $qb->add_constraint('status', '=', ORG_OPENPSA_SALESPROJECTSTATUS_INVOICED);
                break;
            default:
                return false;
        }

        $salesprojects = $qb->execute();

        foreach ($salesprojects as $key => $salesproject)
        {
            $this->_salesprojects_map_id_key[$salesproject->id] = $key;

            if ($salesproject->customer)
            {
                // Cache the customer, we need it later too
                if (!isset($this->_customers[$salesproject->customer]))
                {
                    $this->_customers[$salesproject->customer] = new org_openpsa_contacts_group_dba($salesproject->customer);
                }
            }

            if (!isset($this->_owners[$salesproject->owner]))
            {
                $this->_owners[$salesproject->owner] = new org_openpsa_contacts_person_dba($salesproject->owner);
            }

            // Populate previous/next actions in the project
            $salesproject->get_actions();

            $this->_salesprojects[$key] = $salesproject;
        }
        // TODO: Filtering

        // Sorting
        if (isset($_REQUEST['org_openpsa_sales_sort_by']))
        {
            switch($_REQUEST['org_openpsa_sales_sort_by'])
            {
                case 'title':
                    uasort($this->_salesprojects, array ('org_openpsa_sales_sort', 'by_title'));
                    break;
                case 'value':
                    uasort($this->_salesprojects, array ('org_openpsa_sales_sort', 'by_value'));
                    break;
                case 'profit':
                    uasort($this->_salesprojects, array ('org_openpsa_sales_sort', 'by_profit'));
                    break;
                case 'weighted_value':
                    uasort($this->_salesprojects, array ('org_openpsa_sales_sort', 'by_weighted_value'));
                    break;
                case 'close_est':
                    uasort($this->_salesprojects, array ('org_openpsa_sales_sort', 'by_close_est'));
                    break;
                case 'probability':
                    uasort($this->_salesprojects, array ('org_openpsa_sales_sort', 'by_probability'));
                    break;
                case 'prev_action':
                    $GLOBALS['org_openpsa_sales_project_map'] =& $this->_salesprojects_map_id_key;
                    $GLOBALS['org_openpsa_sales_project_cache'] =& $this->_salesprojects;
                    uasort($this->_salesprojects, array ('org_openpsa_sales_sort', 'by_prev_action'));
                    break;
                case 'next_action':
                    $GLOBALS['org_openpsa_sales_project_map'] =& $this->_salesprojects_map_id_key;
                    $GLOBALS['org_openpsa_sales_project_cache'] =& $this->_salesprojects;
                    uasort($this->_salesprojects, array ('org_openpsa_sales_sort', 'by_next_action'));
                    break;
                case 'customer':
                    $GLOBALS['org_openpsa_sales_customer_cache'] =& $this->_customers;
                    uasort($this->_salesprojects, array ('org_openpsa_sales_sort', 'by_customer'));
                    break;
                case 'owner':
                    $GLOBALS['org_openpsa_sales_owner_cache'] =& $this->_owners;
                    uasort($this->_salesprojects, array ('org_openpsa_sales_sort', 'by_owner'));
                    break;
                default:
                    debug_add("Sort {$_REQUEST['org_openpsa_sales_sort_by']} is not supported", MIDCOM_LOG_WARN);
                    break;
            }
        }
        if (   isset($_REQUEST['org_openpsa_sales_sort_order'])
            && strtolower($_REQUEST['org_openpsa_sales_sort_order']) == 'asc')
        {
            $this->_salesprojects = array_reverse($this->_salesprojects, true);
        }

        org_openpsa_core_ui::enable_jqgrid();

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.core/list.css");

        $this->add_breadcrumb("", $this->_l10n->get('salesprojects ' . $data['list_title']));

        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_list($handler_id, &$data)
    {
        if (count($this->_salesprojects) == 0)
        {
            return;
        }

        // Locate Contacts node for linking
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $this->_request_data['contacts_url'] = $siteconfig->get_node_full_url('org.openpsa.contacts');
        $this->_request_data['reports_url'] = $siteconfig->get_node_full_url('org.openpsa.reports');

        $data['owners'] = $this->_owners;
        $data['customers'] = $this->_customers;

        $data['salesprojects'] = $this->_salesprojects;

        midcom_show_style('show-salesproject-grid');
    }
}
?>