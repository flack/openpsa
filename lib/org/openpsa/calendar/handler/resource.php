<?php
/**
 * @package org.openpsa.calendar
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_handler_resource extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_create
{
    /**
     * @var org_openpsa_calendar_resource_dba
     */
    private $resource;

    public function load_schemadb()
    {
        return midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_resource'));
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    public function & dm2_create_callback(&$controller)
    {
        $this->resource= new org_openpsa_calendar_resource_dba();
        if (!$this->resource->create()) {
            debug_print_r('We operated on this object:', $this->resource);
            throw new midcom_error('Failed to create a new resource. Last Midgard error was: ' . midcom_connection::get_error_string());
        }

        return $this->resource;
    }

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

        // Load the controller instance
        $data['controller'] = $this->get_controller('create');

        $workflow = $this->get_workflow('datamanager2', ['controller' => $data['controller']]);
        return $workflow->run();
    }
}