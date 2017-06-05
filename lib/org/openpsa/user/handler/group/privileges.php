<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.user group handler and viewer class.
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_handler_group_privileges extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_edit
{
    /**
     * The group we're working with, if any
     *
     * @var midcom_db_group
     */
    private $_group = null;

    /**
     * Loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    public function load_schemadb()
    {
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_acl'));

        // Get the calendar root event
        if ($root_event = org_openpsa_calendar_interface::find_root_event()) {
            $schemadb['default']->fields['calendar']['type_config']['privilege_object'] = $root_event;
            $schemadb['default']->fields['calendar']['type_config']['assignee'] = 'group:' . $this->_group->guid;
        }

        return $schemadb;
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
        $this->_group = new midcom_db_group($args[0]);
        $this->_group->require_do('midgard:privileges');

        midcom::get()->head->set_pagetitle($this->_l10n->get("permissions"));

        $workflow = $this->get_workflow('datamanager2', ['controller' => $this->get_controller('simple', $this->_group)]);
        return $workflow->run();
    }
}
