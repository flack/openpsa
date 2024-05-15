<?php
/**
 * @package org.openpsa.sales
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\schemadb;
use Symfony\Component\HttpFoundation\Request;

/**
 * Salesproject edit/create/delete handler
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_handler_edit extends midcom_baseclasses_components_handler
{
    private org_openpsa_sales_salesproject_dba $_salesproject;

    public function _handler_edit(Request $request, string $guid)
    {
        $this->_salesproject = new org_openpsa_sales_salesproject_dba($guid);
        $this->_salesproject->require_do('midgard:update');

        $schemadb = schemadb::from_path($this->_config->get('schemadb_salesproject'));
        $schemadb->get('default')->get_field('customer')['type_config']['options'] = $this->list_groups();
        $dm = new datamanager($schemadb);
        $dm->set_storage($this->_salesproject);

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->_l10n->get('salesproject')));

        $workflow = $this->get_workflow('datamanager', ['controller' => $dm->get_controller()]);
        return $workflow->run($request);
    }

    public function _handler_new(Request $request, ?string $guid = null)
    {
        midcom::get()->auth->require_user_do('midgard:create', class: org_openpsa_sales_salesproject_dba::class);

        $this->_salesproject = new org_openpsa_sales_salesproject_dba;

        $defaults = [
            'code' => org_openpsa_sales_salesproject_dba::generate_salesproject_number(),
            'owner' => midcom_connection::get_user()
        ];
        $schemadb = schemadb::from_path($this->_config->get('schemadb_salesproject'));

        if ($guid) {
            $field =& $schemadb->get('default')->get_field('customer');
            try {
                $customer = new org_openpsa_contacts_group_dba($guid);
                $field['type_config']['options'] = [0 => '', $customer->id => $customer->official];

                $defaults['customer'] = $customer->id;
            } catch (midcom_error) {
                $customer = new org_openpsa_contacts_person_dba($guid);
                $defaults['customerContact'] = $customer->id;
                $field['type_config']['options'] = $this->list_groups([$customer->id => true]);
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
            'save_callback' => $this->save_callback(...)
        ]);
        return $workflow->run($request);
    }

    public function save_callback()
    {
        return $this->router->generate('salesproject_view', ['guid' => $this->_salesproject->guid]);
    }

    /**
     * Function for listing groups salesproject contacts are members of
     *
     * @param array $contacts Default contacts for nonpersistent objects
     */
    private function list_groups(array $contacts = []) : array
    {
        $ret = [0 => ''];

        // Make sure the currently selected customer (if any) is listed
        if ($this->_salesproject->customer > 0) {
            // Make sure we can read the current customer for the name
            midcom::get()->auth->request_sudo('org.openpsa.helpers');
            $this->load_group($ret, $this->_salesproject->customer);
            midcom::get()->auth->drop_sudo();
        }
        if (empty($contacts)) {
            $this->_salesproject->get_members();
            $contacts = $this->_salesproject->contacts;

            if (empty($contacts)) {
                return $ret;
            }
        }

        $mc = midcom_db_member::new_collector();
        $mc->add_constraint('uid', 'IN', array_keys($contacts));
        // Skip magic groups and contact lists
        $mc->add_constraint('gid.name', 'NOT LIKE', '\_\_%');
        $memberships = $mc->get_values('gid');

        foreach ($memberships as $gid) {
            $this->load_group($ret, $gid);
        }

        reset($ret);
        asort($ret);
        return $ret;
    }

    private function load_group(array &$ret, int $company_id)
    {
        if (!array_key_exists($company_id, $ret)) {
            try {
                $company = new org_openpsa_contacts_group_dba($company_id);
                $ret[$company->id] = $company->get_label();
            } catch (midcom_error $e) {
                $e->log();
            }
        }
    }

    public function _handler_delete(Request $request, string $guid)
    {
        $this->_salesproject = new org_openpsa_sales_salesproject_dba($guid);
        $workflow = $this->get_workflow('delete', [
            'object' => $this->_salesproject,
            'recursive' => true
        ]);
        return $workflow->run($request);
    }
}
