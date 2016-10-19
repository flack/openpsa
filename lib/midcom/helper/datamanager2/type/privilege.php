<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 Simple privilege datatype.
 *
 * The class encapsulates a single privilege record with its three states of
 * ALLOWED, DENIED and INHERITED.
 *
 * It requires the privilege widget for correct display.
 *
 * Note, that magic class privileges are currently not supported by this type. Only
 * content-, not self-style privileges are managed by it.
 *
 * <b>Available configuration options:</b>
 *
 * - <i>string privilege_name:</i> The name of the privilege to manage. Must be set.
 * - <i>string assignee:</i> The assignee of the privilege to manage. Must be set.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_type_privilege extends midcom_helper_datamanager2_type
{
    /**
     * The privilege record encapsulated by this type (note that this does not
     * necessarily is an already persisted privilege).
     *
     * This member may be null in case that we do not yet have a storage object.
     * Setting/getting the value of this type must thus not be done directly
     * in this member but using the get/set_value accessors.
     *
     * @var midcom_core_privilege
     */
    var $privilege = null;

    /**
     * The name of the privilege to manage (f.x. 'midgard:update')
     *
     * @var string
     */
    var $privilege_name = null;

    /**
     * The name assignee of the privilege to manage (f.x. 'USERS')
     *
     * @var string
     */
    var $assignee = null;

    /**
     * Classname the privilege applies to
     *
     * @var string
     */
    var $classname = '';

    public $privilege_object;

    /**
     * This startup handler validates that the class is populated correctly.
     */
    public function _on_initialize()
    {
        if (   ! $this->name
            || ! $this->assignee)
        {
            throw new midcom_error("The field {$this->name} had no name or assignee specified with it, cannot start up.");
        }
    }

    private function get_privilege_object()
    {
        if (!$this->privilege_object)
        {
            if (!$this->storage->object)
            {
                return false;
            }
            $this->privilege_object = $this->storage->object;
        }
        if (!$this->privilege_object->can_do('midgard:privileges'))
        {
            return false;
        }
        return $this->privilege_object;
    }

    /**
     * Returns the current privilege value, defaulting to MIDCOM_PRIVILEGE_INHERIT in case the
     * privilege is yet unset.
     *
     * @return int Privilege value
     */
    function get_value()
    {
        if ($this->privilege)
        {
            return $this->privilege->value;
        }
        return MIDCOM_PRIVILEGE_INHERIT;
    }

    /**
     * Sets the privileges value. If the privilege record has not yet been created,
     * it creates a new one (getting a temporary object from the DM core if
     * necessary).
     *
     * @param int $value The new value of the privilege.
     */
    function set_value($value)
    {
        if ($this->privilege)
        {
            $this->privilege->value = $value;
        }
        else if ($value != MIDCOM_PRIVILEGE_INHERIT)
        {
            if (!$this->storage->object)
            {
                $this->storage->create_temporary_object();
            }

            if ($privilege_object = $this->get_privilege_object())
            {
                $this->privilege = $privilege_object->get_privilege($this->privilege_name, $this->assignee, $this->classname);
                $this->privilege->value = $value;
            }
        }
        // If we have no object and should set INHERIT, we do nothing, this
        // is the default.
    }

    /**
     *
     * @return boolean
     */
    public function get_effective_value()
    {
        $privilege_object = $this->get_privilege_object();
        if (!$this->privilege)
        {
            return false;
        }
        if (!$privilege_object)
        {
            $defaults = midcom::get()->auth->acl->get_default_privileges();
            return ($defaults[$this->privilege->privilegename] === MIDCOM_PRIVILEGE_ALLOW);
        }
        if ($this->privilege->assignee == 'SELF')
        {
            if ($privilege_object instanceof midcom_db_group)
            {
                //There's no sane way to query group privileges in auth right now, so we only return defaults
                $defaults = midcom::get()->auth->acl->get_default_privileges();
                return ($defaults[$this->privilege->privilegename] === MIDCOM_PRIVILEGE_ALLOW);
            }
            return midcom::get()->auth->can_user_do($this->privilege->privilegename, new midcom_core_user($privilege_object), $this->privilege->classname);
        }
        if ($principal = midcom::get()->auth->get_assignee($this->privilege->assignee))
        {
            return $privilege_object->can_do($this->privilege->privilegename, $principal);
        }
        return $privilege_object->can_do($this->privilege->privilegename, $this->privilege->assignee);
    }

    /**
     * Loads the privilege from the DB if and only if a storage object is already present
     * and we have sufficient privileges.
     */
    public function convert_from_storage($source)
    {
        if ($privilege_object = $this->get_privilege_object())
        {
            $this->privilege = $privilege_object->get_privilege($this->privilege_name, $this->assignee, $this->classname);
        }
    }

    /**
     * Writes the privilege to the DB unless the privilege member is still null (in which
     * case the inherited default will kick in). In all other cases the type will have
     * already populated storage->object with a temporary object in set_value().
     */
    public function convert_to_storage()
    {
        if ($this->privilege)
        {
            // If we have sufficient privileges, we set all privilege accordingly.
            // otherwise we log this and exit silently.
            if ($this->get_privilege_object())
            {
                $this->privilege->store();
            }
            else
            {
                debug_add("Could not synchronize privilege of field {$this->name}, access was denied, midgard:privileges is needed here.",
                    MIDCOM_LOG_WARN);
            }
        }
    }

    public function convert_from_csv ($source)
    {
        $this->set_value((int) $source);
    }

    public function convert_to_csv()
    {
        return ($this->get_value());
    }

    public function convert_to_raw()
    {
        return $this->get_value();
    }

    public function convert_to_html()
    {
        switch ($this->get_value())
        {
            case MIDCOM_PRIVILEGE_ALLOW:
                return $this->_l10n->get('widget privilege: allow');

            case MIDCOM_PRIVILEGE_DENY:
                return $this->_l10n->get('widget privilege: deny');

            case MIDCOM_PRIVILEGE_INHERIT:
                $effective_value = $this->get_effective_value() ? 'allow' : 'deny';
                return sprintf($this->_l10n->get('widget privilege: inherit %s'), $this->_l10n->get('widget privilege: ' . $effective_value));

            default:
                return $this->get_value();
        }
    }
}
