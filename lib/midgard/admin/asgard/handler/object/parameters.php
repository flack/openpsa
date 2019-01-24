<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Object parameters interface
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_handler_object_parameters extends midcom_baseclasses_components_handler
{
    use midgard_admin_asgard_handler;

    /**
     * The object we're working on
     *
     * @var midcom_core_dbaobject
     */
    private $_object;

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data()
    {
        $this->_request_data['object'] = $this->_object;
    }

    /**
     * Object editing view
     *
     * @param mixed $handler_id The ID of the handler.
     * @param string $guid The object's GUID
     * @param array $data The local request data.
     */
    public function _handler_edit($handler_id, $guid, array &$data)
    {
        $this->_object = midcom::get()->dbfactory->get_object_by_guid($guid);
        $this->_object->require_do('midgard:update');
        $this->_object->require_do('midgard:parameters');
        midcom::get()->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');

        // List all parameters for display
        $qb = midcom_db_parameter::new_query_builder();
        $qb->add_constraint('parentguid', '=', $this->_object->guid);
        $qb->add_order('domain');
        $qb->add_order('name');
        $data['parameters'] = $qb->execute();

        $this->_prepare_request_data();
        midgard_admin_asgard_plugin::bind_to_object($this->_object, $handler_id, $data);
        return $this->get_response();
    }

    /**
     * Shows the loaded object in editor.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $data The local request data.
     */
    public function _show_edit($handler_id, array &$data)
    {
        midcom_show_style('midgard_admin_asgard_object_parameters');
    }
}
