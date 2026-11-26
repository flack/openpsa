<?php
/**
 * @package org.openpsa.sales
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\schemadb;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Query\Expr\Join;

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
        $schemadb->get('default')->get_field('customer')['type_config']['options'] = $this->get_customer_options($request);
        $dm = new datamanager($schemadb);
        $dm->set_storage($this->_salesproject);

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->_l10n->get('salesproject')));
        $this->add_customer_select_js();

        $workflow = $this->get_workflow('datamanager', ['controller' => $dm->get_controller()]);
        return $workflow->run($request);
    }

    public function _handler_new(Request $request, array &$data, ?string $guid = null)
    {
        midcom::get()->auth->require_user_do('midgard:create', class: org_openpsa_sales_salesproject_dba::class);

        $this->_salesproject = new org_openpsa_sales_salesproject_dba;

        if ($guid) {
            try {
                $customer = new org_openpsa_contacts_group_dba($guid);
                $this->_salesproject->customer = $customer->id;
            } catch (midcom_error) {
                $customer = new org_openpsa_contacts_person_dba($guid);
                $this->_salesproject->customerContact = $customer->id;
            }
            $this->add_breadcrumb($this->router->generate('list_customer', ['customer' => $customer->guid]),
                sprintf($this->_l10n->get('salesprojects with %s'), $customer->get_label()));
        }
        $schemadb = schemadb::from_path($this->_config->get('schemadb_salesproject'));
        $schemadb->get('default')->get_field('customer')['type_config']['options'] = $this->get_customer_options($request);
        $dm = new datamanager($schemadb);
        $dm->set_defaults([
            'code' => org_openpsa_sales_salesproject_dba::generate_salesproject_number(),
            'owner' => midcom_connection::get_user()
        ]);
        $dm->set_storage($this->_salesproject);

        midcom::get()->head->set_pagetitle($this->_l10n->get('create salesproject'));
        $this->add_customer_select_js();

        $data['controller'] = $dm->get_controller();
        $workflow = $this->get_workflow('datamanager', [
            'controller' => $data['controller'],
            'save_callback' => $this->save_callback(...)
        ]);
        return $workflow->run($request);
    }

    public function save_callback()
    {
        return $this->router->generate('salesproject_view', ['guid' => $this->_salesproject->guid]);
    }

    /**
     * Returns the organizations a customer contact is a member of (for the customer select)
     */
    public function _handler_customers(Request $request) : JsonResponse
    {
        midcom::get()->auth->require_valid_user();

        $groups = [];
        if ($contact = $request->query->getInt('contact')) {
            foreach ($this->list_groups([$contact]) as $id => $label) {
                $groups[] = ['id' => $id, 'label' => $label];
            }
        }
        return new JsonResponse($groups);
    }

    private function add_customer_select_js()
    {
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/' . $this->_component . '/sales.js');
        midcom::get()->head->add_jquery_state_script('init_customer_select(' . json_encode($this->router->generate('salesproject_customers')) . ');');
    }

    /**
     * Options for the customer select: The current customer plus the organizations of the
     * salesproject's contacts and the submitted customer contact (if any)
     */
    private function get_customer_options(Request $request) : array
    {
        $ret = [0 => ''];

        // Make sure the currently selected customer (if any) is listed
        if ($this->_salesproject->customer > 0) {
            // Make sure we can read the current customer for the name
            midcom::get()->auth->request_sudo($this->_component);
            $company = new org_openpsa_contacts_group_dba($this->_salesproject->customer);
            midcom::get()->auth->drop_sudo();
            $ret[$company->id] = $company->get_label();
        }
        $contacts = $this->_salesproject->contacts;

        // The customer contact may have been changed client-side, so its organizations have to be valid choices, too
        $selection = $request->request->all('org_openpsa_sales')['customerContact']['selection'] ?? '';
        if ($posted = json_decode($selection, true)) {
            $contacts[reset($posted)] = true;
        }

        $ret = $this->list_groups(array_keys($contacts)) + $ret;
        asort($ret);
        return $ret;
    }

    /**
     * List the groups the given persons are members of
     */
    private function list_groups(array $contact_ids) : array
    {
        if (empty($contact_ids)) {
            return [];
        }
        $qb = org_openpsa_contacts_group_dba::new_query_builder();
        // Skip magic groups and contact lists
        $qb->add_constraint('name', 'NOT LIKE', '\_\_%');

        $qb->get_doctrine()
            ->leftJoin('midgard_member', 'm', Join::WITH, 'm.gid = c.id')
            ->andWhere('m.uid IN (:contacts)')
            ->setParameter('contacts', $contact_ids);

        $ret = [];
        foreach ($qb->execute() as $company) {
            $ret[$company->id] = $company->get_label();
        }
        asort($ret);
        return $ret;
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
