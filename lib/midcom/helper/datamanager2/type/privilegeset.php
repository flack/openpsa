<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 privilege set datatype.
 *
 * This type encapsulates a simple privilege or set thereof. You need to specify privilege name,
 * assignee and value. If the corresponding checkbox is set, all privileges will be set accordingly.
 * If it is unset, all privileges will be set to INHERIT. This type only supports a checkbox as
 * widget.
 *
 * When determining the current value of the type, only the first specified privilege is checked.
 * If it is set and matches the configuraiton, the whole list is assumed set. On every sync-to-storage
 * cycle, the current boolean state will be set to accordingly (thus overwriting existing privileges).
 *
 * If the current user does not have privileges permissions on the storage object, nothing is done.
 *
 * As with the attachment types, this type also uses the base storage object directly. If no object
 * is set while reading, no temporary object is requested, the privilege is assumed unset in that
 * case.
 *
 * The class extends the boolean type, overriding the storage I/O methods only. The storage location
 * should be set to null.
 *
 * While all boolean widgets are usable with this type, it is recommended to use the privilegecheckbox
 * widget instead, which validates permissions before actually doing anything.
 *
 * <b>Available configuration options:</b>
 *
 * - All of the boolean base type.
 * - <i>Array privileges:</i> This array is a list of arrays containing the privilege name, assignee
 *   and value, in that order. Example: 'privileges' => Array( Array('midgard:update', 'USERS',
 *   MIDCOM_PRIVILEGE_ALLOW)).
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_type_privilegeset extends midcom_helper_datamanager2_type_boolean
{
    /**
     * Privileges declarations.
     *
     * This array is a list of arrays containing the privilege name, assignee
     * and value, in that order. Example: 'privileges' => Array( Array('midgard:update', 'USERS',
     * MIDCOM_PRIVILEGE_ALLOW)).
     *
     * Assignees should be valid string-based identifiers like group:xxx or user:xxxx etc.
     * The value must be one of MIDCOM_PRIVILEGE_ALLOW or ..._DENY.
     *
     * @var Array
     */
    public $privileges = Array();

    /**
     * This startup handler validates that the privileges array is populated.
     */
    public function _on_initialize()
    {
        if (! $this->privileges)
        {
            throw new midcom_error("The field {$this->name} had no privileges specified with it, cannot start up.");
        }
        return true;
    }

    /**
     * Loads the privileges value from the database if we have sufficient privileges, otherwise
     * the value stays false. This is ok as storage operations will be ignored as well.
     */
    function convert_from_storage($source)
    {
        // Initialize
        $this->_value = false;

        if (   $this->storage->object
            && $this->storage->object->can_do('midgard:privileges'))
        {
            // We have a storage object, we check against the first privilege.
            $privilege = $this->storage->object->get_privilege
            (
                $this->privileges[0][0],
                $this->privileges[0][1]
            );
            if ($privilege->value == $this->privileges[0][2])
            {
                $this->value = true;
            }
        }
    }

    function convert_to_raw()
    {
        return '';
    }

    /**
     * Stores the privileges to the database. This call retrieves a temporary object if necessary.
     * If the midgard:privileges privilege is missing, nothing is updated.
     */
    function convert_to_storage()
    {
        // Get us a storage object shortcut, ensure that this is always possible
        if (! $this->storage->object)
        {
            $this->storage->create_temporary_object();
        }
        $object = $this->storage->object;

        // If we have sufficient privileges, we set all privileges accordingly.
        // otherwise we log this and exit silently.
        if ($object->can_do('midgard:privileges'))
        {
            foreach ($this->privileges as $spec)
            {
                $object->set_privilege
                (
                    $spec[0],
                    $spec[1],
                    ($this->value ? $spec[2] : MIDCOM_PRIVILEGE_INHERIT)
                );
            }
        }
        else
        {
            debug_add("Could not synchronize privileges of field {$this->name}, access was denied, midgard:privileges is needed here.", MIDCOM_LOG_WARN);
        }
    }
}
?>