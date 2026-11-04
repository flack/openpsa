<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 * @author Jan Floegel
 * @package org.openpsa.contacts
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General
 */

/**
 * org.openpsa.contacts person rest handler
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_handler_rest_person extends midcom_baseclasses_components_handler_rest
{
    public function get_object_classname() : string
    {
        return org_openpsa_contacts_person_dba::class;
    }

    public function handle_create()
    {
        // not a person field, so it must not reach bind_data()
        $group_id = $this->_request['params']['group_id'] ?? null;
        unset($this->_request['params']['group_id']);

        parent::handle_create();

        // add to group
        if ($group_id !== null) {
            $group = new midcom_db_group(intval($group_id));

            $member = new midcom_db_member();
            $member->uid = $this->_object->id;
            $member->gid = $group->id;

            // deactivating RCS entry generation (performance)
            $member->_use_rcs = false;
            $member->create();
        }
    }
}
