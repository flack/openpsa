<?php
/**
 * @package midcom.dba
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\dba;

use midgard_query_builder;
use midcom;
use midcom_error;
use midcom_core_privilege;

/**
 * midcom privileges support
 *
 * @package midcom.dba
 */
trait privileges
{
    /**
     * Read all privilege records and return them accordingly.
     *
     * You need privilege access to get this information (midgard:read (tested during
     * construction) and midgard:privileges) otherwise, the call will fail.
     *
     * @return midcom_core_privilege[] A list of privilege objects or false on failure.
     */
    public function get_privileges()
    {
        if (!$this->can_do('midgard:privileges')) {
            debug_add('Could not query the privileges, permission denied.', MIDCOM_LOG_WARN);
            return false;
        }

        return midcom_core_privilege::get_all_privileges($this->guid);
    }

    /**
     * Set a privilege on an object.
     *
     * This requires both midgard:update and midgard:privileges.
     *
     * You can either pass a ready made privilege record or a privilege/assignee/value
     * combination suitable for usage with create_new_privilege_object() (see there).
     *
     * @param mixed $privilege Either the full privilege object (midcom_core_privilege) to set or the name of the privilege (string).
     *     If the name was specified, the other parameters must be specified as well.
     * @param mixed $assignee A valid assignee suitable for midcom_core_privilege::set_privilege(). This defaults to the currently
     *     active user if authenticated or to 'EVERYONE' otherwise (invalid if $privilege is a midcom_core_privilege).
     * @param int $value The privilege value, this defaults to MIDCOM_PRIVILEGE_ALLOW (invalid if $privilege is a midcom_core_privilege).
     * @param string $classname An optional class name to which a SELF privilege gets restricted to. Only valid for SELF privileges
     *     (invalid if $privilege is a midcom_core_privilege).
     * @return boolean Indicating success.
     * @see midcom_services_auth
     */
    public function set_privilege($privilege, $assignee = null, $value = MIDCOM_PRIVILEGE_ALLOW, $classname = '')
    {
        if (   !$this->can_do('midgard:update')
            || !$this->can_do('midgard:privileges')) {
            debug_add("Failed to set a privilege, midgard:update or midgard:privileges on the " . get_class($this) . " {$this->guid} not granted for the current user.",
            MIDCOM_LOG_ERROR);
            return false;
        }

        if (is_a($privilege, 'midcom_core_privilege')) {
            return $privilege->store();
        }
        if (is_string($privilege)) {
            $tmp = $this->create_new_privilege_object($privilege, $assignee, $value, $classname);
            if (!$tmp) {
                throw new midcom_error('Failed to create the privilege. See debug level log for details.');
            }
            return $tmp->store();
        }
        throw new midcom_error('Unknown $privilege argument type');
    }

    /**
     * Unset a privilege on an object (e.g. set it to INHERIT).
     *
     * @param mixed $privilege Either the full privilege object (midcom_core_privilege) to set or the name of the privilege (string).
     *     If the name was specified, the other parameters must be specified as well.
     * @param mixed $assignee A valid assignee suitable for midcom_core_privilege::set_privilege(). This defaults to the currently
     *     active user if authenticated or to 'EVERYONE' otherwise (invalid if $privilege is a midcom_core_privilege).
     * @param string $classname An optional class name to which a SELF privilege gets restricted to. Only valid for SELF privileges
     *     (invalid if $privilege is a midcom_core_privilege).
     * @return boolean Indicating Success.
     */
    public function unset_privilege($privilege, $assignee = null, $classname = '')
    {
        if (   !$this->can_do('midgard:update')
            || !$this->can_do('midgard:privileges')) {
            debug_add("Failed to unset a privilege, midgard:update or midgard:privileges on the " . get_class($this) . " {$this->guid} not granted for the current user.",
            MIDCOM_LOG_ERROR);
            return false;
        }

        if ($assignee === null) {
            $assignee = midcom::get()->auth->user ?: 'EVERYONE';
        }

        if (is_a($privilege, 'midcom_core_privilege')) {
            $priv = $privilege;
        } elseif (is_string($privilege)) {
            $priv = $this->get_privilege($privilege, $assignee, $classname);
            if (!$priv) {
                return false;
            }
        } else {
            throw new midcom_error('Invalid arguments for unset_privilege. See debug level log for details.');
        }

        return $priv->drop();
    }

    /**
     * Looks up a privilege by its parameters.
     *
     * @param string $privilege The name of the privilege.
     * @param mixed $assignee Either a valid magic assignee (SELF, EVERYONE, USERS, ANONYMOUS), a midcom_core_user or a
     *     midcom_core_group object or subtype thereof.
     * @param string $classname An optional class name to which a SELF privilege is restricted to.
     * @return midcom_core_privilege The privilege record from the database.
     */
    public function get_privilege($privilege, $assignee, $classname = '')
    {
        if (!$this->can_do('midgard:privileges')) {
            debug_add("Failed to get a privilege, midgard:update or midgard:privileges on the " . get_class($this) . " {$this->guid} not granted for the current user.",
            MIDCOM_LOG_ERROR);
            return false;
        }

        if (is_object($assignee)) {
            $assignee = $assignee->id;
        }
        return midcom_core_privilege::get_privilege($this, $privilege, $assignee, $classname);
    }

    /**
     * Unsets all privileges
     *
     * @return boolean Indicating success.
     */
    public function unset_all_privileges()
    {
        $privileges = $this->get_privileges();
        if (!$privileges) {
            debug_add('Failed to access the privileges. See above for details.', MIDCOM_LOG_ERROR);
            return false;
        }
        foreach ($privileges as $privilege) {
            if ($this->unset_privilege($privilege)) {
                debug_add('Failed to drop a privilege record, see debug log for more information, aborting.', MIDCOM_LOG_WARN);
                return false;
            }
        }
        return true;
    }

    /**
     * Create a new privilege object. The privilege will
     * be initialized with the values given in the arguments, as outlined below.
     *
     * This call requires the <i>midgard:privileges</i> privilege.
     *
     * @param string $name The name of the privilege to add.
     * @param int $value The privilege value, this defaults to MIDCOM_PRIVILEGE_ALLOW.
     * @param mixed $assignee A valid assignee suitable for midcom_core_privilege::set_privilege(). This defaults to the currently
     *     active user if authenticated or to 'EVERYONE' otherwise.
     * @param string $classname An optional class name to which a SELF privilege gets restricted to. Only valid for SELF privileges.
     * @return midcom_core_privilege The newly created privilege record or false on failure.
     */
    public function create_new_privilege_object($name, $assignee = null, $value = MIDCOM_PRIVILEGE_ALLOW, $classname = '')
    {
        if (!$this->can_do('midgard:privileges')) {
            debug_add('Could not create a new privilege, permission denied.', MIDCOM_LOG_WARN);
            return false;
        }

        if ($assignee === null) {
            $assignee = midcom::get()->auth->user ?: 'EVERYONE';
        }

        $privilege = new midcom_core_privilege();
        if (!$privilege->set_assignee($assignee)) {
            debug_add('Failed to set the assignee, aborting.', MIDCOM_LOG_INFO);
            return false;
        }
        $privilege->set_object($this);
        $privilege->privilegename = $name;
        $privilege->value = $value;
        $privilege->classname = $classname;
        if (!$privilege->validate()) {
            debug_add('Failed to validate the newly created privilege.', MIDCOM_LOG_INFO);
            return false;
        }
        return $privilege;
    }
}
