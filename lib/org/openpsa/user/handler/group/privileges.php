<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\schemadb;

/**
 * org.openpsa.user group handler and viewer class.
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_handler_group_privileges extends midcom_baseclasses_components_handler
{
    private function load_controller(midcom_db_group $group)
    {
        $schemadb = schemadb::from_path($this->_config->get('schemadb_acl'));

        // Get the calendar root event
        if ($root_event = org_openpsa_calendar_interface::find_root_event()) {
            $field =& $schemadb->get('default')->get_field('calendar');
            $field['type_config']['privilege_object'] = $root_event;
            $field['type_config']['assignee'] = 'group:' . $group->guid;
        }

        $dm = new datamanager($schemadb);
        return $dm
            ->set_storage($group)
            ->get_controller();
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_privileges($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_user_do('org.openpsa.user:manage', null, 'org_openpsa_user_interface');

        // Check if we get the group
        $group = new midcom_db_group($args[0]);
        $group->require_do('midgard:privileges');

        midcom::get()->head->set_pagetitle($this->_l10n->get("permissions"));

        $workflow = $this->get_workflow('datamanager', ['controller' => $this->load_controller($group)]);
        return $workflow->run();
    }
}
