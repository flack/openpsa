<?php
/**
 * @package org.openpsa.calendar
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\datamanager;

/**
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_handler_resource extends midcom_baseclasses_components_handler
{
    /**
     * Handle the creation phase
     *
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get('resource')));
        $resource = new org_openpsa_calendar_resource_dba();

        // Load the controller instance
        $data['controller'] = datamanager::from_schemadb($this->_config->get('schemadb_resource'))
            ->set_storage($resource)
            ->get_controller();

        $workflow = $this->get_workflow('datamanager', ['controller' => $data['controller']]);
        return $workflow->run();
    }
}