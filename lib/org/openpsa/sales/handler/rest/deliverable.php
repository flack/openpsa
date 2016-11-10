<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 * @author Jan Floegel
 * @package org.openpsa.sales
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General
 */

/**
 * org.openpsa.sales deliverable rest handler
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_handler_rest_deliverable extends midcom_baseclasses_components_handler_rest
{
    public function get_object_classname()
    {
        return "org_openpsa_sales_salesproject_deliverable_dba";
    }

    public function handle_update()
    {
        $this->retrieve_object();

        // if endtime was set, we need to set continuous to false
        if (isset($this->_request['params']['end'])) {
            $this->_request['params']['continuous'] = false;

            // cleanup at entries
            midcom::get()->auth->request_sudo($this->_component);
            $at_entries = $this->_object->get_at_entries();
            $deliverable_end = $this->_request['params']['end'];
            foreach ($at_entries as $at_entry) {
                $deliverable_end = $at_entry->start;
                $at_entry->delete();
            }
            midcom::get()->auth->drop_sudo();

            $this->_request['params']['end'] = $deliverable_end;
        }

        parent::handle_update();
    }
}
