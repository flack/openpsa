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
        $this->_salesprojects = $qb->execute();

        foreach ($this->_salesprojects as $key => $salesproject)
        {
            // Populate previous/next actions in the project
            $salesproject->get_actions();
        }
        // TODO: Filtering

        $data['list_title'] = $args[0];

        org_openpsa_widgets_grid::add_head_elements();
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

        $data['salesprojects'] = $this->_salesprojects;

        midcom_show_style('show-salesproject-grid');
    }
}
?>