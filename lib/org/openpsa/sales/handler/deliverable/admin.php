<?php
/**
 * @package org.openpsa.sales
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\schemadb;
use midcom\datamanager\datamanager;
use midcom\datamanager\controller;

/**
 * Projects edit/delete deliverable handler
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_handler_deliverable_admin extends midcom_baseclasses_components_handler
{
    /**
     * The deliverable to operate on
     *
     * @var org_openpsa_sales_salesproject_deliverable_dba
     */
    private $_deliverable = null;

    /**
     * @return \midcom\datamanager\controller
     */
    private function load_controller()
    {
        $schemadb = schemadb::from_path($this->_config->get('schemadb_deliverable'));

        $schema = $schemadb->get('subscription');
        $mc = new org_openpsa_relatedto_collector($this->_deliverable->guid, midcom_services_at_entry_dba::class);
        $mc->add_object_order('start', 'ASC');
        $mc->set_object_limit(1);
        $at_entries = $mc->get_related_objects();

        if (sizeof($at_entries) != 1) {
            if (   (   $this->_deliverable->continuous
                    || $this->_deliverable->end > time())
                && $this->_deliverable->state == org_openpsa_sales_salesproject_deliverable_dba::STATE_STARTED) {
                $schema->get_field('next_cycle')['hidden'] = false;
            }
        } else {
            $schema->get_field('next_cycle')['hidden'] = false;

            $entry = $at_entries[0];

            $schema->get_field('next_cycle')['default'] = $entry->start;
            $schema->get_field('at_entry')['default'] = $entry->id;
        }
        $dm = new datamanager($schemadb);
        $dm->set_storage($this->_deliverable);
        return $dm->get_controller();
    }

    /**
     * Displays a deliverable edit view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->_deliverable = new org_openpsa_sales_salesproject_deliverable_dba($args[0]);
        $this->_deliverable->require_do('midgard:update');

        $data['controller'] = $this->load_controller();

        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/' . $this->_component . '/sales.js');
        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->_l10n->get('deliverable')));

        $workflow = $this->get_workflow('datamanager', [
            'controller' => $data['controller'],
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run();
    }

    public function save_callback(controller $controller)
    {
        $formdata = $controller->get_form_values();
        if (isset($formdata['at_entry'])) {
            $this->process_at_entry((int) $formdata['at_entry'], (int) $formdata['next_cycle']);
        }
        $this->_master->process_notify_date((int) $formdata['notify'], $this->_deliverable);
    }

    private function process_at_entry($at_entry, $next_cycle)
    {
        if (!empty($at_entry)) {
            $entry = new midcom_services_at_entry_dba($at_entry);
            if ($next_cycle == 0) {
                $entry->delete();
                $this->_deliverable->end_subscription();
            } elseif ($next_cycle != $entry->start) {
                //@todo If next_cycle is changed to be in the past, should we check if this would lead
                //to multiple runs immediately? i.e. if you set a monthly subscriptions next cycle to
                //one year in the past, this would trigger twelve consecutive runs and maybe
                //the user needs to be warned about that...

                $entry->start = $next_cycle;
                $entry->update();
            }
        } elseif ($next_cycle > 0) {
            //TODO: This code is copied from scheduler, and should be merged into a separate method at some point
            $args = [
                'deliverable' => $this->_deliverable->guid,
                'cycle'       => 2, //TODO: We might want to calculate the correct cycle number from start and unit at some point
            ];
            $at_entry = new midcom_services_at_entry_dba();
            $at_entry->start = $next_cycle;
            $at_entry->component = $this->_component;
            $at_entry->method = 'new_subscription_cycle';
            $at_entry->arguments = $args;

            if (!$at_entry->create()) {
                throw new midcom_error('AT registration failed, last midgard error was: ' . midcom_connection::get_error_string());
            }
            org_openpsa_relatedto_plugin::create($at_entry, 'midcom.services.at', $this->_deliverable, $this->_component);
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $deliverable = new org_openpsa_sales_salesproject_deliverable_dba($args[0]);
        $salesproject = $deliverable->get_parent();
        $workflow = $this->get_workflow('delete', [
            'object' => $deliverable,
            'success_url' => "salesproject/{$salesproject->guid}/"
        ]);
        return $workflow->run();
    }
}
