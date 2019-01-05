<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\schemadb;
use midcom\datamanager\datamanager;

/**
 * org.openpsa.contacts person handler and viewer class.
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_handler_person_privileges extends midcom_baseclasses_components_handler
{
    private function load_controller(midcom_db_person $person)
    {
        $schemadb = schemadb::from_path($this->_config->get('schemadb_acl'));

        // Get the calendar root event
        if ($root_event = org_openpsa_calendar_interface::find_root_event()) {
            $field =& $schemadb->get('default')->get_field('calendar');
            $field['type_config']['privilege_object'] = $root_event;
            $field['type_config']['assignee'] = 'user:' . $person->guid;
        }

        $dm = new datamanager($schemadb);
        return $dm
            ->set_storage($person)
            ->get_controller();
    }

    /**
     * @param array $args The argument list.
     */
    public function _handler_privileges(array $args)
    {
        $person = new midcom_db_person($args[0]);
        $person->require_do('midgard:privileges');

        midcom::get()->head->set_pagetitle($this->_l10n->get("permissions"));

        $workflow = $this->get_workflow('datamanager', ['controller' => $this->load_controller($person)]);
        return $workflow->run();
    }
}
