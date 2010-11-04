<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: virtual.php 26290 2010-06-07 12:39:36Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM group implementation supporting Virtual groups.
 *
 * <b>Virtual Group identifies</b>
 *
 * A virtual group is identified by the combination of the component name and
 * the component-specific virtual group name, combined by a dash (-).
 *
 * So, for example, the virtual group <i>approvers</i> of the component
 * <i>net.nehmer.static</i> would be named <i>net.nehmer.static-approvers</i>.
 *
 * This handle is used in all places to refer to a virtual group.
 *
 * @package midcom
 */
class midcom_core_group_virtual extends midcom_core_group
{
    /**
     * Internal state variable, the component we are assigned to.
     *
     * @access private
     * @var string
     */
    var $_component = '';

    /**
     * Internal state variable, the local vgroup name within our component.
     *
     * @access private
     * @var string
     */
    var $_localname = '';

    /**
     * The constructor retrieves the virtual group identified by its identifier by loading
     * the corresponding component.
     *
     * The object constructor should only be used by the framework itself, when components
     * work with vgroups, they should always deal with vgroup identifiers.
     *
     * @param mixed $id This is either a valid full identifier (without the vgroup: prefix)
     *        for a VGroup or a VGroup database record.
     */
    function __construct($id = null)
    {
        parent::__construct($id);
    }

    /**
     * Callback to load the virtual group. Checks whether the component in question is already
     * loaded. It then queries its vgroup listing for the name of this group.
     *
     * @return boolean Indicating success.
     */
    function _load($id)
    {
        debug_push_class(__CLASS__, __FUNCTION__);

        if (is_string($id))
        {
            // Strip vgroup prefixes if present.
            if (substr($id, 0, 7) == 'vgroup:')
            {
                $id = substr($id, 7);
            }

            $name_parts = explode('-', $id);

            if (count($name_parts) != 2)
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                    "Failed to load the virtual group {$id}: The name is invalid");
                // This will exit.
            }
            $component = $name_parts[0];
            $localname = $name_parts[1];

            // Try to load the VGroup from the database
            $qb = new midgard_query_builder('midcom_core_group_virtual_db');
            $qb->add_constraint('component', '=', $component);
            $qb->add_constraint('identifier', '=', $localname);
            $result = @$qb->execute();

            if (count($result) != 1)
            {
                debug_print_r('Resultset was:', $result);
                debug_add("Failed to load the virtual group {$id}: It was not found in the database, see the debug level log for more details.",
                    MIDCOM_LOG_INFO);
                return false;
            }
            $this->_storage = $result[0];
        }
        else if (is_a($id, 'midcom_core_group_virtual_db'))
        {
            $this->_storage = $id;
            $this->id = "vgroup:{$this->_storage->component}-{$this->_storage->identifier}";
        }

        $this->id = "vgroup:{$id}";

        $this->name = $this->_storage->name;
        $this->_component = $component;
        $this->_localname = $localname;
        $this->scope = MIDCOM_PRIVILEGE_SCOPE_VGROUPS;

        debug_pop();
        return true;
    }

    /**
     * Retrieves a list of users for which are a member in this group.
     *
     * @return Array A list of midcom_core_user objects in which are members of the current group, false on failure, indexed by their ID.
     */
    function list_members()
    {
        static $members = Array();

        if (! array_key_exists($this->id, $members))
        {
            if (!$_MIDCOM->componentloader->load_graceful($this->_component))
            {
                return false;
            }

            $interface = $_MIDCOM->componentloader->get_interface_class($this->_component);

            // Set internal sudo mode during the retrieval of vgroup members,
            // otherwise any DBA access control check can lead to an infinite loop.
            $_MIDCOM->auth->acl->_internal_sudo = true;
            $members[$this->id] = $interface->retrieve_vgroup_members($this->_localname);
            $_MIDCOM->auth->acl->_internal_sudo = false;

            if (is_null($members[$this->id]))
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("The component interface could not list the members of the vgroup {$this->id}.", MIDCOM_LOG_WARN);
                debug_pop();
                return false;
            }
        }
        return $members[$this->id];
    }

    /**
     * This method returns a list of all virtual groups in which the
     * MidCOM user passed is a member.
     *
     * This function is always called statically.
     *
     * @param midcom_core_user $user The user that should be looked-up.
     * @return Array An array of member groups or false on failure, indexed by their id..
     * @static
     */
    function list_memberships($user)
    {
        debug_push_class(__CLASS__, __FUNCTION__);

        $result = Array();

        foreach ($_MIDCOM->auth->get_all_vgroups() as $id => $name)
        {
            $vgroup = $_MIDCOM->auth->get_group($id);
            $members = $vgroup->list_members();
            if (! $members)
            {
                // Silently ignore failed membership retrievals, they were already logged.
                continue;
            }
            if (array_key_exists($user->id, $members))
            {
                $result[$vgroup->id] = $vgroup;
            }
        }

        debug_pop();

        return $result;
    }

}

?>