<?php
/**
 * @package org.openpsa.core
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.core ACL synchronizer.
 *
 * @package org.openpsa.core
 */
class org_openpsa_core_acl_synchronizer
{
    public function write_acls(midcom_core_dbaobject $object, $owner_id, $accesstype) : bool
    {
        if (   empty($owner_id)
            || empty($accesstype)) {
            return false;
        }

        // TODO: Figure out what kind of write handler we need based on the situation (calendar/document etc)
        return $this->_write_full_midcom_acls($object, $owner_id, $accesstype);
    }

    private function _write_full_midcom_acls(midcom_core_dbaobject $object, $owner_id, $accesstype) : bool
    {
        $owner_object = midcom::get()->auth->get_assignee($owner_id);
        if (empty($owner_object->id)) {
            debug_add('Given owner was invalid, aborting');
            return false;
        }

        $privileges = $object->get_privileges();
        $needed_privileges = [
            'midgard:read' => ['value' => MIDCOM_PRIVILEGE_DENY, 'assignee' => 'EVERYONE'],
            'midgard:owner' => ['value' => MIDCOM_PRIVILEGE_ALLOW]
        ];
        // Handle ACL storage
        switch ($accesstype) {
            case org_openpsa_core_acl::ACCESS_PUBLIC:
            case org_openpsa_core_acl::ACCESS_AGGREGATED:
                debug_add("Public object, everybody can read");
                $needed_privileges['midgard:read']['value'] = MIDCOM_PRIVILEGE_ALLOW;
                $needed_privileges['midgard:owner']['assignee'] = $owner_id;
                $this->_set_attachment_permission($object, 'midgard:read', 'EVERYONE', MIDCOM_PRIVILEGE_ALLOW);
                break;
            case org_openpsa_core_acl::ACCESS_PRIVATE:
                debug_add("Private object, only user can read and write");
                $needed_privileges['midgard:owner']['assignee'] = midcom::get()->auth->user->id;
                $this->_set_attachment_permission($object, 'midgard:read', midcom::get()->auth->user->id, MIDCOM_PRIVILEGE_ALLOW);
                break;
            case org_openpsa_core_acl::ACCESS_WGRESTRICTED:
                debug_add("Restricted object, only workgroup members can read and write. Subscribers can read");
                //fall-through. Once subscriber groups get reimplemented, the appropriate code should be added here
            case org_openpsa_core_acl::ACCESS_WGPRIVATE:
                debug_add("Private object, only workgroup members can read and write");
                $needed_privileges['midgard:owner']['assignee'] = $owner_id;
                $this->_set_attachment_permission($object, 'midgard:read', $owner_id, MIDCOM_PRIVILEGE_ALLOW);
                break;
        }

        if ($privileges) {
            foreach ($privileges as $privilege) {
                if (   !empty($needed_privileges[$privilege->privilegename])
                    && $needed_privileges[$privilege->privilegename]['assignee'] == $privilege->assignee
                    && $needed_privileges[$privilege->privilegename]['value'] == $privilege->value) {
                    unset($needed_privileges[$privilege->privilegename]);
                    continue;
                }
                // Clear old ACLs applying to others than current user or selected owner group
                if (   $privilege->assignee != midcom::get()->auth->user->id
                    && $privilege->assignee != $owner_id) {
                    debug_add("Removing privilege {$privilege->privilegename} from {$privilege->assignee}");
                    $object->unset_privilege($privilege->privilegename, $privilege->assignee);
                }
            }
        }

        foreach ($needed_privileges as $name => $priv) {
            $object->set_privilege($name, $priv['assignee'], $priv['value']);
        }

        return true;
    }

    private function _set_attachment_permission(midcom_core_dbaobject $object, string $privilege, string $assignee, $value)
    {
        foreach ($object->list_attachments() as $attachment) {
            debug_add("Setting {$value} to privilege {$privilege} for {$assignee} to attachment #{$attachment->id}");
            $attachment->set_privilege($privilege, $assignee, $value);
        }
    }
}
