<?php
/**
 * @package org.openpsa.sales
 * @author The Midgard Project, http://www.midgard-project.org
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
     */
    public function _handler_list($handler_id, array $args, array &$data)
    {
        $_MIDCOM->auth->require_valid_user();
        $statuscode = 'ORG_OPENPSA_SALESPROJECTSTATUS_' . strtoupper($args[0]);
        if (!defined($statuscode))
        {
            throw new midcom_error('Unknown list type ' . $args[0]);
        }

        $qb = org_openpsa_sales_salesproject_dba::new_query_builder();
        $qb->add_constraint('status', '=', constant($statuscode));
        $salesprojects = $qb->execute();

        foreach ($salesprojects as $key => $salesproject)
        {
            $this->_salesprojects_map_id_key[$salesproject->id] = $key;

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
            $sort_method = 'by_' . $_REQUEST['org_openpsa_sales_sort_by'];
            if (method_exists('org_openpsa_sales_sort', $sort_method))
            {
                switch ($sort_method)
                {
                    case 'by_prev_action':
                        $GLOBALS['org_openpsa_sales_project_map'] =& $this->_salesprojects_map_id_key;
                        $GLOBALS['org_openpsa_sales_project_cache'] =& $this->_salesprojects;
                        break;
                    case 'by_next_action':
                        $GLOBALS['org_openpsa_sales_project_map'] =& $this->_salesprojects_map_id_key;
                        $GLOBALS['org_openpsa_sales_project_cache'] =& $this->_salesprojects;
                        break;
                    case 'customer':
                        $GLOBALS['org_openpsa_sales_customer_cache'] =& $this->_customers;
                        break;
                    case 'owner':
                        $GLOBALS['org_openpsa_sales_owner_cache'] =& $this->_owners;
                        break;
                    default:
                        break;
                }
                uasort($this->_salesprojects, array ('org_openpsa_sales_sort', $sort_method));
            }
            else
            {
                debug_add("Sort {$_REQUEST['org_openpsa_sales_sort_by']} is not supported", MIDCOM_LOG_WARN);
            }
        }
        if (   isset($_REQUEST['org_openpsa_sales_sort_order'])
            && strtolower($_REQUEST['org_openpsa_sales_sort_order']) == 'asc')
        {
            $this->_salesprojects = array_reverse($this->_salesprojects, true);
        }

        $data['list_title'] = $args[0];

        org_openpsa_core_ui_jqgrid::add_head_elements();
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.core/table2csv.js');
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.core/list.css");

        $this->add_breadcrumb("", $this->_l10n->get('salesprojects ' . $data['list_title']));
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_list($handler_id, array &$data)
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