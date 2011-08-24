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
        return true;
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
        else
        {
            return MIDCOM_PRIVILEGE_INHERIT;
        }
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
            if (! $this->storage->object)
            {
                $this->storage->create_temporary_object();
            }
            $object = $this->storage->object;

            $this->privilege = $object->get_privilege($this->privilege_name, $this->assignee, $this->classname);
            $this->privilege->value = $value;
        }
        // If we have no object and should set INHERIT, we do nothing, this
        // is the default.
    }

    /**
     * Loads the privilege from the DB if and only if a storage object is already present
     * and we have sufficient privileges.
     */
    function convert_from_storage($source)
    {
        if (   $this->storage->object
            && $this->storage->object->can_do('midgard:privileges'))
        {
            $this->privilege = $this->storage->object->get_privilege($this->privilege_name, $this->assignee, $this->classname);
        }
    }

    /**
     * Writes the privilege to the DB unless the privilege member is still null (in which
     * case the inherited default will kick in). In all other cases the type will have
     * already populated storage->object with a temporary object in set_value().
     */
    function convert_to_storage()
    {
        if ($this->privilege)
        {
            $object = $this->storage->object;

            // If we have sufficient privileges, we set all privilege accordingly.
            // otherwise we log this and exit silently.
            if ($object->can_do('midgard:privileges'))
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

    function convert_from_csv ($source)
    {
        $this->set_value((int) $source);
    }

    function convert_to_csv()
    {
        return ($this->get_value());
    }

    function convert_to_raw()
    {
        return $this->get_value();
    }

    function convert_to_html()
    {
        switch ($this->get_value())
        {
            case MIDCOM_PRIVILEGE_ALLOW:
                return $this->_l10n->get('widget privilege: allow');

            case MIDCOM_PRIVILEGE_DENY:
                return $this->_l10n->get('widget privilege: deny');

            case MIDCOM_PRIVILEGE_INHERIT:
                return $this->_l10n->get('widget privilege: inherit');

            default:
                return $this->get_value();
        }
    }
}
?>