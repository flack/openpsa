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
     * Object editing view
     */
    public function _handler_edit(string $handler_id, string $guid, array &$data)
    {
        $object = midcom::get()->dbfactory->get_object_by_guid($guid);
        $object->require_do('midgard:update');
        $object->require_do('midgard:parameters');
        midcom::get()->auth->require_user_do('midgard.admin.asgard:manage_objects', class: 'midgard_admin_asgard_plugin');

        // List all parameters for display
        $qb = midcom_db_parameter::new_query_builder();
        $qb->add_constraint('parentguid', '=', $object->guid);
        $qb->add_order('domain');
        $qb->add_order('name');
        $data['parameters'] = $qb->execute();

        $data['object'] = $object;
        midgard_admin_asgard_plugin::bind_to_object($object, $handler_id, $data);
        return $this->get_response('midgard_admin_asgard_object_parameters');
    }
}
