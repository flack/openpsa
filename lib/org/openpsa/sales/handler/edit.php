<?php
/**
 * @package org.openpsa.sales
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\schemadb;

/**
 * Salesproject edit/create/delete handler
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_handler_edit extends midcom_baseclasses_components_handler
{
    /**
     * The salesproject we're working on
     *
     * @var org_openpsa_sales_salesproject_dba
     */
    private $_salesproject = null;

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->_salesproject = new org_openpsa_sales_salesproject_dba($args[0]);
        $this->_salesproject->require_do('midgard:update');

        $schemadb = schemadb::from_path($this->_config->get('schemadb_salesproject'));
        $field =& $schemadb->get('default')->get_field('customer');
        $field['type_config']['options'] = org_openpsa_helpers_list::task_groups($this->_salesproject);
        $dm = new datamanager($schemadb);
        $dm->set_storage($this->_salesproject);

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->_l10n->get('salesproject')));

        $workflow = $this->get_workflow('datamanager', ['controller' => $dm->get_controller()]);
        return $workflow->run();
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_new($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_user_do('midgard:create', null, org_openpsa_sales_salesproject_dba::class);

        $this->_salesproject = new org_openpsa_sales_salesproject_dba;

        $defaults = [
            'code' => org_openpsa_sales_salesproject_dba::generate_salesproject_number(),
            'owner' => midcom_connection::get_user()
        ];
        $schemadb = schemadb::from_path($this->_config->get('schemadb_salesproject'));

        if (!empty($args[0])) {
            $field =& $schemadb->get('default')->get_field('customer');
            try {
                $customer = new org_openpsa_contacts_group_dba($args[0]);
                $field['type_config']['options'] = [0 => '', $customer->id => $customer->official];

                $defaults['customer'] = $customer->id;
            } catch (midcom_error $e) {
                $customer = new org_openpsa_contacts_person_dba($args[0]);
                $defaults['customerContact'] = $customer->id;
                $field['type_config']['options'] = org_openpsa_helpers_list::task_groups(new org_openpsa_sales_salesproject_dba, 'id', [$customer->id => true]);
            }
            $this->add_breadcrumb($this->router->generate('list_customer', ['guid' => $customer->guid]),
                sprintf($this->_l10n->get('salesprojects with %s'), $customer->get_label()));
        }
        $dm = new datamanager($schemadb);
        $dm->set_defaults($defaults);
        $dm->set_storage($this->_salesproject);

        midcom::get()->head->set_pagetitle($this->_l10n->get('create salesproject'));

        $workflow = $this->get_workflow('datamanager', [
            'controller' => $dm->get_controller(),
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run();
    }

    public function save_callback()
    {
        return $this->router->generate('salesproject_view', ['guid' => $this->_salesproject->guid]);
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $this->_salesproject = new org_openpsa_sales_salesproject_dba($args[0]);
        $workflow = $this->get_workflow('delete', [
            'object' => $this->_salesproject,
            'recursive' => true
        ]);
        return $workflow->run();
    }
}
